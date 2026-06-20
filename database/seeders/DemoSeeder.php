<?php

namespace Database\Seeders;

/**
 * DEMO SEEDER — ΜΟΝΟ ΓΙΑ DEMO / LOCAL ΧΡΗΣΗ.
 *
 * Εκτέλεση: php artisan db:seed --class=DemoSeeder
 *
 * ΜΗΝ τρέξεις σε production/client βάση.
 * Είναι idempotent: ξαναεκτελέσεις δεν διπλασιάζουν εγγραφές.
 *
 * Δεδομένα που δημιουργεί:
 *  - 25 πελάτες
 *  - 31 οχήματα
 *  - 10 εντολές εργασίας (3 νέες, 2 σε εξέλιξη, 4 ολοκληρωμένες, 1 ακυρωμένη)
 *  - 5 ραντεβού (3 σήμερα, 2 αύριο)
 */

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Part;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== DEMO SEEDER ===');

        $customers = $this->seedCustomers();
        $vehicles  = $this->seedVehicles($customers);
        $this->seedWorkOrders($vehicles);
        $this->seedAppointments($customers, $vehicles);

        $this->command->info('=== Ολοκληρώθηκε ===');
    }

    // -------------------------------------------------------------------------
    // CUSTOMERS  (25 εγγραφές)
    // Index:  0–24
    // -------------------------------------------------------------------------
    private function seedCustomers(): array
    {
        $rows = [
            /* 0  */ ['full_name' => 'Νικόλαος Παπαδόπουλος',    'phone' => '6971234001', 'email' => 'n.papadopoulos@gmail.com',   'address' => 'Αχαρνών 12, Αθήνα'],
            /* 1  */ ['full_name' => 'Γεώργιος Αντωνίου',         'phone' => '6982340002', 'email' => 'g.antoniou@yahoo.gr',        'address' => 'Κηφισίας 45, Μαρούσι'],
            /* 2  */ ['full_name' => 'Μαρία Κωνσταντίνου',        'phone' => '6993450003', 'email' => 'm.konstantinou@mail.gr',     'address' => 'Πατησίων 78, Αθήνα'],
            /* 3  */ ['full_name' => 'Δημήτρης Ιωάννου',          'phone' => '6974560004', 'email' => 'd.ioannoy@gmail.com',        'address' => 'Λεωφ. Βουλιαγμένης 101, Γλυφάδα'],
            /* 4  */ ['full_name' => 'Ελένη Νικολάου',            'phone' => '6985670005', 'email' => '',                           'address' => 'Ηρακλείου 33, Ηράκλειο Κρήτης'],
            /* 5  */ ['full_name' => 'Κωνσταντίνος Γεωργίου',    'phone' => '6996780006', 'email' => 'k.georgiou@hotmail.com',     'address' => 'Εθν. Αντιστάσεως 55, Καλλιθέα'],
            /* 6  */ ['full_name' => 'Σταύρος Παπανικολάου',     'phone' => '6977890007', 'email' => 'stavros.p@gmail.com',        'address' => 'Μαραθώνος 7, Νέα Ιωνία'],
            /* 7  */ ['full_name' => 'Θεοδώρα Αλεξίου',          'phone' => '6988900008', 'email' => 'theodora.alex@gmail.com',    'address' => 'Αγ. Δημητρίου 20, Θεσσαλονίκη'],
            /* 8  */ ['full_name' => 'Αντώνης Χριστοδούλου',     'phone' => '6979010009', 'email' => 'a.xristodoulou@mail.com',    'address' => 'Βενιζέλου 88, Πάτρα'],
            /* 9  */ ['full_name' => 'Βασίλης Σταυρόπουλος',     'phone' => '6980120010', 'email' => 'v.stavropoulos@gmail.com',   'address' => 'Σόλωνος 15, Αθήνα'],
            /* 10 */ ['full_name' => 'Παναγιώτα Μαρκοπούλου',   'phone' => '6991230011', 'email' => 'p.markopoulou@yahoo.gr',     'address' => 'Λεωφ. Αλεξάνδρας 66, Αθήνα'],
            /* 11 */ ['full_name' => 'Μιχάλης Δημητρίου',        'phone' => '6972340012', 'email' => 'm.dimitriou@gmail.com',      'address' => 'Θησέως 29, Πειραιάς'],
            /* 12 */ ['full_name' => 'Χρήστος Καλογερόπουλος',  'phone' => '6983450013', 'email' => '',                           'address' => 'Δεληγιώργη 4, Αθήνα'],
            /* 13 */ ['full_name' => 'Σοφία Πετρίδου',           'phone' => '6994560014', 'email' => 'sofia.petridou@mail.gr',     'address' => 'Αρμένη 11, Ρέθυμνο'],
            /* 14 */ ['full_name' => 'Αναστάσιος Κυριακόπουλος','phone' => '6975670015', 'email' => 'tasos.k@gmail.com',          'address' => 'Πλ. Ομονοίας 3, Αθήνα'],
            /* 15 */ ['full_name' => 'Ρένα Στεφανίδου',          'phone' => '6986780016', 'email' => 'rena.stef@yahoo.gr',         'address' => 'Ικάρου 50, Ηλιούπολη'],
            /* 16 */ ['full_name' => 'Λευτέρης Αθανασίου',       'phone' => '6997890017', 'email' => 'l.athanassiou@gmail.com',    'address' => 'Κορίνθου 32, Κόρινθος'],
            /* 17 */ ['full_name' => 'Αγγελική Παπαγεωργίου',   'phone' => '6978900018', 'email' => 'angeliki.p@hotmail.com',     'address' => 'Νίκης 6, Θεσσαλονίκη'],
            /* 18 */ ['full_name' => 'Νίκος Τσιώτης',            'phone' => '6989010019', 'email' => 'n.tsiotis@gmail.com',        'address' => 'Μεσογείων 210, Χολαργός'],
            /* 19 */ ['full_name' => 'Ιωάννα Ζαφειρίου',         'phone' => '6970120020', 'email' => '',                           'address' => 'Αγ. Παρασκευής 14, Αγία Παρασκευή'],
            /* 20 */ ['full_name' => 'Κυριάκος Μαντζούκης',     'phone' => '6981230021', 'email' => 'k.mantzukis@mail.gr',        'address' => 'Ευαγγελιστρίας 8, Αθήνα'],
            /* 21 */ ['full_name' => 'Έλλη Σαββίδου',            'phone' => '6992340022', 'email' => 'elli.savvidou@gmail.com',    'address' => 'Τσιμισκή 77, Θεσσαλονίκη'],
            /* 22 */ ['full_name' => 'Δημήτρης Μαυρογιάννης',   'phone' => '6973450023', 'email' => 'd.mavrogiannis@yahoo.gr',    'address' => 'Λεωφ. Δημοκρατίας 19, Νέα Σμύρνη'],
            /* 23 */ ['full_name' => 'Ζωή Θεοδωρίδου',          'phone' => '6984560024', 'email' => 'zoe.theodoridou@mail.com',   'address' => 'Αριστοτέλους 40, Λάρισα'],
            /* 24 */ ['full_name' => 'Παναγιώτης Λαμπρόπουλος', 'phone' => '6995670025', 'email' => 'pan.lampropoulos@gmail.com', 'address' => 'Ολυμπίου 18, Βόλος'],
        ];

        $created = [];
        foreach ($rows as $row) {
            $created[] = Customer::firstOrCreate(['phone' => $row['phone']], $row);
        }

        $this->command->info('Πελάτες: ' . count($created));
        return $created;
    }

    // -------------------------------------------------------------------------
    // VEHICLES  (31 εγγραφές)
    //
    // Ευρετήριο:
    //  0: ΑΒΓ-1234  VW Golf       (cust 0  Παπαδόπουλος)
    //  1: ΑΒΓ-5678  Toyota Yaris  (cust 0  Παπαδόπουλος)
    //  2: ΚΗΗ-2201  Opel Astra    (cust 1  Αντωνίου)
    //  3: ΙΑΜ-3310  Fiat Punto    (cust 2  Κωνσταντίνου)
    //  4: ΜΕΚ-4422  Ford Focus    (cust 3  Ιωάννου)
    //  5: ΜΕΚ-9900  Nissan Qashqai(cust 3  Ιωάννου)
    //  6: ΝΒΚ-5533  Hyundai i20   (cust 4  Νικολάου)
    //  7: ΞΑΠ-6644  Renault Clio  (cust 5  Γεωργίου)
    //  8: ΟΜΓ-7755  Peugeot 208   (cust 6  Παπανικολάου)
    //  9: ΠΑΤ-8866  BMW 3 Series  (cust 7  Αλεξίου)
    // 10: ΡΦΘ-9977  Mercedes C    (cust 8  Χριστοδούλου)
    // 11: ΣΕΝ-1100  Skoda Octavia (cust 9  Σταυρόπουλος)
    // 12: ΣΕΝ-2233  Toyota Hilux  (cust 9  Σταυρόπουλος)
    // 13: ΤΑΚ-3344  Kia Sportage  (cust 10 Μαρκοπούλου)
    // 14: ΥΒΛ-4455  VW Polo       (cust 11 Δημητρίου)
    // 15: ΦΓΔ-5566  Citroen C3    (cust 12 Καλογερόπουλος)
    // 16: ΧΔΕ-6677  Seat Ibiza    (cust 13 Πετρίδου)
    // 17: ΨΕΖ-7788  Honda Civic   (cust 14 Κυριακόπουλος)
    // 18: ΩΖΗ-8899  Dacia Sandero (cust 15 Στεφανίδου)
    // 19: ΑΔΘ-9900  Mazda 3       (cust 16 Αθανασίου)
    // 20: ΑΔΘ-1111  Suzuki Vitara (cust 16 Αθανασίου)
    // 21: ΒΕΙ-2222  Ford Fiesta   (cust 17 Παπαγεωργίου)
    // 22: ΓΖΚ-3333  Hyundai Tucson(cust 18 Τσιώτης)
    // 23: ΔΗΛ-4444  Opel Corsa    (cust 19 Ζαφειρίου)
    // 24: ΕΘΜ-5555  VW Tiguan     (cust 20 Μαντζούκης)
    // 25: ΖΙΝ-6666  Audi A3       (cust 21 Σαββίδου)
    // 26: ΗΚΞ-7777  Renault Megane(cust 22 Μαυρογιάννης)
    // 27: ΗΚΞ-8888  Nissan Micra  (cust 22 Μαυρογιάννης)
    // 28: ΘΛΟ-9999  Toyota Corolla(cust 23 Θεοδωρίδου)
    // 29: ΙΜΠ-0001  Peugeot 3008  (cust 24 Λαμπρόπουλος)
    // 30: ΙΜΠ-0002  Skoda Fabia   (cust 24 Λαμπρόπουλος)
    // -------------------------------------------------------------------------
    private function seedVehicles(array $customers): array
    {
        $t = Carbon::today();

        $rows = [
            // cust 0 — Παπαδόπουλος
            ['c' => 0,  'plate_number' => 'ΑΒΓ-1234', 'make' => 'Volkswagen',   'model' => 'Golf',      'year' => 2018, 'mileage' => 87000,  'vin' => 'WVWZZZ1KZ9W123456', 'kteo' => $t->copy()->addMonths(14)],
            ['c' => 0,  'plate_number' => 'ΑΒΓ-5678', 'make' => 'Toyota',       'model' => 'Yaris',     'year' => 2015, 'mileage' => 112000, 'vin' => null,                  'kteo' => $t->copy()->subDays(10)],
            // cust 1 — Αντωνίου
            ['c' => 1,  'plate_number' => 'ΚΗΗ-2201', 'make' => 'Opel',         'model' => 'Astra',     'year' => 2019, 'mileage' => 54000,  'vin' => null,                  'kteo' => $t->copy()->addDays(25)],
            // cust 2 — Κωνσταντίνου
            ['c' => 2,  'plate_number' => 'ΙΑΜ-3310', 'make' => 'Fiat',         'model' => 'Punto',     'year' => 2011, 'mileage' => 143000, 'vin' => null,                  'kteo' => $t->copy()->addMonths(6)],
            // cust 3 — Ιωάννου
            ['c' => 3,  'plate_number' => 'ΜΕΚ-4422', 'make' => 'Ford',         'model' => 'Focus',     'year' => 2017, 'mileage' => 76000,  'vin' => 'WF0EXXGBBEHJ12345',  'kteo' => $t->copy()->addDays(5)],
            ['c' => 3,  'plate_number' => 'ΜΕΚ-9900', 'make' => 'Nissan',       'model' => 'Qashqai',   'year' => 2020, 'mileage' => 38000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(20)],
            // cust 4 — Νικολάου
            ['c' => 4,  'plate_number' => 'ΝΒΚ-5533', 'make' => 'Hyundai',      'model' => 'i20',       'year' => 2016, 'mileage' => 98000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(3)],
            // cust 5 — Γεωργίου
            ['c' => 5,  'plate_number' => 'ΞΑΠ-6644', 'make' => 'Renault',      'model' => 'Clio',      'year' => 2014, 'mileage' => 124000, 'vin' => null,                  'kteo' => $t->copy()->addDays(18)],
            // cust 6 — Παπανικολάου
            ['c' => 6,  'plate_number' => 'ΟΜΓ-7755', 'make' => 'Peugeot',      'model' => '208',       'year' => 2013, 'mileage' => 155000, 'vin' => null,                  'kteo' => null],
            // cust 7 — Αλεξίου
            ['c' => 7,  'plate_number' => 'ΠΑΤ-8866', 'make' => 'BMW',          'model' => '3 Series',  'year' => 2021, 'mileage' => 28000,  'vin' => 'WBA8E9C54HG123456',  'kteo' => $t->copy()->addMonths(24)],
            // cust 8 — Χριστοδούλου
            ['c' => 8,  'plate_number' => 'ΡΦΘ-9977', 'make' => 'Mercedes-Benz','model' => 'C-Class',   'year' => 2019, 'mileage' => 47000,  'vin' => 'WDD2050421R123456',  'kteo' => $t->copy()->addMonths(18)],
            // cust 9 — Σταυρόπουλος
            ['c' => 9,  'plate_number' => 'ΣΕΝ-1100', 'make' => 'Skoda',        'model' => 'Octavia',   'year' => 2016, 'mileage' => 104000, 'vin' => null,                  'kteo' => $t->copy()->addMonths(8)],
            ['c' => 9,  'plate_number' => 'ΣΕΝ-2233', 'make' => 'Toyota',       'model' => 'Hilux',     'year' => 2018, 'mileage' => 68000,  'vin' => null,                  'kteo' => $t->copy()->addDays(12)],
            // cust 10 — Μαρκοπούλου
            ['c' => 10, 'plate_number' => 'ΤΑΚ-3344', 'make' => 'Kia',          'model' => 'Sportage',  'year' => 2020, 'mileage' => 32000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(16)],
            // cust 11 — Δημητρίου
            ['c' => 11, 'plate_number' => 'ΥΒΛ-4455', 'make' => 'Volkswagen',   'model' => 'Polo',      'year' => 2012, 'mileage' => 178000, 'vin' => null,                  'kteo' => $t->copy()->subMonths(2)],
            // cust 12 — Καλογερόπουλος
            ['c' => 12, 'plate_number' => 'ΦΓΔ-5566', 'make' => 'Citroen',      'model' => 'C3',        'year' => 2015, 'mileage' => 91000,  'vin' => null,                  'kteo' => $t->copy()->addDays(3)],
            // cust 13 — Πετρίδου
            ['c' => 13, 'plate_number' => 'ΧΔΕ-6677', 'make' => 'Seat',         'model' => 'Ibiza',     'year' => 2017, 'mileage' => 63000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(11)],
            // cust 14 — Κυριακόπουλος
            ['c' => 14, 'plate_number' => 'ΨΕΖ-7788', 'make' => 'Honda',        'model' => 'Civic',     'year' => 2016, 'mileage' => 88000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(7)],
            // cust 15 — Στεφανίδου
            ['c' => 15, 'plate_number' => 'ΩΖΗ-8899', 'make' => 'Dacia',        'model' => 'Sandero',   'year' => 2018, 'mileage' => 57000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(5)],
            // cust 16 — Αθανασίου
            ['c' => 16, 'plate_number' => 'ΑΔΘ-9900', 'make' => 'Mazda',        'model' => '3',         'year' => 2019, 'mileage' => 41000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(13)],
            ['c' => 16, 'plate_number' => 'ΑΔΘ-1111', 'make' => 'Suzuki',       'model' => 'Vitara',    'year' => 2021, 'mileage' => 19000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(28)],
            // cust 17 — Παπαγεωργίου
            ['c' => 17, 'plate_number' => 'ΒΕΙ-2222', 'make' => 'Ford',         'model' => 'Fiesta',    'year' => 2014, 'mileage' => 116000, 'vin' => null,                  'kteo' => $t->copy()->addDays(22)],
            // cust 18 — Τσιώτης
            ['c' => 18, 'plate_number' => 'ΓΖΚ-3333', 'make' => 'Hyundai',      'model' => 'Tucson',    'year' => 2022, 'mileage' => 15000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(30)],
            // cust 19 — Ζαφειρίου
            ['c' => 19, 'plate_number' => 'ΔΗΛ-4444', 'make' => 'Opel',         'model' => 'Corsa',     'year' => 2013, 'mileage' => 132000, 'vin' => null,                  'kteo' => $t->copy()->addMonths(4)],
            // cust 20 — Μαντζούκης
            ['c' => 20, 'plate_number' => 'ΕΘΜ-5555', 'make' => 'Volkswagen',   'model' => 'Tiguan',    'year' => 2020, 'mileage' => 44000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(22)],
            // cust 21 — Σαββίδου
            ['c' => 21, 'plate_number' => 'ΖΙΝ-6666', 'make' => 'Audi',         'model' => 'A3',        'year' => 2018, 'mileage' => 61000,  'vin' => 'WAUZZZ8P5JA123456',  'kteo' => $t->copy()->addMonths(10)],
            // cust 22 — Μαυρογιάννης
            ['c' => 22, 'plate_number' => 'ΗΚΞ-7777', 'make' => 'Renault',      'model' => 'Megane',    'year' => 2016, 'mileage' => 89000,  'vin' => null,                  'kteo' => $t->copy()->addDays(8)],
            ['c' => 22, 'plate_number' => 'ΗΚΞ-8888', 'make' => 'Nissan',       'model' => 'Micra',     'year' => 2011, 'mileage' => 161000, 'vin' => null,                  'kteo' => null],
            // cust 23 — Θεοδωρίδου
            ['c' => 23, 'plate_number' => 'ΘΛΟ-9999', 'make' => 'Toyota',       'model' => 'Corolla',   'year' => 2017, 'mileage' => 73000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(9)],
            // cust 24 — Λαμπρόπουλος
            ['c' => 24, 'plate_number' => 'ΙΜΠ-0001', 'make' => 'Peugeot',      'model' => '3008',      'year' => 2021, 'mileage' => 26000,  'vin' => null,                  'kteo' => $t->copy()->addMonths(26)],
            ['c' => 24, 'plate_number' => 'ΙΜΠ-0002', 'make' => 'Skoda',        'model' => 'Fabia',     'year' => 2010, 'mileage' => 197000, 'vin' => null,                  'kteo' => $t->copy()->subMonths(1)],
        ];

        $created = [];
        foreach ($rows as $row) {
            $customerModel = $customers[$row['c']];
            $vehicle = Vehicle::firstOrCreate(
                ['plate_number' => $row['plate_number']],
                [
                    'customer_id'    => $customerModel->id,
                    'make'           => $row['make'],
                    'model'          => $row['model'],
                    'year'           => $row['year'],
                    'mileage'        => $row['mileage'],
                    'vin'            => $row['vin'],
                    'kteo_expires_at'=> $row['kteo'] ? $row['kteo']->toDateString() : null,
                ]
            );
            $created[] = $vehicle;
        }

        $this->command->info('Οχήματα: ' . count($created));
        return $created;
    }

    // -------------------------------------------------------------------------
    // WORK ORDERS
    // Vehicles referenced by index from the array above.
    // -------------------------------------------------------------------------
    private function seedWorkOrders(array $vehicles): void
    {
        $now = Carbon::now();

        // Part IDs (nullable-safe)
        $pid = fn (string $code) => Part::where('code', $code)->value('id');
        $oilId      = $pid('OIL-5W40');
        $filtOilId  = $pid('FLT-OIL-01');
        $filtAirId  = $pid('FLT-AIR-01');
        $filtCabId  = $pid('FLT-CAB-01');
        $padsId     = $pid('BRK-PAD-FR');
        $batId      = $pid('BAT-60AH');
        $sparkId    = $pid('IGN-SPRK-01');

        $orders = [
            // ────────────── ΝΕΕΣ ──────────────
            [
                'vidx'                => 0,   // ΑΒΓ-1234 VW Golf — Παπαδόπουλος
                'problem_description' => 'Φωτάκι check engine αναβοσβήνει. Ο πελάτης αναφέρει και αυξημένη κατανάλωση καυσίμου.',
                'diagnosis'           => null,
                'work_performed'      => null,
                'status'              => 'new',
                'labor_cost'          => 0, 'parts_cost' => 0, 'total_cost' => 0,
                'current_mileage'     => 87320,
                'next_service_date'   => null, 'next_service_mileage' => null,
                'created_at'          => $now->copy()->subHours(3),
                'parts'               => [],
            ],
            [
                'vidx'                => 4,   // ΜΕΚ-4422 Ford Focus — Ιωάννου
                'problem_description' => 'Χαρακτηριστικός τριγμός από εμπρός δεξί τροχό κατά την οδήγηση, κυρίως σε στροφές.',
                'diagnosis'           => null,
                'work_performed'      => null,
                'status'              => 'new',
                'labor_cost'          => 0, 'parts_cost' => 0, 'total_cost' => 0,
                'current_mileage'     => 76440,
                'next_service_date'   => null, 'next_service_mileage' => null,
                'created_at'          => $now->copy()->subDay(),
                'parts'               => [],
            ],
            [
                'vidx'                => 17,  // ΨΕΖ-7788 Honda Civic — Κυριακόπουλος
                'problem_description' => 'Τακτικό service — αλλαγή λαδιών & φίλτρων στα 90.000 χλμ.',
                'diagnosis'           => null,
                'work_performed'      => null,
                'status'              => 'new',
                'labor_cost'          => 0, 'parts_cost' => 0, 'total_cost' => 0,
                'current_mileage'     => 88200,
                'next_service_date'   => null, 'next_service_mileage' => null,
                'created_at'          => $now->copy()->subDays(2),
                'parts'               => [],
            ],
            // ────────────── ΣΕ ΕΞΕΛΙΞΗ ──────────────
            [
                'vidx'                => 9,   // ΠΑΤ-8866 BMW 3 Series — Αλεξίου
                'problem_description' => 'Δυσκολία εκκίνησης. Ο κινητήρας παίρνει μπρος με αρκετές προσπάθειες.',
                'diagnosis'           => 'Αδύνατη μπαταρία (4ο χρόνο). Επίσης διαπιστώθηκε φθορά στα μπουζί.',
                'work_performed'      => null,
                'status'              => 'in_progress',
                'labor_cost'          => 60, 'parts_cost' => 0, 'total_cost' => 60,
                'current_mileage'     => 28150,
                'next_service_date'   => null, 'next_service_mileage' => null,
                'created_at'          => $now->copy()->subHours(28),
                'parts'               => [],
            ],
            [
                'vidx'                => 14,  // ΥΒΛ-4455 VW Polo — Δημητρίου
                'problem_description' => 'Αντικατάσταση ιμάντα χρονισμού — έχει λήξει βάσει χιλιομέτρων.',
                'diagnosis'           => 'Ιμάντας χρονισμού σε επικίνδυνη κατάσταση. Απαιτείται ολόκληρο κιτ.',
                'work_performed'      => null,
                'status'              => 'in_progress',
                'labor_cost'          => 180, 'parts_cost' => 0, 'total_cost' => 180,
                'current_mileage'     => 178500,
                'next_service_date'   => null, 'next_service_mileage' => null,
                'created_at'          => $now->copy()->subDays(2),
                'parts'               => [],
            ],
            // ────────────── ΟΛΟΚΛΗΡΩΜΕΝΕΣ ──────────────
            [
                'vidx'                => 3,   // ΙΑΜ-3310 Fiat Punto — Κωνσταντίνου
                'problem_description' => 'Service 15.000 χλμ — λάδια, φίλτρα, γενικός έλεγχος.',
                'diagnosis'           => 'Τυπική συντήρηση. Χρειάζεται επίσης νέο φίλτρο καμπίνας.',
                'work_performed'      => 'Αλλαγή λαδιού 4L 5W-40, φίλτρο λαδιού, φίλτρο αέρα, φίλτρο καμπίνας. Γενικός έλεγχος ΟΚ.',
                'status'              => 'completed',
                'labor_cost'          => 45,
                'parts_cost'          => 78.00,
                'total_cost'          => 123.00,
                'current_mileage'     => 143000,
                'next_service_date'   => $now->copy()->addYear()->toDateString(),
                'next_service_mileage'=> 158000,
                'created_at'          => $now->copy()->subDays(7),
                'parts'               => [
                    ['part_id' => $oilId,    'quantity' => 4, 'unit_price' => 14.50, 'line_total' => 58.00],
                    ['part_id' => $filtOilId,'quantity' => 1, 'unit_price' => 10.00, 'line_total' => 10.00],
                    ['part_id' => $filtCabId,'quantity' => 1, 'unit_price' => 12.00, 'line_total' => 12.00],
                ],
            ],
            [
                'vidx'                => 7,   // ΞΑΠ-6644 Renault Clio — Γεωργίου
                'problem_description' => 'Τριγμός φρένων εμπρός. Δεν πιάνουν καλά.',
                'diagnosis'           => 'Φθαρμένα τακάκια εμπρός. Δίσκοι εντός ορίων.',
                'work_performed'      => 'Αντικατάσταση τακακίων εμπρός και σφιγκτήρων. Αλλαγή υγρού φρένων.',
                'status'              => 'completed',
                'labor_cost'          => 70,
                'parts_cost'          => 55.00,
                'total_cost'          => 125.00,
                'current_mileage'     => 124200,
                'next_service_date'   => null, 'next_service_mileage' => null,
                'created_at'          => $now->copy()->subDays(14),
                'parts'               => [
                    ['part_id' => $padsId,'quantity' => 1, 'unit_price' => 55.00, 'line_total' => 55.00],
                ],
            ],
            [
                'vidx'                => 11,  // ΣΕΝ-1100 Skoda Octavia — Σταυρόπουλος
                'problem_description' => 'Δεν ξεκινά το πρωί με κρύο καιρό.',
                'diagnosis'           => 'Μπαταρία εκτός ορίων (4,5 χρόνια παλιά). Αντικατάσταση.',
                'work_performed'      => 'Αντικατάσταση μπαταρίας 60Ah. Έλεγχος δυναμό — ΟΚ.',
                'status'              => 'completed',
                'labor_cost'          => 25,
                'parts_cost'          => 95.00,
                'total_cost'          => 120.00,
                'current_mileage'     => 104800,
                'next_service_date'   => null, 'next_service_mileage' => null,
                'created_at'          => $now->copy()->subDays(21),
                'parts'               => [
                    ['part_id' => $batId,'quantity' => 1, 'unit_price' => 95.00, 'line_total' => 95.00],
                ],
            ],
            [
                'vidx'                => 23,  // ΔΗΛ-4444 Opel Corsa — Ζαφειρίου
                'problem_description' => 'Τακτικό service + αντικατάσταση μπουζί.',
                'diagnosis'           => 'Service στα 130.000 χλμ. Μπουζί σε κακή κατάσταση.',
                'work_performed'      => 'Αλλαγή λαδιού 5L, φίλτρο λαδιού, φίλτρο αέρα, 4 μπουζί ιριδίου.',
                'status'              => 'completed',
                'labor_cost'          => 55,
                'parts_cost'          => 167.50,
                'total_cost'          => 222.50,
                'current_mileage'     => 132500,
                'next_service_date'   => $now->copy()->addMonths(10)->toDateString(),
                'next_service_mileage'=> 147000,
                'created_at'          => $now->copy()->subDays(30),
                'parts'               => [
                    ['part_id' => $oilId,    'quantity' => 5, 'unit_price' => 14.50, 'line_total' => 72.50],
                    ['part_id' => $filtOilId,'quantity' => 1, 'unit_price' => 10.00, 'line_total' => 10.00],
                    ['part_id' => $filtAirId,'quantity' => 1, 'unit_price' => 15.00, 'line_total' => 15.00],
                    ['part_id' => $sparkId,  'quantity' => 4, 'unit_price' => 20.00, 'line_total' => 80.00],
                ],
            ],
            // ────────────── ΑΚΥΡΩΜΕΝΗ ──────────────
            [
                'vidx'                => 8,   // ΟΜΓ-7755 Peugeot 208 — Παπανικολάου
                'problem_description' => 'Κλιματισμός δεν κρυώνει. Πιθανή διαρροή ψυκτικού.',
                'diagnosis'           => null,
                'work_performed'      => null,
                'status'              => 'cancelled',
                'labor_cost'          => 0, 'parts_cost' => 0, 'total_cost' => 0,
                'current_mileage'     => null,
                'next_service_date'   => null, 'next_service_mileage' => null,
                'created_at'          => $now->copy()->subDays(5),
                'parts'               => [],
            ],
        ];

        $count = 0;
        foreach ($orders as $order) {
            $vehicle = $vehicles[$order['vidx']] ?? null;
            if (!$vehicle) continue;

            if ($vehicle->workOrders()->exists()) continue;

            $parts     = $order['parts'];
            $createdAt = $order['created_at'];
            unset($order['vidx'], $order['parts'], $order['created_at']);

            $order['vehicle_id']  = $vehicle->id;
            $order['customer_id'] = $vehicle->customer_id;

            $workOrder = WorkOrder::create($order);
            $workOrder->created_at = $createdAt;
            $workOrder->saveQuietly();

            // Insert parts directly to skip inventory events (demo data)
            if (!empty($parts)) {
                $rows = array_map(fn ($p) => array_merge($p, [
                    'work_order_id' => $workOrder->id,
                    'created_at'    => $createdAt,
                    'updated_at'    => $createdAt,
                ]), $parts);

                DB::table('work_order_parts')->insert($rows);
            }

            $count++;
        }

        $this->command->info('Εντολές εργασίας: ' . $count);
    }

    // -------------------------------------------------------------------------
    // APPOINTMENTS  (5 εγγραφές — 3 σήμερα, 2 αύριο)
    // -------------------------------------------------------------------------
    private function seedAppointments(array $customers, array $vehicles): void
    {
        $today    = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $rows = [
            [
                'cidx'  => 3,  'vidx'  => 5,   // Ιωάννου — ΜΕΚ-9900 Nissan Qashqai
                'date'  => $today->copy()->setTime(9, 0),
                'status'=> 'scheduled',
                'desc'  => 'Τακτικό service 40.000 χλμ.',
            ],
            [
                'cidx'  => 10, 'vidx'  => 13,  // Μαρκοπούλου — ΤΑΚ-3344 Kia Sportage
                'date'  => $today->copy()->setTime(11, 30),
                'status'=> 'scheduled',
                'desc'  => 'Έλεγχος κλιματισμού — δεν κρυώνει σωστά.',
            ],
            [
                'cidx'  => 18, 'vidx'  => 22,  // Τσιώτης — ΓΖΚ-3333 Hyundai Tucson
                'date'  => $today->copy()->setTime(15, 0),
                'status'=> 'in_progress',
                'desc'  => 'Έλεγχος φώτων & διαγνωστικό.',
            ],
            [
                'cidx'  => 16, 'vidx'  => 20,  // Αθανασίου — ΑΔΘ-1111 Suzuki Vitara
                'date'  => $tomorrow->copy()->setTime(10, 0),
                'status'=> 'scheduled',
                'desc'  => 'Αλλαγή λαδιών & γενικός έλεγχος.',
            ],
            [
                'cidx'  => 21, 'vidx'  => 25,  // Σαββίδου — ΖΙΝ-6666 Audi A3
                'date'  => $tomorrow->copy()->setTime(14, 0),
                'status'=> 'scheduled',
                'desc'  => 'Παράπονο τριγμού στα φρένα.',
            ],
        ];

        $count = 0;
        foreach ($rows as $r) {
            $customer = $customers[$r['cidx']] ?? null;
            $vehicle  = $vehicles[$r['vidx']]  ?? null;
            if (!$customer || !$vehicle) continue;

            $dateStr = $r['date']->toDateTimeString();
            if (Appointment::where('customer_id', $customer->id)->where('appointment_date', $dateStr)->exists()) {
                continue;
            }

            Appointment::create([
                'customer_id'      => $customer->id,
                'vehicle_id'       => $vehicle->id,
                'appointment_date' => $dateStr,
                'status'           => $r['status'],
                'description'      => $r['desc'],
            ]);
            $count++;
        }

        $this->command->info('Ραντεβού: ' . $count);
    }
}
