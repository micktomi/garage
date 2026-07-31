<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopIndexCardGridTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_index_uses_clickable_compact_cards(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Μιχάλης Δημητρίου']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΜΕΚ-9900',
            'make' => 'Ford',
            'model' => 'Focus',
        ]);
        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => str_repeat('Αλλαγή λαδιών και φίλτρων ', 8),
            'status' => WorkOrderStatus::New,
        ]);

        $response = $this->actingAs($user)->get(route('workshop.work-orders.index'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $grids = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-work-order-grid ")]');
        $this->assertCount(1, $grids);

        $cards = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " ws-work-order-row ")]', $grids->item(0));
        $this->assertCount(1, $cards);
        $this->assertSame(route('workshop.work-orders.show', $order), $cards->item(0)->getAttribute('href'));
        $this->assertCount(1, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge ")]', $cards->item(0)));
        $this->assertCount(1, $xpath->query('.//time[contains(concat(" ", normalize-space(@class), " "), " ws-row-time ")]', $cards->item(0)));
        $this->assertCount(1, $xpath->query('.//svg[contains(concat(" ", normalize-space(@class), " "), " ws-work-order-chevron ")]', $cards->item(0)));
        $response->assertSee('ΜΕΚ-9900')->assertSee('Μιχάλης Δημητρίου')->assertSee('Ford Focus');
    }

    public function test_customer_index_uses_non_nested_compact_cards_with_real_call_actions(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'full_name' => 'Ελένη Καραμάνου',
            'phone' => '6944556677',
            'email' => 'eleni@example.test',
        ]);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΧΑΡ-5678']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΙΟΜ-2468']);

        $response = $this->actingAs($user)->get(route('workshop.customers.index'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $grids = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " cu-list ")]');
        $this->assertCount(1, $grids);

        $cards = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " cu-card ")]', $grids->item(0));
        $this->assertCount(1, $cards);
        $this->assertSame('div', $cards->item(0)->nodeName);
        $this->assertCount(0, $xpath->query('ancestor::a', $cards->item(0)));

        $callLinks = $xpath->query('.//a[starts-with(@href, "tel:")]', $cards->item(0));
        $this->assertCount(1, $callLinks);
        $this->assertSame('tel:6944556677', $callLinks->item(0)->getAttribute('href'));
        $this->assertSame('Κλήση Ελένη Καραμάνου', $callLinks->item(0)->getAttribute('aria-label'));
        $response->assertSee('2 οχήματα')->assertSee('ΧΑΡ-5678')->assertSee('ΙΟΜ-2468')->assertSee('eleni@example.test');
    }

    public function test_customer_card_grid_has_only_one_and_two_column_states(): void
    {
        $view = file_get_contents(resource_path('views/workshop/customers/index.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.cu-list\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\);[^}]*\}/s',
            $view,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1120px\)\s*\{\s*\.cu-list\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);[^}]*\}/s',
            $view,
        );
        $this->assertDoesNotMatchRegularExpression('/\.cu-list\s*\{[^}]*repeat\(3,/s', $view);
        $this->assertMatchesRegularExpression(
            '/\.cu-card\s*\{[^}]*min-width:\s*0;[^}]*height:\s*100%;[^}]*background:\s*var\(--ws-card\);[^}]*box-shadow:\s*var\(--shadow-sm\);[^}]*\}/s',
            $view,
        );
        $this->assertStringNotContainsString('text-transform: uppercase', $view);
    }

    public function test_vehicle_index_uses_edit_overlay_without_blocking_existing_actions(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'full_name' => 'Ανδρέας Κωνσταντίνου',
            'phone' => '6944112233',
        ]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΚΥΖ-6789',
            'make' => 'Peugeot',
            'model' => '308',
            'mileage' => 126500,
            'kteo_expires_at' => now()->addMonths(3),
        ]);
        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Έλεγχος συμπλέκτη',
            'status' => WorkOrderStatus::InProgress,
        ]);

        $response = $this->actingAs($user)->get(route('workshop.search'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $cards = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " sr-card ")]');
        $this->assertCount(1, $cards);
        $this->assertSame('article', $cards->item(0)->nodeName);

        $overlay = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " sr-card-overlay ")]', $cards->item(0));
        $this->assertCount(1, $overlay);
        $this->assertSame(route('workshop.vehicles.edit', $vehicle), $overlay->item(0)->getAttribute('href'));

        $callLinks = $xpath->query('.//a[@href="tel:6944112233" and contains(concat(" ", normalize-space(@class), " "), " sr-card-action ")]', $cards->item(0));
        $this->assertCount(1, $callLinks);
        $this->assertCount(1, $xpath->query('.//a[@href="'.route('workshop.work-orders.show', $order).'"]', $cards->item(0)));

        $createLinks = $xpath->query('.//a[contains(@href, "/workshop/work-orders/create")]', $cards->item(0));
        $this->assertCount(1, $createLinks);
        $this->assertStringContainsString('customer_id='.$customer->id, $createLinks->item(0)->getAttribute('href'));
        $this->assertStringContainsString('vehicle_id='.$vehicle->id, $createLinks->item(0)->getAttribute('href'));

        $response->assertSee('126.500 km')->assertSee('ΚΤΕΟ')->assertSee('Peugeot 308');
    }

    public function test_vehicle_index_is_paginated_and_keeps_the_query_string_contract(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Πελάτης στόλου']);

        foreach (range(1, 13) as $index) {
            Vehicle::create([
                'customer_id' => $customer->id,
                'plate_number' => 'ΣΤΛ-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($user)->get(route('workshop.search'))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $pageLinks = $xpath->query('//a[contains(@href, "page=2")]');

        $this->assertNotSame(0, $pageLinks->length);

        $controller = file_get_contents(app_path('Http/Controllers/WorkshopController.php'));
        $this->assertMatchesRegularExpression(
            '/if \(\$q === \'\'\).*?->paginate\(12\)\s*->withQueryString\(\);/s',
            $controller,
        );
    }

    public function test_appointment_index_uses_non_clickable_cards_with_status_and_call_action(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'full_name' => 'Μαρία Θεοδώρου',
            'phone' => '6944556677',
        ]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΠΡΤ-1357',
            'make' => 'Citroën',
            'model' => 'C3',
        ]);
        Appointment::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => now()->addDay()->setTime(10, 30),
            'description' => str_repeat('Έλεγχος και προγραμματισμένη συντήρηση ', 6),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->get(route('workshop.appointments.index'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $cards = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ap-card ")]');
        $this->assertCount(1, $cards);
        $this->assertSame('article', $cards->item(0)->nodeName);
        $this->assertCount(0, $xpath->query('ancestor::a', $cards->item(0)));
        $this->assertCount(0, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " sr-card-overlay ")]', $cards->item(0)));

        $links = $xpath->query('.//a', $cards->item(0));
        $this->assertCount(1, $links);
        $this->assertSame('tel:6944556677', $links->item(0)->getAttribute('href'));
        $this->assertCount(0, $xpath->query('.//svg', $cards->item(0)));

        $response->assertSee('Σε εξέλιξη')
            ->assertSee('Μαρία Θεοδώρου')
            ->assertSee('ΠΡΤ-1357')
            ->assertSee('Citroën C3')
            ->assertSee('Έλεγχος και προγραμματισμένη συντήρηση');
    }

    public function test_vehicle_and_appointment_grids_have_only_one_and_two_column_states(): void
    {
        $vehicleView = file_get_contents(resource_path('views/workshop/search.blade.php'));
        $appointmentView = file_get_contents(resource_path('views/workshop/appointments/index.blade.php'));

        foreach ([['sr-list', 'sr-card', $vehicleView], ['ap-list', 'ap-card', $appointmentView]] as [$list, $card, $view]) {
            $this->assertMatchesRegularExpression(
                '/\.'.preg_quote($list, '/').'\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\);[^}]*\}/s',
                $view,
            );
            $this->assertMatchesRegularExpression(
                '/@media\s*\(min-width:\s*1120px\)\s*\{\s*\.'.preg_quote($list, '/').'\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);[^}]*\}/s',
                $view,
            );
            $this->assertDoesNotMatchRegularExpression('/\.'.preg_quote($list, '/').'\s*\{[^}]*repeat\(3,/s', $view);
            $this->assertMatchesRegularExpression(
                '/\.'.preg_quote($card, '/').'\s*\{[^}]*min-width:\s*0;[^}]*height:\s*100%;[^}]*background:\s*var\(--ws-card\);[^}]*box-shadow:\s*var\(--shadow-sm\);[^}]*\}/s',
                $view,
            );
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}|rgba?\(/i', $view);
            $this->assertStringNotContainsString('text-transform: uppercase', $view);
            $this->assertDoesNotMatchRegularExpression('/letter-spacing\s*:/', $view);
        }

        $this->assertMatchesRegularExpression(
            '/\.sr-card-overlay\s*\{[^}]*z-index:\s*1;[^}]*\}/s',
            $vehicleView,
        );
        $this->assertMatchesRegularExpression(
            '/\.sr-card-action\s*\{[^}]*z-index:\s*2;[^}]*\}/s',
            $vehicleView,
        );
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
