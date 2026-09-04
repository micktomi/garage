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
            ->assertSee('Σύστημα ενεργό')
            ->assertSee('Γρήγορες Ενέργειες')
            ->assertSee('Αναμονή ανταλλακτικού');
    }

    public function test_quick_actions_use_existing_workshop_routes(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $actions = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-quick-action ")]');
        $this->assertCount(3, $actions);

        foreach ([
            route('workshop.customers.create'),
            route('workshop.vehicles.create'),
            route('workshop.work-orders.create'),
        ] as $href) {
            $this->assertCount(1, $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " ws-quick-action ") and @href="'.$href.'"]'));
        }
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

        // "Στο συνεργείο" is the strip's own heading; repeating it as a metric
        // told the reader the same number twice.
        $this->assertSame([
            'Ραντεβού σήμερα',
            'ΚΤΕΟ έληξαν',
            'Αναμονή ανταλλακτικών',
        ], $metricLabels);
        $this->assertNotContains('Στο συνεργείο', $metricLabels);
        $this->assertNotContains('Ανοιχτές εντολές', $metricLabels);
        $this->assertSame([], array_values(array_intersect($metricLabels, $sectionLabels)));
    }

    public function test_shop_strip_and_open_list_answer_different_questions(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΕΞΩ-1234');

        $onSite = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Το όχημα είναι στον ανυψωτήρα',
            'status' => WorkOrderStatus::InProgress,
        ]);
        $awayForParts = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Ο πελάτης πήρε το αυτοκίνητο μέχρι να έρθει το ανταλλακτικό',
            'status' => WorkOrderStatus::AwaitingParts,
        ]);
        $awayForParts->update(['in_shop' => false]);

        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        // The strip counts vehicles, not orders, and holds only what is here.
        $response->assertSee('Στο συνεργείο')->assertSee('· 1 όχημα');

        $cards = $xpath->query('//section[@aria-label="Οχήματα στο συνεργείο"]//a[contains(concat(" ", normalize-space(@class), " "), " ws-bay ")]');
        $this->assertCount(1, $cards);
        $this->assertSame(route('workshop.work-orders.show', $onSite), $cards->item(0)->getAttribute('href'));

        // Both orders are still open, so both stay in the list below.
        $rows = $xpath->query('//section[@aria-label="Πρόσφατες ανοιχτές εντολές"]//a[contains(concat(" ", normalize-space(@class), " "), " ws-recent-order ")]');
        $this->assertCount(2, $rows);

        $flags = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-shop-flag ")]');
        $this->assertCount(1, $flags);
        $this->assertSame('Εκτός συνεργείου', trim($flags->item(0)->textContent));
    }

    public function test_closing_an_order_checks_the_vehicle_out_of_the_shop(): void
    {
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΚΛΕ-9090');

        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Σέρβις',
            'status' => WorkOrderStatus::InProgress,
        ]);

        $this->assertTrue($order->in_shop);
        $this->assertNotNull($order->checked_in_at);
        $this->assertNull($order->checked_out_at);

        $order->update(['status' => WorkOrderStatus::Completed]);

        $this->assertFalse($order->fresh()->in_shop);
        $this->assertNotNull($order->fresh()->checked_out_at);

        // An order created as already closed never counts as present.
        $imported = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Παλιά ολοκληρωμένη εντολή',
            'status' => WorkOrderStatus::Completed,
        ]);

        $this->assertFalse($imported->in_shop);
        $this->assertSame(0, WorkOrder::inShop()->count());
    }

    public function test_kteo_panel_splits_lapsed_from_upcoming_and_counts_the_days(): void
    {
        $user = User::factory()->create();

        foreach ([
            ['ΠΑΛ-0001', 'Παναγιώτης Λάμπρου', -80],
            ['ΠΑΛ-0002', 'Ελένη Σταύρου', -12],
            ['ΝΕΟ-0003', 'Μαρία Κώστα', 14],
        ] as [$plate, $name, $offset]) {
            [, $vehicle] = $this->makeCustomerAndVehicle($plate, $name);
            $vehicle->update(['kteo_expires_at' => now()->addDays($offset)]);
        }

        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $content = $response->getContent();
        $xpath = $this->xpath($content);

        $headings = [];
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-kteo-subhead ")]') as $heading) {
            $headings[] = trim(preg_replace('/\s+/u', ' ', $heading->textContent));
        }

        $this->assertSame(['Έληξαν · 2', 'Λήγουν σύντομα · 1'], $headings);

        // The panel title no longer promises a window the list does not keep.
        $this->assertStringNotContainsString('ΚΤΕΟ εντός 30 ημερών', $content);

        // Day counts turn a date into a debt; lapsed runs oldest first.
        $deadlines = [];
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-kteo-deadline ")]') as $deadline) {
            $deadlines[] = trim($deadline->textContent);
        }

        $this->assertSame([
            'Έληξε '.now()->subDays(80)->format('d/m').' · 80 ημ',
            'Έληξε '.now()->subDays(12)->format('d/m').' · 12 ημ',
            'Λήγει '.now()->addDays(14)->format('d/m').' · 14 ημ',
        ], $deadlines);
    }

    public function test_shop_strip_has_no_placeholder_card_and_equal_height_cards(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΓΕΜ-7777');

        // More open orders than the strip shows: the overflow lives behind the
        // "Όλες οι εντολές" link, not in a dashed placeholder tile.
        for ($i = 0; $i < 10; $i++) {
            WorkOrder::create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'problem_description' => str_repeat('Πολύ μακρά περιγραφή εργασίας ', 4),
                'status' => WorkOrderStatus::InProgress,
            ]);
        }

        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $cards = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " ws-bay ")]');
        $this->assertCount(8, $cards);
        $this->assertCount(0, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-bay-more ")]'));

        // Four equal stage segments on every card, never a percentage width.
        foreach ($cards as $card) {
            $this->assertCount(4, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-bay-rail-step ")]', $card));
        }

        $layout = file_get_contents(resource_path('css/workshop.css'));

        $this->assertMatchesRegularExpression(
            '/\.ws-bay-rail\s*\{[^}]*grid-template-columns:\s*repeat\(4, 1fr\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-bay-grid\s*\{[^}]*justify-content:\s*start;[^}]*align-items:\s*stretch;[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-bay\s*\{[^}]*height:\s*100%;[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-bay-foot\s*\{[^}]*margin-top:\s*auto;[^}]*\}/s',
            $layout,
        );
        // One clamped line, so a two-line job never lifts the card's footer.
        $this->assertMatchesRegularExpression(
            '/\.ws-bay-job\s*\{[^}]*-webkit-line-clamp:\s*1;[^}]*\}/s',
            $layout,
        );
        $this->assertStringNotContainsString('.ws-bay-more', $layout);
    }

    public function test_titles_are_only_attached_to_text_that_actually_truncates(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΤΙΤ-4321', 'Άννα Δή');
        $vehicle->update(['kteo_expires_at' => now()->subDay()]);

        WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => 'Λάδια',
            'status' => WorkOrderStatus::New,
        ]);

        $short = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $this->assertStringNotContainsString('title="Άννα Δή"', $short->getContent());

        $longName = 'Κωνσταντίνος Παπαδόπουλος-Γεωργιάδης';
        $customer->update(['full_name' => $longName]);

        $long = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $this->assertStringContainsString('title="'.$longName.'"', $long->getContent());
    }

    public function test_only_the_expired_kteo_numeric_value_uses_the_danger_tone(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΚΤΕ-2200');
        $vehicle->update(['kteo_expires_at' => now()->subDay()]);

        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $kteoCards = $xpath->query(
            '//*[contains(concat(" ", normalize-space(@class), " "), " ws-stat-card ")]'
            .'[.//*[contains(concat(" ", normalize-space(@class), " "), " ws-stat-label ")'
            .' and normalize-space(.)="ΚΤΕΟ έληξαν"]]',
        );

        $this->assertCount(1, $kteoCards);
        $this->assertCount(1, $xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), " ws-stat-value--danger ")]',
            $kteoCards->item(0),
        ));
        $this->assertCount(1, $xpath->query('//*[@class and contains(concat(" ", normalize-space(@class), " "), " ws-stat-value--danger ")]'));
        $this->assertStringNotContainsString('danger', $kteoCards->item(0)->getAttribute('class'));
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

        // The workshop strip and the open-order list are two views of the same
        // orders, so the badge count is asserted per surface, not per page.
        foreach ([
            '//section[@aria-label="Πρόσφατες ανοιχτές εντολές"]',
            '//section[@aria-label="Οχήματα στο συνεργείο"]',
        ] as $surface) {
            $this->assertCount(1, $xpath->query($surface.'//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge--new ")]'), $surface);
            $this->assertCount(1, $xpath->query($surface.'//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge--in-progress ")]'), $surface);
        }
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

        $rows = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ws-recent-order ")]');
        $this->assertCount(3, $rows);

        foreach ($rows as $row) {
            $badges = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge ")]', $row);
            $this->assertCount(1, $badges);
        }
    }

    public function test_open_work_orders_render_as_clickable_responsive_dashboard_rows(): void
    {
        $user = User::factory()->create();
        [$customer, $vehicle] = $this->makeCustomerAndVehicle('ΚΑΡ-2048');
        $order = WorkOrder::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'problem_description' => str_repeat('Μακρά περιγραφή εργασίας ', 8),
            'status' => WorkOrderStatus::New,
        ]);

        $response = $this->actingAs($user)->get(route('workshop.dashboard'))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $sections = $xpath->query('//section[@aria-label="Πρόσφατες ανοιχτές εντολές"]');
        $this->assertCount(1, $sections);

        $panels = $xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), " ws-panel ")]',
            $sections->item(0),
        );
        $this->assertCount(1, $panels);

        $rows = $xpath->query(
            './/a[contains(concat(" ", normalize-space(@class), " "), " ws-recent-order ")]',
            $panels->item(0),
        );
        $this->assertCount(1, $rows);
        $this->assertSame(route('workshop.work-orders.show', $order), $rows->item(0)->getAttribute('href'));
        $this->assertCount(1, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-recent-order-number ")]', $rows->item(0)));
        $this->assertCount(1, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-recent-order-customer ")]', $rows->item(0)));
        $this->assertCount(1, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-recent-order-meta ")]', $rows->item(0)));
        $this->assertCount(1, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ws-status-badge ")]', $rows->item(0)));
        $this->assertCount(1, $xpath->query('.//time[contains(concat(" ", normalize-space(@class), " "), " ws-recent-order-time ")]', $rows->item(0)));
        $this->assertCount(1, $xpath->query('.//svg[contains(concat(" ", normalize-space(@class), " "), " ws-recent-order-chevron ")]', $rows->item(0)));
    }

    public function test_open_work_order_grid_has_only_one_and_two_column_states(): void
    {
        $layout = file_get_contents(resource_path('css/workshop.css'));

        $this->assertMatchesRegularExpression(
            '/\.ws-work-order-grid\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1120px\)\s*\{\s*\.ws-work-order-grid\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);[^}]*\}/s',
            $layout,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.ws-work-order-grid\s*\{[^}]*grid-template-columns:\s*repeat\(3,/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-work-order-grid \.ws-work-order-row\s*\{[^}]*min-width:\s*0;[^}]*height:\s*100%;[^}]*background:\s*var\(--ws-card\);[^}]*box-shadow:\s*var\(--shadow-sm\);[^}]*\}/s',
            $layout,
        );
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

    public function test_phone_and_tablet_user_agents_are_served_the_workshop(): void
    {
        $user = User::factory()->create();

        $agents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)',
            'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X)',
            'Mozilla/5.0 (Linux; Android 14) Mobile Safari/537.36',
        ];

        foreach ($agents as $agent) {
            $this->actingAs($user)
                ->withHeader('User-Agent', $agent)
                ->get(route('workshop.dashboard'))
                ->assertOk();
        }
    }

    public function test_layout_ships_no_client_side_mobile_guard_in_any_environment(): void
    {
        $user = User::factory()->create();

        foreach (['local', 'production'] as $environment) {
            $this->app->detectEnvironment(static fn (): string => $environment);

            $response = $this->actingAs($user)
                ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)')
                ->get(route('workshop.dashboard'))
                ->assertOk();

            // No redirect script and no minimum page width forcing a desktop layout.
            $response->assertDontSee('window.location.replace', false);
            $response->assertDontSee('min-width: 768px;', false);
            $response->assertSee('ws-mobile-bottom-nav', false);
        }
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

    public function test_dashboard_next_port_palette_typography_and_truncation_contract(): void
    {
        $layout = file_get_contents(resource_path('css/workshop.css'));

        $this->assertStringContainsString('--ws-page: #F6F5F2;', $layout);
        $this->assertStringContainsString('--ws-nav: #1B1F27;', $layout);
        $this->assertStringContainsString('--ws-nav-active: #232935;', $layout);
        $this->assertStringContainsString('--ws-primary: #0F6E56;', $layout);
        $this->assertStringContainsString('--ws-signal: #CF2221;', $layout);
        $this->assertStringContainsString('--ws-plate-band: #0B3AA8;', $layout);
        $this->assertStringContainsString('--ws-font-sans:', $layout);
        $this->assertStringContainsString('--ws-font-display:', $layout);
        $this->assertStringContainsString('--ws-font-mono:', $layout);
        $this->assertStringNotContainsString('fonts.googleapis.com', $layout);

        $this->assertMatchesRegularExpression(
            '/\.ws-dashboard-title\s*\{[^}]*font-family:\s*var\(--ws-font-display\);[^}]*font-size:\s*30px;[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-sidebar-item\[aria-current="page"\]\s*\{[^}]*color:\s*var\(--ws-primary-fg\);[^}]*background:\s*var\(--ws-nav-active\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-dashboard \.ws-stat-card\s*\{[^}]*border:\s*1px solid var\(--ws-border\);[^}]*border-radius:\s*14px;[^}]*background:\s*var\(--ws-card\);[^}]*box-shadow:\s*var\(--shadow-sm\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-recent-order-body\s*\{[^}]*min-width:\s*0;[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-recent-order-customer,\s*\.ws-recent-order-meta\s*\{[^}]*overflow:\s*hidden;[^}]*text-overflow:\s*ellipsis;[^}]*white-space:\s*nowrap;[^}]*\}/s',
            $layout,
        );
    }

    public function test_dashboard_responsive_layout_has_mobile_tablet_and_desktop_states(): void
    {
        $layout = file_get_contents(resource_path('css/workshop.css'));

        // Three metrics at every width: two columns would orphan the third.
        $this->assertMatchesRegularExpression(
            '/\.ws-dashboard \.ws-stat-grid\s*\{[^}]*grid-template-columns:\s*repeat\(3, minmax\(0, 1fr\)\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1024px\).*?\.ws-dashboard \.ws-stat-grid\s*\{[^}]*repeat\(3, minmax\(0, 1fr\)\)/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/\.ws-dashboard-body\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1200px\).*?\.ws-dashboard-body\s*\{[^}]*grid-template-columns:\s*minmax\(0, 2fr\) minmax\(280px, 1fr\);[^}]*\}/s',
            $layout,
        );
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*767px\).*?\.ws-dashboard \.ws-dashboard-controls\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\);[^}]*\}/s',
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
