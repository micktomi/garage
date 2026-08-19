<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_status_is_cast_to_the_domain_enum(): void
    {
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::AwaitingParts);

        $this->assertSame(WorkOrderStatus::AwaitingParts, $workOrder->status);
        $this->assertSame('Αναμονή ανταλλακτικού', $workOrder->status->label());
        $this->assertTrue($workOrder->status->isOpen());
        $this->assertFalse(WorkOrderStatus::Completed->isOpen());
    }

    public function test_status_endpoint_accepts_all_new_workflow_states(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::New);

        foreach ([
            WorkOrderStatus::InProgress,
            WorkOrderStatus::AwaitingParts,
            WorkOrderStatus::ReadyForPickup,
            WorkOrderStatus::Completed,
        ] as $status) {
            // Re-read every iteration: each accepted transition advances the
            // work order's optimistic-concurrency token.
            $payload = [
                'lock_version' => $workOrder->fresh()->lock_version,
                'status' => $status->value,
            ];
            if ($status === WorkOrderStatus::Completed) {
                $payload['closure_document'] = 'retail_receipt';
            }

            $this->actingAs($user)->patch(
                route('workshop.work-orders.status', $workOrder),
                $payload,
            )->assertRedirect(route('workshop.work-orders.show', $workOrder));

            $this->assertSame($status, $workOrder->fresh()->status);
        }
    }

    public function test_status_endpoint_rejects_completion_without_closure_document(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);

        $this->actingAs($user)->patch(
            route('workshop.work-orders.status', $workOrder),
            ['lock_version' => $workOrder->lock_version, 'status' => WorkOrderStatus::Completed->value],
        )->assertSessionHasErrors('closure_document');

        $this->assertSame(WorkOrderStatus::ReadyForPickup, $workOrder->fresh()->status);
    }

    public function test_status_endpoint_rejects_completion_with_none_and_no_reason(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);

        $this->actingAs($user)->patch(
            route('workshop.work-orders.status', $workOrder),
            ['lock_version' => $workOrder->lock_version, 'status' => WorkOrderStatus::Completed->value, 'closure_document' => 'none'],
        )->assertSessionHasErrors('non_issue_reason');

        $this->assertSame(WorkOrderStatus::ReadyForPickup, $workOrder->fresh()->status);
    }

    public function test_status_endpoint_accepts_completion_with_none_and_a_reason(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::ReadyForPickup);

        $this->actingAs($user)->patch(
            route('workshop.work-orders.status', $workOrder),
            ['lock_version' => $workOrder->lock_version, 'status' => WorkOrderStatus::Completed->value, 'closure_document' => 'none', 'non_issue_reason' => 'warranty'],
        )->assertRedirect(route('workshop.work-orders.show', $workOrder));

        $fresh = $workOrder->fresh();
        $this->assertSame(WorkOrderStatus::Completed, $fresh->status);
        $this->assertSame(\App\Enums\ClosureDocument::None, $fresh->closure_document);
        $this->assertSame(\App\Enums\NonIssueReason::Warranty, $fresh->non_issue_reason);
    }

    public function test_status_endpoint_rejects_unknown_state(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::New);

        $this->actingAs($user)->patch(
            route('workshop.work-orders.status', $workOrder),
            ['lock_version' => $workOrder->lock_version, 'status' => 'waiting_for_moon_alignment'],
        )->assertSessionHasErrors('status');

        $this->assertSame(WorkOrderStatus::New, $workOrder->fresh()->status);
    }

    public function test_open_work_order_page_shows_every_canonical_open_status_and_excludes_closed_states(): void
    {
        $user = User::factory()->create();

        $openOrders = [];
        $closedOrders = [];

        foreach (WorkOrderStatus::cases() as $index => $status) {
            $workOrder = $this->makeWorkOrder($status, sprintf('LST-%04d', $index + 1));

            if ($status->isOpen()) {
                $openOrders[] = $workOrder;
            } else {
                $closedOrders[] = $workOrder;
            }
        }

        $response = $this->actingAs($user)
            ->get(route('workshop.work-orders.index'))
            ->assertOk();
        $xpath = $this->xpath($response->getContent());

        $this->assertCount(count($openOrders), $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " wol-card ")]'));

        foreach ($openOrders as $workOrder) {
            $response
                ->assertSee($workOrder->vehicle->plate_number)
                ->assertSee($workOrder->status->label());
        }

        foreach ($closedOrders as $workOrder) {
            $response
                ->assertDontSee($workOrder->vehicle->plate_number)
                ->assertDontSee(route('workshop.work-orders.show', $workOrder), false);
        }
    }

    public function test_existing_work_order_detail_page_opens_for_every_supported_status(): void
    {
        $user = User::factory()->create();

        foreach (WorkOrderStatus::cases() as $index => $status) {
            $workOrder = $this->makeWorkOrder($status, sprintf('STA-%04d', $index + 1));

            $response = $this->actingAs($user)
                ->get(route('workshop.work-orders.show', $workOrder));

            $response
                ->assertOk()
                ->assertSee($status->label())
                ->assertSee(route('work-orders.print', $workOrder), false)
                ->assertSee(route('workshop.work-orders.status', $workOrder), false)
                ->assertDontSee('wo-badge--blocking')
                ->assertDontSee('wos-status-btn--blocking');

            $xpath = $this->xpath($response->getContent());
            $statusControls = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wos-status-btn ")]');
            $this->assertCount(6, $statusControls, "Expected all six status controls for [{$status->value}].");
            $this->assertCount(1, $xpath->query('//*[@aria-current="true" and contains(concat(" ", normalize-space(@class), " "), " wos-status-btn ")]'));
            $this->assertCount(5, $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " wos-status-form ")]//input[@name="status"]'));

            foreach (WorkOrderStatus::cases() as $option) {
                $this->assertSame(
                    1,
                    $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wos-status-btn ") and normalize-space()="'.$option->label().'"]')->length,
                    "Missing unique status control [{$option->value}].",
                );
            }

            // The donor header carries a single status tag; the status is not
            // repeated in a summary block below it.
            $this->assertSame(
                1,
                substr_count($response->getContent(), '<span class="ws-status-badge '),
                "Expected exactly the header badge for status [{$status->value}].",
            );
        }
    }

    public function test_work_order_detail_preserves_service_fields_part_sources_notes_and_prices(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->makeWorkOrder(WorkOrderStatus::InProgress, 'DET-4400');
        $workOrder->customer->update(['phone' => '6912345678']);
        $workOrder->update([
            'diagnosis' => 'Φθορά στα αναλώσιμα του service.',
            'work_performed' => 'Αλλαγή φίλτρων και τελικός έλεγχος.',
            'current_mileage' => 84500,
            'next_service_date' => '2027-02-10',
            'next_service_mileage' => 94500,
            'labor_cost' => 50,
        ]);

        $stockPart = Part::create([
            'code' => 'DET-001',
            'name' => 'Φίλτρο λαδιού',
            'quantity' => 10,
            'purchase_price' => 6,
            'sale_price' => 12,
        ]);

        WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'part_id' => $stockPart->id,
            'source' => 'from_stock',
            'description' => 'Φίλτρο από το ράφι',
            'quantity' => 1.5,
            'unit_cost' => 6,
            'unit_price' => 12,
            'line_total' => 18,
            'note' => 'Χρησιμοποιήθηκε από υπάρχον απόθεμα.',
        ]);
        WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'source' => 'customer_supplied',
            'description' => 'Λάδι πελάτη',
            'quantity' => 1,
            'unit_cost' => 0,
            'unit_price' => 25,
            'line_total' => 25,
            'note' => 'Παραδόθηκε σφραγισμένο.',
        ]);
        WorkOrderPart::create([
            'work_order_id' => $workOrder->id,
            'source' => 'purchased_for_job',
            'description' => 'Καθαριστικό συστήματος',
            'quantity' => 3,
            'unit_cost' => 3,
            'unit_price' => 5,
            'line_total' => 15,
            'note' => 'Αγορά για τη συγκεκριμένη εντολή.',
        ]);

        $response = $this->actingAs($user)
            ->get(route('workshop.work-orders.show', $workOrder))
            ->assertOk();
        $xpath = $this->xpath($response->getContent());

        $response
            ->assertSee('Φθορά στα αναλώσιμα του service.')
            ->assertSee('Αλλαγή φίλτρων και τελικός έλεγχος.')
            ->assertSee('84.500 km')
            ->assertSee('10 Φεβρουαρίου 2027')
            ->assertSee('94.500 km')
            ->assertSee('Από απόθεμα')
            ->assertSee('Έφερε ο πελάτης')
            ->assertSee('Αγοράστηκε για τη δουλειά')
            ->assertSee('Φίλτρο από το ράφι')
            ->assertSee('Χρησιμοποιήθηκε από υπάρχον απόθεμα.')
            ->assertSee('Παραδόθηκε σφραγισμένο.')
            ->assertSee('Αγορά για τη συγκεκριμένη εντολή.')
            ->assertSee('1,5')
            ->assertSee('6,00 €')
            ->assertSee('12,00 €')
            ->assertSee('18,00 €')
            ->assertSee('58,00 €')
            ->assertSee('108,00 €')
            ->assertDontSee('ΦΠΑ')
            ->assertDontSee('Έκπτωση')
            ->assertDontSee('Αναμενόμενη παράδοση')
            ->assertDontSee('Προσθήκη ανταλλακτικού')
            ->assertDontSee('Επεξεργασία')
            ->assertDontSee('Διαγραφή');

        $this->assertCount(1, $xpath->query('//a[@href="tel:6912345678"]'));
        $this->assertCount(1, $xpath->query('//a[@href="'.route('work-orders.print', $workOrder).'"]'));
        $this->assertCount(1, $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " wos-parts-table ")]'));
        $this->assertCount(6, $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " wos-parts-table ")]//th[@scope="col"]'));
        $this->assertCount(3, $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " wos-parts-table ")]/tbody/tr'));
        $this->assertCount(18, $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " wos-parts-table ")]//span[contains(concat(" ", normalize-space(@class), " "), " wos-part-cell-label ")]'));

        foreach (['Ανταλλακτικό', 'Πηγή', 'Ποσότητα', 'Κόστος', 'Τιμή', 'Σύνολο'] as $mobileLabel) {
            $this->assertCount(
                3,
                $xpath->query('//span[contains(concat(" ", normalize-space(@class), " "), " wos-part-cell-label ") and normalize-space()="'.$mobileLabel.'"]'),
                "Expected one real mobile label [{$mobileLabel}] per part.",
            );
        }

        foreach ([
            'from_stock' => 'Από απόθεμα',
            'customer_supplied' => 'Έφερε ο πελάτης',
            'purchased_for_job' => 'Αγοράστηκε για τη δουλειά',
        ] as $source => $label) {
            $nodes = $xpath->query('//span[contains(concat(" ", normalize-space(@class), " "), " wos-part-source--'.$source.' ")]');
            $this->assertCount(1, $nodes);
            $this->assertSame($label, trim($nodes->item(0)->textContent));
        }

        // Mobile labels are real DOM elements (asserted above via the
        // wos-part-cell-label spans), not the old CSS-generated-content
        // technique — checked directly on the rendered table's own td
        // elements, rather than banning the raw strings anywhere in the
        // whole view file.
        $this->assertCount(
            0,
            $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " wos-parts-table ")]//td[@data-label]'),
        );

        $view = file_get_contents(resource_path('views/workshop/work-orders/show.blade.php'));
        $this->assertDoesNotMatchRegularExpression('/\.wos-parts-table\s+td::before/', $view);
    }

    public function test_blocking_reason_flag_and_route_are_removed(): void
    {
        $this->assertFalse(Schema::hasColumn('work_orders', 'blocking_reason'));
        $this->assertFalse(Route::has('workshop.work-orders.blocking-reason'));
    }

    public function test_parts_switch_from_stacked_cards_to_table_only_at_1024px(): void
    {
        $view = file_get_contents(resource_path('views/workshop/work-orders/show.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.wos-parts-table,\s*\.wos-parts-table tbody,\s*\.wos-parts-table tr,\s*\.wos-parts-table td\s*\{[^}]*display:\s*block;[^}]*\}/s',
            $view,
        );
        $this->assertMatchesRegularExpression(
            '/\.wos-parts-table thead\s*\{[^}]*display:\s*none;[^}]*\}/s',
            $view,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1024px\)\s*\{.*?\.wos-parts-table\s*\{[^}]*display:\s*table;[^}]*\}.*?\.wos-parts-table thead\s*\{[^}]*display:\s*table-header-group;[^}]*\}.*?\.wos-parts-table td\s*\{[^}]*display:\s*table-cell;[^}]*\}/s',
            $view,
        );
        // The old 768px breakpoint must not still be wrapping this table —
        // scoped to .wos-parts-table specifically, not a whole-file ban on
        // the breakpoint value (other, unrelated elements in this view
        // legitimately use their own breakpoints, e.g. 600px/1180px above).
        $this->assertDoesNotMatchRegularExpression(
            '/@media\s*\(min-width:\s*768px\)\s*\{[^@]*\.wos-parts-table\b/s',
            $view,
        );
    }

    private function makeWorkOrder(WorkOrderStatus $status, string $plate = 'ΑΑΑ-0001'): WorkOrder
    {
        $customer = Customer::create(['full_name' => 'Test Customer '.$plate]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

        return WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Test service',
            'labor_cost' => 0,
            'parts_cost' => 0,
            'total_cost' => 0,
            'status' => $status,
        ]);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
