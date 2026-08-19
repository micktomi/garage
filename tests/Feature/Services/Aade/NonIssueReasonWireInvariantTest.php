<?php

namespace Tests\Feature\Services\Aade;

use App\Enums\ClosureDocument;
use App\Enums\NonIssueReason;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Micktomi\GarageAadeBridge\Outbox\Models\OutboxEntry;
use Micktomi\GarageAadeBridge\Outbox\PayloadCodec;
use Micktomi\GarageAadeBridge\Xml\UpdateClientXmlSerializer;
use Micktomi\GarageAadeBridge\Xml\XsdValidator;
use Tests\TestCase;

/**
 * The completion form's "αιτιολογία μη έκδοσης" select lives inside a `hidden`
 * wrapper. `hidden` hides; it does not stop a control from being submitted —
 * so the παραστατικό and a reason for issuing no παραστατικό can arrive in the
 * same request. ΑΑΔΕ rejects that combination (codes 203/205).
 *
 * What stands between the two is a single saving() hook in WorkOrder. It was
 * never covered by anything that went over the wire, which is what makes it
 * dangerous: it looks like a tidy-up and it is actually load-bearing.
 *
 * "Wire level" here means both ends of the wire — the request body is built
 * from the rendered DOM the way a browser would build it, and the resulting
 * outbox payload is decoded, serialised and validated against the official
 * ΑΑΔΕ XSD rather than merely inspected as an array.
 */
class NonIssueReasonWireInvariantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The invariant itself: whatever the client sends, a παραστατικό other
     * than "none" can never produce a payload carrying reasonNonIssueType.
     * Stated against an explicitly hostile request so it stays true no matter
     * what the form is later changed to look like.
     */
    public function test_a_reason_sent_alongside_a_real_document_never_reaches_aade(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->readyWorkOrder('ΜΗΕ-1000');

        $this->actingAs($user)->patch(route('workshop.work-orders.status', $workOrder), [
            'lock_version' => $workOrder->lock_version,
            'status' => WorkOrderStatus::Completed->value,
            'closure_document' => ClosureDocument::Invoice->value,
            'non_issue_reason' => NonIssueReason::Warranty->value,
        ])->assertRedirect();

        $this->assertNull(
            $workOrder->fresh()->non_issue_reason,
            'A stored reason would resurface the moment the closure document was corrected to "none".',
        );

        $payload = $this->updatePayloadFor($workOrder);

        $this->assertNull($payload['reasonNonIssueType']);
        $this->assertFalse($payload['nonIssueInvoice']);
        $this->assertSame(2, $payload['invoiceKind']);

        $this->assertValidUpdateClientDocument($payload);
    }

    /**
     * Same request, taken from the page instead of invented: every control the
     * browser would submit, with the παραστατικό switched to a real one.
     */
    public function test_the_form_the_browser_actually_submits_produces_a_valid_payload(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->readyWorkOrder('ΜΗΕ-2000');

        $body = $this->completionFormBody($user, $workOrder);
        $body['closure_document'] = ClosureDocument::Invoice->value;

        $this->actingAs($user)
            ->patch(route('workshop.work-orders.status', $workOrder), $body)
            ->assertRedirect();

        $this->assertNull($workOrder->fresh()->non_issue_reason);
        $this->assertValidUpdateClientDocument($this->updatePayloadFor($workOrder));
    }

    /**
     * UI hardening: the select carries an unselected placeholder, so an
     * untouched form submits no reason at all. HTML only — deliberately not
     * a `disabled` attribute driven by the existing onchange handler, which
     * would make completing with "none" depend on that script running.
     */
    public function test_an_untouched_completion_form_carries_no_reason_on_the_wire(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->readyWorkOrder('ΜΗΕ-3000');

        $body = $this->completionFormBody($user, $workOrder);

        $this->assertArrayHasKey('non_issue_reason', $body, 'The control is still in the form, as the "none" path needs it.');
        $this->assertSame('', $body['non_issue_reason'], 'An untouched form must not pick a tax reason on the user\'s behalf.');
    }

    public function test_choosing_none_still_requires_and_transmits_a_reason(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->readyWorkOrder('ΜΗΕ-4000');

        $body = $this->completionFormBody($user, $workOrder);
        $body['closure_document'] = ClosureDocument::None->value;

        // Exactly what the untouched form sends once "none" is picked and no
        // reason is chosen: the server still refuses it.
        $this->actingAs($user)
            ->patch(route('workshop.work-orders.status', $workOrder), $body)
            ->assertSessionHasErrors('non_issue_reason');

        $body['non_issue_reason'] = NonIssueReason::Warranty->value;

        $this->actingAs($user)
            ->patch(route('workshop.work-orders.status', $workOrder), $body)
            ->assertRedirect();

        $payload = $this->updatePayloadFor($workOrder);

        $this->assertTrue($payload['nonIssueInvoice']);
        // ReasonNonIssueType::WarrantyCompensation — Αποζημίωση Παροχής Εγγύησης.
        $this->assertSame(3, $payload['reasonNonIssueType']);
        $this->assertNull($payload['invoiceKind']);
        $this->assertValidUpdateClientDocument($payload);
    }

    public function test_the_filament_form_cannot_carry_a_reason_alongside_a_real_document(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->readyWorkOrder('ΜΗΕ-5000');

        Livewire::actingAs($user)
            ->test(EditWorkOrder::class, ['record' => $workOrder->id])
            ->fillForm([
                'status' => WorkOrderStatus::Completed->value,
                'closure_document' => ClosureDocument::Invoice->value,
                'non_issue_reason' => NonIssueReason::SelfUse->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($workOrder->fresh()->non_issue_reason);
        $this->assertValidUpdateClientDocument($this->updatePayloadFor($workOrder));
    }

    /**
     * Decode the stored payload back through the package's own codec — which
     * re-runs UpdateClientData's ΑΑΔΕ business rules — then serialise it and
     * validate the document against the official XSD shipped with the bridge.
     *
     * @param  array<string, mixed>  $payload
     */
    private function assertValidUpdateClientDocument(array $payload): void
    {
        $document = (new UpdateClientXmlSerializer)->serialize(PayloadCodec::decodeUpdateClient($payload));

        $this->assertSame(
            [],
            (new XsdValidator)->validate($document, 'updateClient-v1.1.xsd'),
            'The enqueued payload does not satisfy the official ΑΑΔΕ schema.',
        );
    }

    /** @return array<string, mixed> */
    private function updatePayloadFor(WorkOrder $workOrder): array
    {
        return OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'update_client')
            ->orderByDesc('id')
            ->firstOrFail()
            ->payload;
    }

    /**
     * Serialises the rendered completion form the way a browser would: every
     * named, non-disabled control, using the selected option of each select.
     * `hidden` is deliberately not honoured — that is the whole point.
     *
     * @return array<string, string>
     */
    private function completionFormBody(User $user, WorkOrder $workOrder): array
    {
        $html = $this->actingAs($user)
            ->get(route('workshop.work-orders.show', $workOrder))
            ->assertOk()
            ->getContent();

        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);

        $form = $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " wos-status-form--completion ")]')->item(0);
        $this->assertNotNull($form, 'The completion form is gone from the page.');

        $body = [];

        foreach ($xpath->query('.//input[@name] | .//select[@name]', $form) as $control) {
            /** @var DOMElement $control */
            if ($control->hasAttribute('disabled')) {
                continue;
            }

            $name = $control->getAttribute('name');

            if ($control->nodeName === 'input') {
                $body[$name] = $control->getAttribute('value');

                continue;
            }

            $selected = $xpath->query('.//option[@selected]', $control)->item(0)
                ?? $xpath->query('.//option', $control)->item(0);

            $body[$name] = $selected instanceof DOMElement ? $selected->getAttribute('value') : '';
        }

        unset($body['_token'], $body['_method']);

        return $body;
    }

    private function readyWorkOrder(string $plate): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Πελάτης '.$plate]);
        $vehicle = Vehicle::create(['customer_id' => $customer->id, 'plate_number' => $plate]);

        $workOrder = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => WorkOrderStatus::ReadyForPickup,
        ]);

        OutboxEntry::query()
            ->where('local_entity_type', 'work_order')
            ->where('local_entity_id', (string) $workOrder->id)
            ->where('operation', 'send_client')
            ->update(['status' => 'sent', 'dcl_id' => 100000000830764]);

        return $workOrder->fresh();
    }
}
