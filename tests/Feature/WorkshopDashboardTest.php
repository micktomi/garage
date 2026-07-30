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
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorkshopDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-30 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_renders_the_required_summary_and_primary_action(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΡΦΘ-9977');

        WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Αναμονή για κιτ ιμάντα χρονισμού',
            'status' => WorkOrderStatus::AwaitingParts,
        ]);
        Appointment::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'appointment_date' => now()->setTime(11, 0),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)->get(route('workshop.dashboard'));

        $response->assertOk()
            ->assertSee('Πέμπτη, 30 Ιουλίου 2026')
            ->assertSee('Στο συνεργείο')
            ->assertSee('Ανοιχτές εντολές')
            ->assertSee('Ραντεβού σήμερα')
            ->assertSee('ΚΤΕΟ έληξαν')
            ->assertSee('Αναμονή ανταλλακτικών')
            ->assertSee('Νέα εντολή')
            ->assertSee('Αναμονή ανταλλακτικού');
    }

    public function test_metric_labels_do_not_repeat_section_headers(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $metricLabels = [];
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-stat-label ")]') as $label) {
            $metricLabels[] = trim($label->textContent);
        }

        $sectionLabels = [];
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-section-header ")]/h2') as $label) {
            $sectionLabels[] = trim($label->textContent);
        }

        $this->assertContains('Στο συνεργείο', $metricLabels);
        $this->assertNotContains('Ανοιχτές εντολές', $metricLabels);
        $this->assertSame([], array_values(array_intersect($metricLabels, $sectionLabels)));
    }

    public function test_new_and_in_progress_orders_use_distinct_badge_presentations(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΜΙΚ-1100');

        foreach ([WorkOrderStatus::New, WorkOrderStatus::InProgress] as $status) {
            WorkOrder::create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'problem_description' => 'Έλεγχος οπτικής διάκρισης κατάστασης',
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $this->assertCount(1, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge--new ")]'));
        $this->assertCount(1, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge--in-progress ")]'));
    }

    public function test_dashboard_has_one_search_input_and_one_badge_per_work_order_row(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΠΑΤ-8866');

        foreach ([WorkOrderStatus::New, WorkOrderStatus::AwaitingParts, WorkOrderStatus::ReadyForPickup] as $status) {
            WorkOrder::create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'problem_description' => str_repeat('Μακρά περιγραφή εργασίας ', 10),
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $this->assertCount(1, $xpath->query('//input[@type="search"]'));

        $rows = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-work-order-row ")]');
        $this->assertCount(3, $rows);

        foreach ($rows as $row) {
            $badges = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge ")]', $row);
            $this->assertCount(1, $badges);
        }
    }

    public function test_kteo_actions_are_links_with_customer_specific_accessible_names(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΥΒΛ-4455', 'Μιχάλης Δημητρίου', '6944445555');
        $vehicle->update(['kteo_expires_at' => now()->subDays(4)]);

        $this->actingAs($user)->get(route('workshop.dashboard'))
            ->assertOk()
            ->assertSee('Έληξε 26/07')
            ->assertSee('href="tel:6944445555"', false)
            ->assertSee('href="sms:6944445555"', false)
            ->assertSee('aria-label="Κλήση Μιχάλης Δημητρίου"', false)
            ->assertSee('aria-label="SMS προς Μιχάλης Δημητρίου"', false);
    }

    public function test_dashboard_empty_states_are_invitations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('workshop.dashboard'))
            ->assertOk()
            ->assertSee('Δεν υπάρχουν ανοιχτές εντολές')
            ->assertSee('Άνοιγμα νέας εντολής')
            ->assertSee('Δεν υπάρχουν λήξεις ΚΤΕΟ εντός 30 ημερών')
            ->assertSee('Καταχώρηση οχήματος')
            ->assertDontSee('Δεν βρέθηκαν εγγραφές');
    }

    public function test_phone_user_agents_redirect_to_filament_but_tablets_remain_supported(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)')
            ->get(route('workshop.dashboard'))
            ->assertRedirect('/admin');

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X)')
            ->get(route('workshop.dashboard'))
            ->assertOk();
    }

    public function test_dashboard_composition_and_components_contain_no_hex_colors_or_php_blocks(): void
    {
        $viewFiles = [
            resource_path('views/workshop/index.blade.php'),
            ...glob(resource_path('views/components/workshop/*.blade.php')),
        ];

        foreach ($viewFiles as $file) {
            $contents = file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}/i', $contents, $file);
            $this->assertStringNotContainsString('@php', $contents, $file);
        }
    }

    public function test_dashboard_v11_accent_palette_and_truncation_contract(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/workshop.blade.php'));

        $this->assertStringContainsString('--ws-status-new-bg: #F1F1EF;', $layout);
        $this->assertStringContainsString('--ws-status-new-fg: #54534F;', $layout);
        $this->assertStringNotContainsString('--ws-status-new-bg: #EFF3F8;', $layout);
        $this->assertSame(2, preg_match_all('/var\(--ws-primary\)/', $layout));

        $this->assertMatchesRegularExpression(
            '/\.ws-page-date\s*\{[^}]*color:\s*var\(--ws-text-muted\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-dnav-item\.ws-active\s*\{[^}]*color:\s*var\(--ws-text-muted\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-row-body\s*\{[^}]*flex:\s*1;[^}]*min-width:\s*0;[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-row-title,\s*\.ws-row-meta,\s*\.ws-kteo-deadline\s*\{[^}]*overflow:\s*hidden;[^}]*text-overflow:\s*ellipsis;[^}]*white-space:\s*nowrap;[^}]*\}/s',
            $layout,
        );
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }

    /**
     * @return array{Customer, Vehicle}
     */
    private function makeCustomerAndVehicle(
        string $plate,
        string $name = 'Αντώνης Χριστοδούλου',
        string $phone = '6900000000',
    ): array {
        $customer = Customer::create(['full_name' => $name, 'phone' => $phone]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => $plate,
            'make' => 'Mercedes-Benz',
            'model' => 'C-Class',
        ]);

        return [$customer, $vehicle];
    }
}
