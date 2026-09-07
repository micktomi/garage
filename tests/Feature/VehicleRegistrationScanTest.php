<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleRegistrationScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_extract_or_save_the_scan_flow(): void
    {
        $this->get(route('workshop.registration-scan.show'))->assertRedirect('/admin/login');
        $this->post(route('workshop.registration-scan.extract'))->assertRedirect('/admin/login');
        $this->post(route('workshop.registration-scan.store'))->assertRedirect('/admin/login');
    }

    public function test_the_scan_page_exposes_a_mobile_camera_file_input(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('workshop.registration-scan.show'));

        $response->assertOk()
            ->assertSee('name="registration_image"', false)
            ->assertSee('accept="image/jpeg,image/png,image/webp"', false)
            ->assertSee('capture="environment"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('const maxDimension = 3072', false)
            ->assertSee("canvas.toBlob(resolve, 'image/jpeg', 0.9)", false)
            ->assertSee('όλα τα τμήματα στο κάδρο')
            ->assertSee("imageOrientation: 'from-image'", false)
            ->assertSee('new DataTransfer()', false)
            ->assertSee('name="browser_preprocess_ms"', false);
    }

    public function test_extract_rejects_non_image_uploads_without_storing_them(): void
    {
        Storage::fake('local');

        $response = $this->actingAs(User::factory()->create())
            ->from(route('workshop.registration-scan.show'))
            ->post(route('workshop.registration-scan.extract'), [
                'registration_image' => UploadedFile::fake()->create('adeia.txt', 10, 'text/plain'),
            ]);

        $response->assertSessionHasErrors('registration_image');
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_valid_upload_opens_the_human_review_form_with_extracted_data(): void
    {
        config([
            'garage-assistant.enabled' => true,
            'garage-assistant.gemini.api_key' => 'test-api-key',
            'garage-assistant.gemini.model' => 'gemini-3.7-flash',
        ]);
        Storage::fake('local');
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [['text' => json_encode([
                    'owner_full_name' => 'Μαρία Ιωάννου',
                    'afm' => null,
                    'phone' => null,
                    'address' => null,
                    'plate_number' => 'ΙΥΖ-5020',
                    'vin' => 'VF3LRYHZPJS396912',
                    'make' => 'Volkswagen',
                    'model' => 'Golf',
                    'fuel' => 'Βενζίνη',
                    'engine_cc' => 1390,
                    'first_registered_at' => '2018-06-15',
                ], JSON_UNESCAPED_UNICODE)]]],
            ]],
        ])]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.extract'), [
                'registration_image' => UploadedFile::fake()->image('adeia.jpg', 1200, 800),
            ]);

        $response->assertOk()
            ->assertSee('Απαραίτητος ανθρώπινος έλεγχος')
            ->assertSee('value="Μαρία Ιωάννου"', false)
            ->assertSee('value="ΙΥΖ-5020"', false)
            ->assertSee('value="VF3LRYHZPJS396912"', false)
            ->assertSee('action="'.route('workshop.registration-scan.store').'"', false);
        $response->assertHeader('Server-Timing');
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_uncertain_extracted_vin_does_not_discard_the_other_review_fields(): void
    {
        config([
            'garage-assistant.enabled' => true,
            'garage-assistant.gemini.api_key' => 'test-api-key',
            'garage-assistant.gemini.model' => 'gemini-3.7-flash',
        ]);
        Storage::fake('local');
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [['text' => json_encode([
                    'owner_full_name' => 'Μαρία Ιωάννου',
                    'afm' => null,
                    'phone' => null,
                    'address' => null,
                    'plate_number' => 'ΙΥΖ-5020',
                    'vin' => 'VF3LRYHZPIS396912',
                    'make' => 'Volkswagen',
                    'model' => 'Golf',
                    'fuel' => 'Βενζίνη',
                    'engine_cc' => 1390,
                    'first_registered_at' => '2018-06-15',
                ], JSON_UNESCAPED_UNICODE)]]],
            ]],
        ])]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.extract'), [
                'registration_image' => UploadedFile::fake()->image('adeia.jpg', 1200, 800),
            ]);

        $response->assertOk()
            ->assertSee('value="Μαρία Ιωάννου"', false)
            ->assertSee('value="ΙΥΖ-5020"', false)
            ->assertSee('name="vin" maxlength="50" value=""', false)
            ->assertDontSee('VF3LRYHZPIS396912');
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_reviewed_data_creates_customer_and_vehicle_without_touching_aade(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.store'), $this->reviewPayload());

        $vehicle = Vehicle::sole();
        $customer = Customer::sole();

        $response->assertRedirect(route('workshop.vehicles.edit', $vehicle));
        $this->assertSame('Μαρία Ιωάννου', $customer->full_name);
        $this->assertSame('123456789', $customer->afm);
        $this->assertSame('IYZ5020', $vehicle->plate_number);
        $this->assertSame('WVWZZZ1JZXW000001', $vehicle->vin);
        $this->assertSame('Βενζίνη', $vehicle->fuel);
        $this->assertSame(1390, $vehicle->engine_cc);
        $this->assertSame(2018, $vehicle->year);
        $this->assertSame('2018-06-15', $vehicle->first_registered_at?->format('Y-m-d'));
        $this->assertTrue($vehicle->customer->is($customer));
    }

    public function test_duplicate_plate_is_rejected_before_a_second_customer_is_created(): void
    {
        $existingCustomer = Customer::create(['full_name' => 'Υπάρχων Πελάτης']);
        Vehicle::create([
            'customer_id' => $existingCustomer->id,
            'plate_number' => 'IYZ-5020',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.store'), [
                ...$this->reviewPayload(),
                'plate_number' => 'ιυζ 5020',
                'afm' => '987654321',
            ]);

        $response->assertRedirect(route('workshop.registration-scan.show'))
            ->assertSessionHasErrors('plate_number');
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_duplicate_vin_is_rejected(): void
    {
        $existingCustomer = Customer::create(['full_name' => 'Υπάρχων Πελάτης']);
        Vehicle::create([
            'customer_id' => $existingCustomer->id,
            'plate_number' => 'ΖΗΘ-5678',
            'vin' => 'WVWZZZ1JZXW000001',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.store'), [
                ...$this->reviewPayload(),
                'plate_number' => 'ΝΕΑ-9999',
                'vin' => 'wvw zzz1jzxw000001',
            ]);

        $response->assertSessionHasErrors('vin');
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_existing_customer_is_reused_when_the_reviewed_afm_matches(): void
    {
        $customer = Customer::create([
            'full_name' => 'Μαρία Ιωάννου',
            'afm' => '123456789',
            'phone' => '6900000000',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.store'), $this->reviewPayload())
            ->assertRedirect();

        $this->assertDatabaseCount('customers', 1);
        $this->assertTrue(Vehicle::sole()->customer->is($customer));
        $this->assertSame('6900000000', $customer->fresh()->phone);
    }

    public function test_all_real_plate_variants_are_persisted_as_the_same_ascii_canonical_value(): void
    {
        $user = User::factory()->create();

        foreach (['ΙΥΖ-5020', 'IYZ-5020', 'IYZ 5020'] as $plate) {
            $response = $this->actingAs($user)
                ->post(route('workshop.registration-scan.store'), [
                    ...$this->reviewPayload(),
                    'plate_number' => $plate,
                    'afm' => null,
                ]);

            $vehicle = Vehicle::sole();
            $customer = Customer::sole();

            $response->assertRedirect(route('workshop.vehicles.edit', $vehicle));
            $this->assertSame('IYZ5020', $vehicle->plate_number);

            $vehicle->delete();
            $customer->delete();
        }
    }

    public function test_vin_is_only_uppercased_and_dehyphenated_before_lookup_and_persistence(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.store'), [
                ...$this->reviewPayload(),
                'vin' => 'vf3l-ryhz pjs396912',
            ])
            ->assertRedirect();

        $this->assertSame('VF3LRYHZPJS396912', Vehicle::sole()->vin);
    }

    public function test_vin_reaches_review_request_action_and_vehicle_without_letter_mutation(): void
    {
        config([
            'garage-assistant.enabled' => true,
            'garage-assistant.gemini.api_key' => 'test-api-key',
            'garage-assistant.gemini.model' => 'gemini-3.7-flash',
        ]);
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [['text' => json_encode([
                    'owner_full_name' => 'Μαρία Ιωάννου',
                    'afm' => null,
                    'phone' => null,
                    'address' => null,
                    'plate_number' => 'ΙΥΖ-5020',
                    'vin' => 'VF3LRYHZPJS396912',
                    'make' => null,
                    'model' => null,
                    'fuel' => null,
                    'engine_cc' => null,
                    'first_registered_at' => null,
                ], JSON_UNESCAPED_UNICODE)]]],
            ]],
        ])]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('workshop.registration-scan.extract'), [
                'registration_image' => UploadedFile::fake()->image('adeia.jpg'),
            ])
            ->assertOk()
            ->assertSee('value="VF3LRYHZPJS396912"', false);

        $this->actingAs($user)
            ->post(route('workshop.registration-scan.store'), [
                ...$this->reviewPayload(),
                'vin' => 'VF3LRYHZPJS396912',
            ])
            ->assertRedirect();

        $this->assertSame('VF3LRYHZPJS396912', Vehicle::sole()->vin);
    }

    public function test_invalid_or_uncertain_vins_are_rejected_before_any_data_is_created(): void
    {
        $user = User::factory()->create();

        foreach ([
            'VF3LRYHZPJS39691',
            'VF3LRYHZPIS396912',
            'VF3LRYHZPQS396912',
            'VF3LRYHZPOS396912',
        ] as $vin) {
            $this->actingAs($user)
                ->post(route('workshop.registration-scan.store'), [
                    ...$this->reviewPayload(),
                    'vin' => $vin,
                ])
                ->assertSessionHasErrors('vin');
        }

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_near_vin_requires_explicit_review_but_is_not_automatically_merged(): void
    {
        $existingCustomer = Customer::create(['full_name' => 'Υπάρχων Πελάτης']);
        Vehicle::create([
            'customer_id' => $existingCustomer->id,
            'plate_number' => 'AAA1000',
            'vin' => 'VF3LRYHZPJS396912',
        ]);
        $user = User::factory()->create();
        $payload = [
            ...$this->reviewPayload(),
            'plate_number' => 'BBB2000',
            'vin' => 'VF3LBYHZPJS396912',
        ];

        $this->actingAs($user)
            ->post(route('workshop.registration-scan.store'), $payload)
            ->assertRedirect(route('workshop.registration-scan.show'))
            ->assertSessionHasErrors('near_vin');

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('vehicles', 1);

        $this->get(route('workshop.registration-scan.show'))
            ->assertOk()
            ->assertSee('name="confirm_near_vin"', false)
            ->assertSee('Έλεγξα ξανά το πεδίο (E)');

        $this->actingAs($user)
            ->post(route('workshop.registration-scan.store'), [
                ...$payload,
                'confirm_near_vin' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('vehicles', 2);
        $this->assertSame('VF3LBYHZPJS396912', Vehicle::latest('id')->firstOrFail()->vin);
    }

    public function test_second_real_near_vin_example_also_requires_review(): void
    {
        $existingCustomer = Customer::create(['full_name' => 'Υπάρχων Πελάτης']);
        Vehicle::create([
            'customer_id' => $existingCustomer->id,
            'plate_number' => 'AAA1000',
            'vin' => 'VF3LBYHZPJS396912',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.store'), [
                ...$this->reviewPayload(),
                'plate_number' => 'BBB2000',
                'vin' => 'VF3LBYHZRJS396912',
            ])
            ->assertSessionHasErrors('near_vin');

        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_canonical_plate_match_with_near_vin_is_blocked_as_probable_same_vehicle(): void
    {
        $existingCustomer = Customer::create(['full_name' => 'Υπάρχων Πελάτης']);
        Vehicle::create([
            'customer_id' => $existingCustomer->id,
            'plate_number' => 'IYZ5020',
            'vin' => 'VF3LRYHZPJS396912',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('workshop.registration-scan.store'), [
                ...$this->reviewPayload(),
                'plate_number' => 'ΙΥΖ-5020',
                'vin' => 'VF3LBYHZPJS396912',
            ])
            ->assertSessionHasErrors(['plate_number', 'near_vin']);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('vehicles', 1);
    }

    private function reviewPayload(): array
    {
        return [
            'review_ready' => '1',
            'owner_full_name' => 'Μαρία Ιωάννου',
            'afm' => '123456789',
            'phone' => '6912345678',
            'address' => 'Αθήνα',
            'plate_number' => 'ιυζ 5020',
            'vin' => 'wvw zzz1jzxw000001',
            'make' => 'Volkswagen',
            'model' => 'Golf',
            'fuel' => 'Βενζίνη',
            'engine_cc' => '1390',
            'first_registered_at' => '2018-06-15',
        ];
    }
}
