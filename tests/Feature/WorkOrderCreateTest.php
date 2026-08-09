<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Part;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderPart;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderCreateTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomerAndVehicle(string $plate = 'ΑΒΓ-1234'): array
    {
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
            'make' => 'Toyota',
            'model' => 'Yaris',
        ]);

        return [$customer, $vehicle];
    }

    public function test_create_form_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workshop.work-orders.create'));

        $response->assertOk();
        $response->assertSee('Νέα εντολή εργασίας');
    }

    public function test_create_form_uses_one_card_with_internal_sections_and_sticky_actions(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('workshop.work-orders.create'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $forms = $xpath->query('//form[@action="'.route('workshop.work-orders.store').'"]');
        $this->assertCount(1, $forms);
        $form = $forms->item(0);

        $cards = $xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), " woc-card ")]',
            $form,
        );
        $this->assertCount(1, $cards);

        $sections = $xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), " woc-form-section ")]',
            $cards->item(0),
        );
        $this->assertCount(5, $sections);

        $sectionTitles = [];
        foreach ($xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), " woc-form-section-title ")]',
            $cards->item(0),
        ) as $title) {
            $sectionTitles[] = trim($title->textContent);
        }

        $this->assertSame([
            'Πελάτης και όχημα',
            'Τι δηλώνει ο πελάτης',
            'Κόστος και ανταλλακτικά',
            'Στοιχεία service',
            'Σύνοψη κόστους',
        ], $sectionTitles);

        $actionBars = $xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), " woc-action-bar ")]',
            $cards->item(0),
        );
        $this->assertCount(1, $actionBars);
        $this->assertCount(1, $xpath->query('.//button[@type="submit" and normalize-space(.)="Αποθήκευση"]', $actionBars->item(0)));
        $this->assertCount(1, $xpath->query('.//a[normalize-space(.)="Ακύρωση"]', $actionBars->item(0)));
    }

    public function test_create_form_starts_with_collapsed_parts_and_uses_responsive_autogrow_workflow_textareas(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('workshop.work-orders.create'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $textareas = $xpath->query('//textarea[@rows="2" and @data-autogrow]');
        $this->assertCount(3, $textareas);

        foreach ([
            'problem_description' => 'Πρόβλημα ή εργασία',
            'diagnosis' => 'Διάγνωση συνεργείου',
            'work_performed' => 'Εργασίες που πραγματοποιήθηκαν',
        ] as $field => $label) {
            $this->assertCount(1, $xpath->query('//textarea[@id="'.$field.'" and @name="'.$field.'" and @rows="2" and @data-autogrow]'));
            $this->assertCount(1, $xpath->query('//label[@for="'.$field.'" and contains(normalize-space(), "'.$label.'")]'));
            $this->assertCount(1, $xpath->query('//textarea[@id="'.$field.'"]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " woc-card-body--grid ")]'));
        }

        $problem = $xpath->query('//textarea[@id="problem_description"]')->item(0);
        $this->assertSame('Τριγμός από εμπρός δεξιά κατά την οδήγηση', $problem->getAttribute('placeholder'));

        $response->assertSee("x-data='workOrderForm([],", false);
        $response->assertSee('Προσθήκη ανταλλακτικού');

        $view = file_get_contents(resource_path('views/workshop/work-orders/create.blade.php'));
        $this->assertStringContainsString('const maxRows = 8;', $view);

        // Section titles and part-row numbers use normal-case typography
        // (not the small-caps/tracked-out style the redesign replaced) —
        // asserted as the specific weight/color each one actually carries,
        // scoped to its own declaration block so unrelated selectors
        // elsewhere in the file (e.g. uppercase field labels) can't affect it.
        $this->assertMatchesRegularExpression(
            '/\.woc-form-section-title\s*\{[^}]*font-weight:\s*700;[^}]*\}/s',
            $view,
        );
        preg_match('/\.woc-form-section-title\s*\{([^}]*)\}/s', $view, $sectionTitleBlock);
        $this->assertStringNotContainsString('text-transform', $sectionTitleBlock[1] ?? '');
        $this->assertStringNotContainsString('letter-spacing', $sectionTitleBlock[1] ?? '');

        $this->assertMatchesRegularExpression(
            '/\.woc-part-row-num\s*\{[^}]*font-weight:\s*500;[^}]*color:\s*var\(--ws-text-muted\);[^}]*\}/s',
            $view,
        );
        preg_match('/\.woc-part-row-num\s*\{([^}]*)\}/s', $view, $partRowNumBlock);
        $this->assertStringNotContainsString('text-transform', $partRowNumBlock[1] ?? '');
        $this->assertStringNotContainsString('letter-spacing', $partRowNumBlock[1] ?? '');
    }

    public function test_create_form_uses_muted_destructive_actions_and_sticky_action_bar(): void
    {
        $view = file_get_contents(resource_path('views/workshop/work-orders/create.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.woc-remove-btn\s*\{[^}]*color:\s*var\(--ws-text-muted\);[^}]*background:\s*transparent;[^}]*border:\s*1px solid transparent;[^}]*\}/s',
            $view,
        );
        $this->assertMatchesRegularExpression(
            '/\.woc-action-bar\s*\{[^}]*position:\s*sticky;[^}]*bottom:\s*0;[^}]*background:\s*var\(--ws-sunken\);[^}]*\}/s',
            $view,
        );
    }

    public function test_work_order_can_be_created_with_vehicle_belonging_to_customer(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle();

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Αλλαγή λαδιών',
            'labor_cost' => 40,
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertEquals($customer->id, $workOrder->customer_id);
        $this->assertEquals($vehicle->id, $workOrder->vehicle_id);
    }

    public function test_work_order_stores_diagnosis_and_work_performed_and_renders_them_on_detail(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΔΙΑ-5000');

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Θόρυβος κατά την πέδηση.',
            'diagnosis' => 'Φθαρμένα εμπρός τακάκια.',
            'work_performed' => 'Αντικατάσταση εμπρός τακακίων και δοκιμή δρόμου.',
        ]);

        $workOrder = WorkOrder::firstOrFail();

        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertSame('Φθαρμένα εμπρός τακάκια.', $workOrder->diagnosis);
        $this->assertSame('Αντικατάσταση εμπρός τακακίων και δοκιμή δρόμου.', $workOrder->work_performed);

        $this->actingAs($user)
            ->get(route('workshop.work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Φθαρμένα εμπρός τακάκια.')
            ->assertSee('Αντικατάσταση εμπρός τακακίων και δοκιμή δρόμου.');
    }

    public function test_diagnosis_and_work_performed_are_nullable(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΚΕΝ-5000');

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Προγραμματισμένος έλεγχος.',
        ])->assertRedirect();

        $workOrder = WorkOrder::firstOrFail();
        $this->assertNull($workOrder->diagnosis);
        $this->assertNull($workOrder->work_performed);
    }

    public function test_workflow_notes_enforce_max_length_restore_old_input_and_show_inline_errors(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΜΑΧ-5001');
        $tooLongDiagnosis = str_repeat('δ', 5001);
        $tooLongWork = str_repeat('ε', 5001);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος ορίου κειμένου.',
            'diagnosis' => $tooLongDiagnosis,
            'work_performed' => $tooLongWork,
        ])->assertSessionHasErrors(['diagnosis', 'work_performed']);

        $response = $this->actingAs($user)
            ->get(route('workshop.work-orders.create'))
            ->assertOk();
        $xpath = $this->xpath($response->getContent());

        foreach (['diagnosis' => $tooLongDiagnosis, 'work_performed' => $tooLongWork] as $field => $oldValue) {
            $textarea = $xpath->query('//textarea[@id="'.$field.'"]')->item(0);
            $this->assertNotNull($textarea);
            $this->assertSame($oldValue, $textarea->textContent);
            $this->assertStringContainsString('is-invalid', $textarea->getAttribute('class'));
            $this->assertCount(1, $xpath->query('./following-sibling::span[contains(concat(" ", normalize-space(@class), " "), " woc-error ")]', $textarea));
        }

        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_work_order_rejects_vehicle_belonging_to_different_customer(): void
    {
        $user = User::factory()->create();
        $customerA = Customer::create(['full_name' => 'Πελάτης Α']);
        $customerB = Customer::create(['full_name' => 'Πελάτης Β']);

        $vehicleOfB = Vehicle::create([
            'customer_id' => $customerB->id,
            'plate_number' => 'ΔΕΖ-5678',
            'make' => 'Honda',
            'model' => 'Civic',
        ]);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleOfB->id,
            'problem_description' => 'Έλεγχος φρένων',
            'labor_cost' => 30,
        ]);

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_work_order_can_be_created_with_single_stock_part(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΖΗΘ-9012');
        $part = Part::create([
            'code' => 'P100',
            'name' => 'Φίλτρο λαδιού',
            'quantity' => 5,
            'sale_price' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'labor_cost' => 50,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 2,
                    'unit_price' => 10,
                ],
            ],
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertEquals(20, $workOrder->parts_cost);
        $this->assertEquals(70, $workOrder->total_cost);
    }

    public function test_work_order_can_be_created_with_multiple_part_rows_of_different_sources(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΗΘΙ-3456');
        $part = Part::create([
            'code' => 'P200',
            'name' => 'Φίλτρο αέρος',
            'quantity' => 5,
            'purchase_price' => 6,
            'sale_price' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Γενικό service',
            'labor_cost' => 50,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 2,
                    'unit_price' => 10,
                ],
                [
                    'source' => 'customer_supplied',
                    'description' => 'Μπαταρία πελάτη',
                    'quantity' => 1,
                    'unit_price' => 25,
                ],
                [
                    'source' => 'purchased_for_job',
                    'description' => 'Λάδι κινητήρα 5W-40',
                    'quantity' => 3,
                    'unit_cost' => 3,
                    'unit_price' => 5,
                ],
            ],
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $response->assertRedirect(route('workshop.work-orders.show', $workOrder));
        $this->assertEquals(3, WorkOrderPart::count());
        $this->assertEquals(60, $workOrder->parts_cost); // 2*10 + 1*25 + 3*5
        $this->assertEquals(110, $workOrder->total_cost); // 50 + 60

        $customerSuppliedRow = WorkOrderPart::where('source', 'customer_supplied')->first();
        $this->assertEquals(0, $customerSuppliedRow->unit_cost);
        $this->assertNull($customerSuppliedRow->part_id);

        $purchasedRow = WorkOrderPart::where('source', 'purchased_for_job')->first();
        $this->assertEquals(15, $purchasedRow->line_total);
        $this->assertNull($purchasedRow->part_id);
    }

    public function test_stock_quantity_is_decremented_only_for_from_stock_parts(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΘΙΚ-7890');
        $part = Part::create([
            'code' => 'P300',
            'name' => 'Μπουζί',
            'quantity' => 10,
            'sale_price' => 8,
        ]);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Αλλαγή μπουζί',
            'labor_cost' => 20,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 3,
                    'unit_price' => 8,
                ],
                [
                    'source' => 'customer_supplied',
                    'description' => 'Καλώδιο πελάτη',
                    'quantity' => 1,
                    'unit_price' => 5,
                ],
            ],
        ]);

        $this->assertEquals(7, $part->fresh()->quantity);
    }

    public function test_quantity_accepts_half_unit_steps(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΙΚΛ-1122');
        $part = Part::create([
            'code' => 'P400',
            'name' => 'Λάδι χύμα (λίτρο)',
            'quantity' => 10,
            'sale_price' => 6,
        ]);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Συμπλήρωση λαδιού',
            'labor_cost' => 0,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 1.5,
                    'unit_price' => 6,
                ],
            ],
        ]);

        $workOrderPart = WorkOrderPart::first();

        $this->assertEquals(1.5, (float) $workOrderPart->quantity);
        $this->assertEquals(9, $workOrderPart->line_total); // 1.5 * 6
        $this->assertEquals(8.5, (float) $part->fresh()->quantity); // 10 - 1.5
    }

    public function test_work_order_stores_service_tracking_fields(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΚΛΜ-3344');

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Γενικό service',
            'labor_cost' => 60,
            'current_mileage' => 85000,
            'next_service_date' => '2026-12-01',
            'next_service_mileage' => 95000,
        ]);

        $workOrder = WorkOrder::first();

        $this->assertNotNull($workOrder);
        $this->assertEquals(85000, $workOrder->current_mileage);
        $this->assertEquals('2026-12-01', $workOrder->next_service_date->format('Y-m-d'));
        $this->assertEquals(95000, $workOrder->next_service_mileage);
    }

    public function test_create_form_exposes_vehicle_mileage_for_autofill(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Γιώργος Παπαδόπουλος']);
        Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΟΠΡ-7788',
            'make' => 'Toyota',
            'model' => 'Yaris',
            'mileage' => 73500,
        ]);

        $response = $this->actingAs($user)->get(route('workshop.work-orders.create'));

        $response->assertOk();
        // The vehicle-select JS blob must carry each vehicle's mileage so
        // it can auto-fill the "current mileage" field on selection.
        $response->assertSee('"mileage":73500', false);
    }

    public function test_work_order_raises_vehicle_mileage_when_higher(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΣΤΥ-4455');
        $vehicle->update(['mileage' => 80000]);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'current_mileage' => 85000,
        ]);

        $this->assertEquals(85000, $vehicle->fresh()->mileage);
    }

    public function test_work_order_does_not_lower_vehicle_mileage(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΦΧΨ-6677');
        $vehicle->update(['mileage' => 90000]);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'current_mileage' => 50000, // older/lower reading — must not overwrite
        ]);

        $this->assertEquals(90000, $vehicle->fresh()->mileage);
    }

    public function test_work_order_sets_vehicle_mileage_when_previously_unknown(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΩΑΒ-8899');

        $this->assertNull($vehicle->mileage);

        $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'current_mileage' => 60000,
        ]);

        $this->assertEquals(60000, $vehicle->fresh()->mileage);
    }

    public function test_invalid_part_row_prevents_work_order_creation(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΝΞΟ-5566');
        $part = Part::create([
            'code' => 'P500',
            'name' => 'Φίλτρο καμπίνας',
            'quantity' => 5,
            'sale_price' => 12,
        ]);

        $response = $this->actingAs($user)->post(route('workshop.work-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Service',
            'labor_cost' => 40,
            'parts' => [
                [
                    'source' => 'from_stock',
                    'part_id' => $part->id,
                    'quantity' => 2,
                    'unit_price' => 12,
                ],
                [
                    // Missing part_id for a from_stock row — invalid.
                    'source' => 'from_stock',
                    'quantity' => 1,
                    'unit_price' => 12,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('parts.1.part_id');
        $this->assertDatabaseCount('work_orders', 0);
        $this->assertDatabaseCount('work_order_parts', 0);
        $this->assertEquals(5, $part->fresh()->quantity);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
