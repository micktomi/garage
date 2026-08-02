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

    public function test_work_order_index_uses_fully_clickable_accessible_cards_with_real_content(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Μιχάλης Δημητρίου']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΜΕΚ-9900',
            'make' => 'Ford',
            'model' => 'Focus',
        ]);
        $problem = str_repeat('Αλλαγή λαδιών και φίλτρων ', 8);
        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => $problem,
            'status' => WorkOrderStatus::New,
        ]);

        $response = $this->actingAs($user)->get(route('workshop.work-orders.index'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $grids = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " wol-grid ")]');
        $this->assertCount(1, $grids);
        $this->assertStringContainsString('ws-panel', $grids->item(0)->getAttribute('class'));

        $cards = $xpath->query('./a[contains(concat(" ", normalize-space(@class), " "), " wol-card ")]', $grids->item(0));
        $this->assertCount(1, $cards);
        $this->assertStringContainsString('ws-panel', $cards->item(0)->getAttribute('class'));
        $this->assertStringContainsString('ws-clickable-card', $cards->item(0)->getAttribute('class'));
        $this->assertSame(route('workshop.work-orders.show', $order), $cards->item(0)->getAttribute('href'));
        $this->assertSame(
            sprintf(
                'Εντολή #%s, κατάσταση %s, πελάτης Μιχάλης Δημητρίου, όχημα Ford Focus, πινακίδα ΜΕΚ-9900, πρόβλημα %s, άνοιγμα %s',
                str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
                WorkOrderStatus::New->label(),
                trim($problem),
                $order->created_at->format('d/m/Y'),
            ),
            $cards->item(0)->getAttribute('aria-label'),
        );
        $this->assertCount(1, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge ")]', $cards->item(0)));
        $this->assertCount(1, $xpath->query('.//time[contains(concat(" ", normalize-space(@class), " "), " ws-row-time ")]', $cards->item(0)));
        $this->assertCount(1, $xpath->query('.//svg[contains(concat(" ", normalize-space(@class), " "), " wol-card-chevron ")]', $cards->item(0)));
        $this->assertCount(0, $xpath->query('//table'));
        $this->assertCount(1, $xpath->query('//h1[normalize-space()="Εργασίες"]'));
        $this->assertCount(1, $xpath->query('//h2[normalize-space()="Ανοιχτές εντολές"]'));

        $response
            ->assertSee('ΜΕΚ-9900')
            ->assertSee('Μιχάλης Δημητρίου')
            ->assertSee('Ford Focus')
            ->assertSee('Αλλαγή λαδιών και φίλτρων')
            ->assertSee(route('workshop.work-orders.create'), false);
    }

    public function test_work_order_index_keeps_the_empty_state_and_real_create_route(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workshop.work-orders.index'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $emptyStates = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-empty-state ")]');
        $this->assertCount(1, $emptyStates);
        $this->assertCount(1, $xpath->query('.//a[@href="'.route('workshop.work-orders.create').'"]', $emptyStates->item(0)));
        $response->assertSee('Δεν υπάρχουν ανοιχτές εντολές');
    }

    public function test_work_order_list_switches_from_stacked_cards_to_72px_operational_rows_at_1024px(): void
    {
        $view = file_get_contents(resource_path('views/workshop/work-orders/index.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.wol-grid\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*minmax\(0, 1fr\);[^}]*\}/s',
            $view,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1024px\)\s*\{.*?\.wol-grid\s*\{[^}]*display:\s*block;[^}]*overflow:\s*hidden;[^}]*\}.*?\.wol-card\s*\{[^}]*height:\s*72px;[^}]*min-height:\s*72px;[^}]*display:\s*grid;[^}]*\}/s',
            $view,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1024px\).*?\.wol-card-problem \.ws-field-label\s*\{[^}]*display:\s*none;[^}]*\}/s',
            $view,
        );
        $this->assertStringNotContainsString('<table', $view);
    }

    public function test_customer_index_uses_shared_operational_cards_with_real_routes_and_call_actions(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'full_name' => 'Ελένη Παπαδοπούλου-Κωνσταντοπούλου',
            'phone' => '6944556677',
            'email' => 'eleni.papadopoulou.konstantopoulou@very-long-example.test',
        ]);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΧΑΡ-5678']);
        Vehicle::create(['customer_id' => $customer->id, 'plate_number' => 'ΙΟΜ-2468-ΜΑΚΡΙΑ']);

        $response = $this->actingAs($user)->get(route('workshop.customers.index'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $grids = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " cu-list ")]');
        $this->assertCount(1, $grids);
        $this->assertStringContainsString('ws-card-grid', $grids->item(0)->getAttribute('class'));
        $this->assertStringContainsString('ws-card-grid--two-up', $grids->item(0)->getAttribute('class'));

        $cards = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " cu-card ")]', $grids->item(0));
        $this->assertCount(1, $cards);
        $this->assertSame('article', $cards->item(0)->nodeName);
        $this->assertStringContainsString('ws-panel', $cards->item(0)->getAttribute('class'));
        $this->assertStringContainsString('ws-operational-card', $cards->item(0)->getAttribute('class'));
        $this->assertCount(0, $xpath->query('ancestor::a', $cards->item(0)));
        $this->assertCount(0, $xpath->query('.//a//a', $cards->item(0)));

        $editRoute = route('filament.admin.resources.customers.edit', ['record' => $customer]);
        $overlay = $xpath->query('.//a[@href="'.$editRoute.'" and contains(concat(" ", normalize-space(@class), " "), " ws-operational-card__overlay ")]', $cards->item(0));
        $this->assertCount(1, $overlay);
        $this->assertSame('Επεξεργασία πελάτη Ελένη Παπαδοπούλου-Κωνσταντοπούλου', $overlay->item(0)->getAttribute('aria-label'));
        $this->assertCount(1, $xpath->query('.//a[@href="'.$editRoute.'" and normalize-space()="Επεξεργασία"]', $cards->item(0)));

        $callLinks = $xpath->query('.//a[starts-with(@href, "tel:")]', $cards->item(0));
        $this->assertCount(1, $callLinks);
        $this->assertSame('tel:6944556677', $callLinks->item(0)->getAttribute('href'));
        $this->assertSame('Κλήση Ελένη Παπαδοπούλου-Κωνσταντοπούλου', $callLinks->item(0)->getAttribute('aria-label'));
        $this->assertCount(1, $xpath->query(
            './/a[@href="'.route('workshop.work-orders.create', ['customer_id' => $customer->id]).'" and normalize-space()="Νέα εντολή"]',
            $cards->item(0),
        ));

        $response->assertSee('2 οχήματα')
            ->assertSee('ΧΑΡ-5678')
            ->assertSee('ΙΟΜ-2468-ΜΑΚΡΙΑ')
            ->assertSee('eleni.papadopoulou.konstantopoulou@very-long-example.test');
    }

    public function test_customer_without_a_vehicle_has_no_new_work_order_action(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Πελάτης χωρίς όχημα']);

        $response = $this->actingAs($user)->get(route('workshop.customers.index'))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $card = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " cu-card ")]')->item(0);

        $this->assertCount(2, $xpath->query(
            './/a[@href="'.route('filament.admin.resources.customers.edit', ['record' => $customer]).'"]',
            $card,
        ));
        $this->assertCount(0, $xpath->query('.//a[contains(@href, "/workshop/work-orders/create")]', $card));
        $response->assertSee('Χωρίς καταχωρημένο όχημα');
    }

    public function test_vehicle_index_uses_edit_overlay_without_blocking_existing_actions(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'full_name' => 'Ανδρέας Κωνσταντίνου-Παπαγεωργόπουλος',
            'phone' => '6944112233',
        ]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'ΚΥΖ-6789-ΜΑΚΡΙΑ',
            'make' => 'Peugeot Automobiles',
            'model' => '308 Grand Touring Edition',
            'year' => 2019,
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

        $grids = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " sr-list ")]');
        $this->assertCount(1, $grids);
        $this->assertStringContainsString('ws-card-grid', $grids->item(0)->getAttribute('class'));
        $this->assertStringContainsString('ws-card-grid--two-up', $grids->item(0)->getAttribute('class'));

        $cards = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " sr-card ")]');
        $this->assertCount(1, $cards);
        $this->assertSame('article', $cards->item(0)->nodeName);
        $this->assertStringContainsString('ws-panel', $cards->item(0)->getAttribute('class'));
        $this->assertStringContainsString('ws-operational-card', $cards->item(0)->getAttribute('class'));

        $overlay = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " sr-card-overlay ")]', $cards->item(0));
        $this->assertCount(1, $overlay);
        $this->assertSame(route('workshop.vehicles.edit', $vehicle), $overlay->item(0)->getAttribute('href'));
        $this->assertSame('Επεξεργασία οχήματος ΚΥΖ-6789-ΜΑΚΡΙΑ', $overlay->item(0)->getAttribute('aria-label'));
        $this->assertCount(1, $xpath->query('.//a[@href="'.route('workshop.vehicles.edit', $vehicle).'" and normalize-space()="Επεξεργασία"]', $cards->item(0)));

        $callLinks = $xpath->query('.//a[@href="tel:6944112233" and contains(concat(" ", normalize-space(@class), " "), " sr-card-action ")]', $cards->item(0));
        $this->assertCount(1, $callLinks);
        $this->assertSame('Κλήση Ανδρέας Κωνσταντίνου-Παπαγεωργόπουλος', $callLinks->item(0)->getAttribute('aria-label'));
        $this->assertCount(1, $xpath->query('.//a[@href="'.route('workshop.work-orders.show', $order).'"]', $cards->item(0)));

        $createLinks = $xpath->query('.//a[@href="'.route('workshop.work-orders.create', ['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id]).'"]', $cards->item(0));
        $this->assertCount(1, $createLinks);

        $response->assertSee('126.500 km')
            ->assertSee('ΚΤΕΟ')
            ->assertSee('Peugeot Automobiles')
            ->assertSee('308 Grand Touring Edition')
            ->assertSee('2019')
            ->assertSee('ΚΥΖ-6789-ΜΑΚΡΙΑ');
    }

    public function test_customer_and_vehicle_indexes_share_the_1024px_operational_card_contract(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/workshop.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.ws-card-grid\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1024px\)\s*\{\s*\.ws-card-grid--two-up\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);[^}]*\}/s',
            $layout,
        );
        $this->assertStringContainsString('.ws-operational-card__footer', $layout);
        $this->assertStringContainsString('.ws-operational-card__overlay:focus-visible', $layout);

        foreach (['customers/index.blade.php', 'search.blade.php'] as $view) {
            $contents = file_get_contents(resource_path('views/workshop/'.$view));

            $this->assertStringContainsString('ws-card-grid ws-card-grid--two-up', $contents);
            $this->assertStringContainsString('ws-panel ws-operational-card', $contents);
            $this->assertStringContainsString('ws-operational-card__footer', $contents);
        }
    }

    public function test_vehicle_index_pagination_keeps_its_query_icon_and_previous_next_contracts(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Πελάτης στόλου']);

        foreach (range(1, 25) as $index) {
            Vehicle::create([
                'customer_id' => $customer->id,
                'plate_number' => 'ΣΤΛ-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($user)->get(route('workshop.search'))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $pageLinks = $xpath->query('//a[contains(@href, "page=2")]');
        $paginator = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-pagination ")]');

        $this->assertNotSame(0, $pageLinks->length);
        $this->assertCount(1, $paginator);
        $this->assertCount(2, $xpath->query('.//svg[contains(concat(" ", normalize-space(@class), " "), " w-5 ") and contains(concat(" ", normalize-space(@class), " "), " h-5 ")]', $paginator->item(0)));
        $this->assertCount(0, $xpath->query('.//a[@rel="prev"]', $paginator->item(0)));
        $this->assertCount(1, $xpath->query('.//a[@rel="next"]', $paginator->item(0)));
        $this->assertCount(1, $xpath->query('.//*[@aria-disabled="true"]', $paginator->item(0)));

        $pageTwo = $this->xpath(
            $this->actingAs($user)->get(route('workshop.search', ['page' => 2]))->assertOk()->getContent(),
        );
        $this->assertCount(1, $pageTwo->query('//a[@rel="prev"]'));
        $this->assertCount(1, $pageTwo->query('//a[@rel="next"]'));

        $pageThree = $this->xpath(
            $this->actingAs($user)->get(route('workshop.search', ['page' => 3]))->assertOk()->getContent(),
        );
        $this->assertCount(1, $pageThree->query('//a[@rel="prev"]'));
        $this->assertCount(0, $pageThree->query('//a[@rel="next"]'));
        $this->assertCount(1, $pageThree->query('//*[@aria-disabled="true"]'));

        $layout = file_get_contents(resource_path('views/layouts/workshop.blade.php'));
        $this->assertMatchesRegularExpression(
            '/\.ws-pagination svg\.w-5\.h-5\s*\{[^}]*width:\s*18px;[^}]*height:\s*18px;[^}]*\}/s',
            $layout,
        );

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

    public function test_appointment_grid_keeps_its_existing_one_and_two_column_states(): void
    {
        $appointmentView = file_get_contents(resource_path('views/workshop/appointments/index.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.ap-list\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\);[^}]*\}/s',
            $appointmentView,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1120px\)\s*\{\s*\.ap-list\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);[^}]*\}/s',
            $appointmentView,
        );
        $this->assertDoesNotMatchRegularExpression('/\.ap-list\s*\{[^}]*repeat\(3,/s', $appointmentView);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
