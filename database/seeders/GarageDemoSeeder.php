<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GarageDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Guard against execution in non-local environments
        if (!app()->environment('local')) {
            $this->command->error('The GarageDemoSeeder can only run in local environment!');
            return;
        }

        $this->command->info('=== Starting GarageDemoSeeder ===');

        // 1. Create 8 Customers
        $customersData = [
            [
                'full_name' => 'Γιάννης Παπαδόπουλος',
                'phone' => '6912345678',
                'email' => 'yiannis.pap@gmail.com',
                'address' => 'Ακαδημίας 45, Αθήνα',
                'notes' => 'Παλιός πελάτης, προτιμά τηλεφωνική επικοινωνία',
            ],
            [
                'full_name' => 'Μαρία Οικονόμου',
                'phone' => '6923456789',
                'email' => 'm.oikonomou@live.com',
                'address' => 'Κηφισίας 120, Μαρούσι',
                'notes' => 'Εταιρικό όχημα',
            ],
            [
                'full_name' => 'Δημήτρης Γεωργίου',
                'phone' => '6934567890',
                'email' => 'd.georgiou@yahoo.gr',
                'address' => 'Βασ. Όλγας 32, Θεσσαλονίκη',
                'notes' => null,
            ],
            [
                'full_name' => 'Ελένη Καρρά',
                'phone' => '6945678901',
                'email' => 'eleni.karra@outlook.com',
                'address' => 'Πανεπιστημίου 15, Αθήνα',
                'notes' => null,
            ],
            [
                'full_name' => 'Νίκος Νικολάου',
                'phone' => '6956789012',
                'email' => 'n.nikolaou@gmail.com',
                'address' => 'Μεσογείων 200, Χολαργός',
                'notes' => null,
            ],
            [
                'full_name' => 'Σοφία Βασιλείου',
                'phone' => '6978901234',
                'email' => 's.vasileiou@gmail.com',
                'address' => 'Τσιμισκή 54, Θεσσαλονίκη',
                'notes' => 'Προσοχή στις λεπτομέρειες',
            ],
            [
                'full_name' => 'Κώστας Κωνσταντινίδης',
                'phone' => '6989012345',
                'email' => 'k.konstant@gmail.com',
                'address' => 'Λεωφ. Ηρακλείου 88, Νέα Ιωνία',
                'notes' => null,
            ],
            [
                'full_name' => 'Άννα Δημητριάδη',
                'phone' => '6990123456',
                'email' => 'a.dimitriadi@yahoo.com',
                'address' => 'Πατησίων 150, Αθήνα',
                'notes' => null,
            ],
        ];

        $customers = [];
        foreach ($customersData as $data) {
            $customers[] = Customer::firstOrCreate(['phone' => $data['phone']], $data);
        }
        $this->command->info('Created ' . count($customers) . ' customers.');

        // 2. Create 10 Vehicles
        $vehiclesData = [
            [
                'customer_index' => 0,
                'plate_number' => 'ΧΝΕ-4321',
                'make' => 'Toyota',
                'model' => 'Yaris',
                'year' => 2015,
                'mileage' => 120000,
                'vin' => 'JTDKK483758394832',
                'kteo_expires_at' => Carbon::today()->addDays(5)->toDateString(), // Close KTEO reminder 1 (Warning)
                'notes' => 'Χρειάζεται σύντομα service',
            ],
            [
                'customer_index' => 0,
                'plate_number' => 'ΥΖΜ-9876',
                'make' => 'Volkswagen',
                'model' => 'Golf',
                'year' => 2018,
                'mileage' => 85000,
                'vin' => 'WVWZZZ1KZEW194857',
                'kteo_expires_at' => Carbon::today()->addMonths(10)->toDateString(),
                'notes' => null,
            ],
            [
                'customer_index' => 1,
                'plate_number' => 'ΙΚΑ-3321',
                'make' => 'Opel',
                'model' => 'Astra',
                'year' => 2017,
                'mileage' => 98000,
                'vin' => 'W0L0AHL35H8473849',
                'kteo_expires_at' => Carbon::today()->subDays(2)->toDateString(), // Close KTEO reminder 2 (Expired, Danger)
                'notes' => null,
            ],
            [
                'customer_index' => 1,
                'plate_number' => 'ΕΧΒ-7744',
                'make' => 'Ford',
                'model' => 'Focus',
                'year' => 2019,
                'mileage' => 54000,
                'vin' => 'WF0EXXGBBE1293847',
                'kteo_expires_at' => Carbon::today()->addMonths(18)->toDateString(),
                'notes' => null,
            ],
            [
                'customer_index' => 2,
                'plate_number' => 'ΑΗΖ-5522',
                'make' => 'Nissan',
                'model' => 'Qashqai',
                'year' => 2020,
                'mileage' => 38000,
                'vin' => 'SJNFAA15U02938472',
                'kteo_expires_at' => Carbon::today()->addMonths(14)->toDateString(),
                'notes' => null,
            ],
            [
                'customer_index' => 3,
                'plate_number' => 'ΒΚΤ-9911',
                'make' => 'Hyundai',
                'model' => 'i20',
                'year' => 2016,
                'mileage' => 110000,
                'vin' => 'MALAA51CA12938475',
                'kteo_expires_at' => Carbon::today()->addMonths(6)->toDateString(),
                'notes' => null,
            ],
            [
                'customer_index' => 4,
                'plate_number' => 'ΖΟΡ-8833',
                'make' => 'Renault',
                'model' => 'Clio',
                'year' => 2014,
                'mileage' => 125000,
                'vin' => 'VF15RFL0A12938472',
                'kteo_expires_at' => Carbon::today()->addMonths(12)->toDateString(),
                'notes' => null,
            ],
            [
                'customer_index' => 5,
                'plate_number' => 'ΚΡΤ-2266',
                'make' => 'Peugeot',
                'model' => '208',
                'year' => 2015,
                'mileage' => 145000,
                'vin' => 'VF3CCHNZT12938471',
                'kteo_expires_at' => Carbon::today()->addMonths(8)->toDateString(),
                'notes' => null,
            ],
            [
                'customer_index' => 6,
                'plate_number' => 'ΜΤΥ-1100',
                'make' => 'Fiat',
                'model' => 'Panda',
                'year' => 2012,
                'mileage' => 160000,
                'vin' => 'ZFA31200001293847',
                'kteo_expires_at' => Carbon::today()->addMonths(2)->toDateString(),
                'notes' => null,
            ],
            [
                'customer_index' => 7,
                'plate_number' => 'ΠΡΑ-6699',
                'make' => 'Skoda',
                'model' => 'Octavia',
                'year' => 2018,
                'mileage' => 87000,
                'vin' => 'TMBJH7NE1J1293847',
                'kteo_expires_at' => Carbon::today()->addMonths(22)->toDateString(),
                'notes' => null,
            ],
        ];

        $vehicles = [];
        foreach ($vehiclesData as $data) {
            $customerIndex = $data['customer_index'];
            unset($data['customer_index']);
            $data['customer_id'] = $customers[$customerIndex]->id;

            $vehicles[] = Vehicle::firstOrCreate(['plate_number' => $data['plate_number']], $data);
        }
        $this->command->info('Created ' . count($vehicles) . ' vehicles.');

        // 3. Create 5 appointments scheduled for today
        $appointmentsData = [
            [
                'customer_index' => 0,
                'vehicle_index' => 1,
                'appointment_date' => Carbon::today()->setTime(9, 0)->toDateTimeString(),
                'description' => 'Ετήσιο service και αλλαγή φίλτρων',
                'status' => 'scheduled',
            ],
            [
                'customer_index' => 1,
                'vehicle_index' => 2,
                'appointment_date' => Carbon::today()->setTime(10, 30)->toDateTimeString(),
                'description' => 'Έλεγχος για θόρυβο στα φρένα',
                'status' => 'scheduled',
            ],
            [
                'customer_index' => 4,
                'vehicle_index' => 6,
                'appointment_date' => Carbon::today()->setTime(12, 0)->toDateTimeString(),
                'description' => 'Δεν λειτουργεί το A/C',
                'status' => 'in_progress',
            ],
            [
                'customer_index' => 5,
                'vehicle_index' => 7,
                'appointment_date' => Carbon::today()->setTime(14, 0)->toDateTimeString(),
                'description' => 'Έλεγχος KTEO και προετοιμασία',
                'status' => 'scheduled',
            ],
            [
                'customer_index' => 7,
                'vehicle_index' => 9,
                'appointment_date' => Carbon::today()->setTime(16, 30)->toDateTimeString(),
                'description' => 'Αλλαγή μπαταρίας',
                'status' => 'scheduled',
            ],
        ];

        $appointmentsCreated = 0;
        foreach ($appointmentsData as $data) {
            $customerIndex = $data['customer_index'];
            $vehicleIndex = $data['vehicle_index'];
            unset($data['customer_index'], $data['vehicle_index']);

            $data['customer_id'] = $customers[$customerIndex]->id;
            $data['vehicle_id'] = $vehicles[$vehicleIndex]->id;

            $exists = Appointment::where('customer_id', $data['customer_id'])
                ->where('vehicle_id', $data['vehicle_id'])
                ->where('appointment_date', $data['appointment_date'])
                ->exists();

            if (!$exists) {
                Appointment::create($data);
                $appointmentsCreated++;
            }
        }
        $this->command->info("Created {$appointmentsCreated} appointments for today.");

        // 4. Create 4 open work orders ('new' / 'in_progress')
        $openWorkOrdersData = [
            [
                'vehicle_index' => 0,
                'problem_description' => 'Check engine αναμμένο, ρετάρισμα στο ρελαντί',
                'status' => 'new',
                'current_mileage' => 120150,
            ],
            [
                'vehicle_index' => 3,
                'problem_description' => 'Τριγμός από την πίσω ανάρτηση σε λακκούβες',
                'status' => 'new',
                'current_mileage' => 54200,
            ],
            [
                'vehicle_index' => 4,
                'problem_description' => 'Αλλαγή τακάκια εμπρός και πίσω',
                'status' => 'in_progress',
                'current_mileage' => 38100,
            ],
            [
                'vehicle_index' => 5,
                'problem_description' => 'Μυρωδιά καυσίμου στην καμπίνα',
                'status' => 'in_progress',
                'current_mileage' => 110200,
            ],
        ];

        $openWorkOrdersCreated = 0;
        foreach ($openWorkOrdersData as $data) {
            $vehicleIndex = $data['vehicle_index'];
            unset($data['vehicle_index']);

            $vehicle = $vehicles[$vehicleIndex];
            $data['vehicle_id'] = $vehicle->id;
            $data['customer_id'] = $vehicle->customer_id;

            // Make sure we do not duplicate if running multiple times
            $exists = WorkOrder::where('vehicle_id', $vehicle->id)
                ->where('status', $data['status'])
                ->where('problem_description', $data['problem_description'])
                ->exists();

            if (!$exists) {
                WorkOrder::create($data);
                $openWorkOrdersCreated++;
            }
        }
        $this->command->info("Created {$openWorkOrdersCreated} open work orders.");

        // 5. Create 3 completed work orders with associated parts
        $completedWorkOrdersData = [
            [
                'vehicle_index' => 6,
                'problem_description' => 'Αλλαγή λαδιών και φίλτρου λαδιού',
                'diagnosis' => 'Τυπικό service 125.000 χλμ.',
                'work_performed' => 'Αντικατάσταση λαδιών κινητήρα 5W40 και φίλτρου λαδιού',
                'status' => 'completed',
                'current_mileage' => 125100,
                'labor_cost' => 50.00,
                'parts_cost' => 68.00,
                'total_cost' => 118.00,
                'next_service_date' => Carbon::today()->addYear()->toDateString(),
                'next_service_mileage' => 140000,
                'parts' => [
                    ['code' => 'OIL-5W40', 'quantity' => 4, 'unit_price' => 14.50],
                    ['code' => 'FLT-OIL-01', 'quantity' => 1, 'unit_price' => 10.00],
                ],
            ],
            [
                'vehicle_index' => 7,
                'problem_description' => 'Θόρυβος στα φρένα κατά το φρενάρισμα',
                'diagnosis' => 'Φθαρμένα τακάκια εμπρός τροχών',
                'work_performed' => 'Αντικατάσταση τακάκια εμπρός',
                'status' => 'completed',
                'current_mileage' => 145200,
                'labor_cost' => 60.00,
                'parts_cost' => 55.00,
                'total_cost' => 115.00,
                'parts' => [
                    ['code' => 'BRK-PAD-FR', 'quantity' => 1, 'unit_price' => 55.00],
                ],
            ],
            [
                'vehicle_index' => 9,
                'problem_description' => 'Δυσκολία στην εκκίνηση το πρωί',
                'diagnosis' => 'Παλιά μπαταρία, χαμηλή τάση',
                'work_performed' => 'Αντικατάσταση μπαταρίας 60Ah',
                'status' => 'completed',
                'current_mileage' => 87400,
                'labor_cost' => 40.00,
                'parts_cost' => 95.00,
                'total_cost' => 135.00,
                'parts' => [
                    ['code' => 'BAT-60AH', 'quantity' => 1, 'unit_price' => 95.00],
                ],
            ],
        ];

        $completedWorkOrdersCreated = 0;
        foreach ($completedWorkOrdersData as $data) {
            $vehicleIndex = $data['vehicle_index'];
            $parts = $data['parts'];
            unset($data['vehicle_index'], $data['parts']);

            $vehicle = $vehicles[$vehicleIndex];
            $data['vehicle_id'] = $vehicle->id;
            $data['customer_id'] = $vehicle->customer_id;

            $exists = WorkOrder::where('vehicle_id', $vehicle->id)
                ->where('status', 'completed')
                ->where('problem_description', $data['problem_description'])
                ->exists();

            if (!$exists) {
                $workOrder = WorkOrder::create($data);

                // Insert parts directly to skip inventory events, just like the original demo seeder
                $partsRows = [];
                foreach ($parts as $p) {
                    $part = Part::where('code', $p['code'])->first();
                    if ($part) {
                        $partsRows[] = [
                            'work_order_id' => $workOrder->id,
                            'part_id' => $part->id,
                            'quantity' => $p['quantity'],
                            'unit_price' => $p['unit_price'],
                            'line_total' => $p['quantity'] * $p['unit_price'],
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    }
                }

                if (!empty($partsRows)) {
                    DB::table('work_order_parts')->insert($partsRows);
                }

                $completedWorkOrdersCreated++;
            }
        }
        $this->command->info("Created {$completedWorkOrdersCreated} completed work orders with associated parts.");

        $this->command->info('=== GarageDemoSeeder Completed Successfully ===');
    }
}
