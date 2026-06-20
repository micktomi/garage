<?php

namespace Database\Seeders;

use App\Models\Part;
use Illuminate\Database\Seeder;

class PartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parts = [
            // Λάδια / Υγρά
            ['code' => 'OIL-5W30', 'name' => 'Λάδι Κινητήρα 5W-30 (1L)', 'description' => 'Συνθετικό λάδι κινητήρα', 'quantity' => 100, 'purchase_price' => 5.50, 'sale_price' => 12.00],
            ['code' => 'OIL-5W40', 'name' => 'Λάδι Κινητήρα 5W-40 (1L)', 'description' => 'Συνθετικό λάδι κινητήρα υψηλής απόδοσης', 'quantity' => 80, 'purchase_price' => 6.00, 'sale_price' => 14.50],
            ['code' => 'OIL-10W40', 'name' => 'Λάδι Κινητήρα 10W-40 (1L)', 'description' => 'Ημισυνθετικό λάδι κινητήρα', 'quantity' => 120, 'purchase_price' => 4.50, 'sale_price' => 9.00],
            ['code' => 'FL-BRK-DOT4', 'name' => 'Υγρό Φρένων DOT 4 (500ml)', 'description' => 'Υγρό φρένων', 'quantity' => 50, 'purchase_price' => 3.00, 'sale_price' => 8.00],
            ['code' => 'FL-COOL-G12', 'name' => 'Αντιψυκτικό Υγρό G12 (1L)', 'description' => 'Αντιψυκτικό/Ψυκτικό υγρό', 'quantity' => 60, 'purchase_price' => 2.50, 'sale_price' => 6.50],
            
            // Φίλτρα
            ['code' => 'FLT-OIL-01', 'name' => 'Φίλτρο Λαδιού', 'description' => 'Τυπικό φίλτρο λαδιού', 'quantity' => 150, 'purchase_price' => 3.50, 'sale_price' => 10.00],
            ['code' => 'FLT-AIR-01', 'name' => 'Φίλτρο Αέρα', 'description' => 'Φίλτρο αέρα κινητήρα', 'quantity' => 100, 'purchase_price' => 5.00, 'sale_price' => 15.00],
            ['code' => 'FLT-CAB-01', 'name' => 'Φίλτρο Καμπίνας (Απλό)', 'description' => 'Φίλτρο γύρης/καμπίνας', 'quantity' => 80, 'purchase_price' => 4.00, 'sale_price' => 12.00],
            ['code' => 'FLT-CAB-02', 'name' => 'Φίλτρο Καμπίνας (Ενεργού Άνθρακα)', 'description' => 'Φίλτρο καμπίνας ενεργού άνθρακα', 'quantity' => 60, 'purchase_price' => 8.00, 'sale_price' => 22.00],
            ['code' => 'FLT-FUEL-01', 'name' => 'Φίλτρο Καυσίμου', 'description' => 'Φίλτρο βενζίνης/πετρελαίου', 'quantity' => 70, 'purchase_price' => 9.00, 'sale_price' => 25.00],

            // Φρένα
            ['code' => 'BRK-PAD-FR', 'name' => 'Τακάκια Φρένων Εμπρός', 'description' => 'Σετ τακάκια εμπρός άξονα', 'quantity' => 40, 'purchase_price' => 25.00, 'sale_price' => 55.00],
            ['code' => 'BRK-PAD-RR', 'name' => 'Τακάκια Φρένων Πίσω', 'description' => 'Σετ τακάκια πίσω άξονα', 'quantity' => 30, 'purchase_price' => 20.00, 'sale_price' => 45.00],
            ['code' => 'BRK-DSC-FR', 'name' => 'Δίσκοι Φρένων Εμπρός', 'description' => 'Σετ δισκόπλακες εμπρός', 'quantity' => 20, 'purchase_price' => 45.00, 'sale_price' => 95.00],
            ['code' => 'BRK-DSC-RR', 'name' => 'Δίσκοι Φρένων Πίσω', 'description' => 'Σετ δισκόπλακες πίσω', 'quantity' => 15, 'purchase_price' => 35.00, 'sale_price' => 75.00],
            ['code' => 'BRK-SHOE', 'name' => 'Σιαγόνες Φρένων', 'description' => 'Σετ σιαγόνες πίσω (ταμπούρα)', 'quantity' => 10, 'purchase_price' => 18.00, 'sale_price' => 40.00],

            // Ανάφλεξη / Κινητήρας
            ['code' => 'IGN-SPRK-01', 'name' => 'Μπουζί Ιριδίου', 'description' => 'Μπουζί υψηλής διάρκειας', 'quantity' => 200, 'purchase_price' => 8.00, 'sale_price' => 20.00],
            ['code' => 'IGN-SPRK-02', 'name' => 'Μπουζί Απλό', 'description' => 'Τυπικό μπουζί', 'quantity' => 150, 'purchase_price' => 3.00, 'sale_price' => 8.00],
            ['code' => 'IGN-COIL', 'name' => 'Πολλαπλασιαστής', 'description' => 'Πηνίο ανάφλεξης', 'quantity' => 20, 'purchase_price' => 35.00, 'sale_price' => 80.00],
            ['code' => 'ENG-GLW', 'name' => 'Προθερμαντήρας (Glow Plug)', 'description' => 'Για κινητήρες diesel', 'quantity' => 40, 'purchase_price' => 12.00, 'sale_price' => 28.00],

            // Μπαταρίες
            ['code' => 'BAT-45AH', 'name' => 'Μπαταρία 45Ah', 'description' => 'Μπαταρία μολύβδου 45Ah', 'quantity' => 10, 'purchase_price' => 40.00, 'sale_price' => 75.00],
            ['code' => 'BAT-60AH', 'name' => 'Μπαταρία 60Ah', 'description' => 'Μπαταρία μολύβδου 60Ah', 'quantity' => 15, 'purchase_price' => 50.00, 'sale_price' => 95.00],
            ['code' => 'BAT-75AH', 'name' => 'Μπαταρία 75Ah', 'description' => 'Μπαταρία μολύβδου 75Ah', 'quantity' => 10, 'purchase_price' => 65.00, 'sale_price' => 115.00],
            ['code' => 'BAT-AGM-70AH', 'name' => 'Μπαταρία AGM 70Ah', 'description' => 'Για συστήματα Start-Stop', 'quantity' => 5, 'purchase_price' => 110.00, 'sale_price' => 190.00],

            // Ανάρτηση / Διεύθυνση
            ['code' => 'SUS-SHK-FR', 'name' => 'Αμορτισέρ Εμπρός', 'description' => 'Ζεύγος αμορτισέρ εμπρός', 'quantity' => 8, 'purchase_price' => 60.00, 'sale_price' => 130.00],
            ['code' => 'SUS-SHK-RR', 'name' => 'Αμορτισέρ Πίσω', 'description' => 'Ζεύγος αμορτισέρ πίσω', 'quantity' => 8, 'purchase_price' => 50.00, 'sale_price' => 110.00],
            ['code' => 'SUS-SPR-FR', 'name' => 'Ελατήρια Ανάρτησης Εμπρός', 'description' => 'Ζεύγος ελατηρίων', 'quantity' => 4, 'purchase_price' => 35.00, 'sale_price' => 80.00],
            ['code' => 'SUS-ARM', 'name' => 'Ψαλίδι Ανάρτησης', 'description' => 'Κάτω ψαλίδι', 'quantity' => 12, 'purchase_price' => 45.00, 'sale_price' => 100.00],
            ['code' => 'SUS-BUSH', 'name' => 'Συνεμπλόκ', 'description' => 'Ελαστικός σύνδεσμος', 'quantity' => 40, 'purchase_price' => 8.00, 'sale_price' => 20.00],
            ['code' => 'STR-TR', 'name' => 'Ακρόμπαρο', 'description' => 'Ακρόμπαρο συστήματος διεύθυνσης', 'quantity' => 16, 'purchase_price' => 15.00, 'sale_price' => 35.00],
            ['code' => 'STR-LNK', 'name' => 'Μπαλάκι Ζαμφόρ', 'description' => 'Σύνδεσμος αντιστρεπτικής', 'quantity' => 24, 'purchase_price' => 12.00, 'sale_price' => 25.00],

            // Ιμάντες / Χρονισμός
            ['code' => 'BLT-TMG', 'name' => 'Ιμάντας Χρονισμού', 'description' => 'Μόνο ο ιμάντας', 'quantity' => 15, 'purchase_price' => 25.00, 'sale_price' => 60.00],
            ['code' => 'BLT-TMG-KIT', 'name' => 'Σετ Χρονισμού', 'description' => 'Ιμάντας & ρουλεμάν', 'quantity' => 10, 'purchase_price' => 60.00, 'sale_price' => 140.00],
            ['code' => 'BLT-TMG-WTR', 'name' => 'Σετ Χρονισμού με Αντλία Νερού', 'description' => 'Ιμάντας, ρουλεμάν & αντλία', 'quantity' => 8, 'purchase_price' => 90.00, 'sale_price' => 195.00],
            ['code' => 'BLT-ALT', 'name' => 'Ιμάντας Δυναμό / Αξεσουάρ', 'description' => 'Ιμάντας Poly-V', 'quantity' => 30, 'purchase_price' => 10.00, 'sale_price' => 25.00],

            // Αισθητήρες
            ['code' => 'SNR-O2', 'name' => 'Αισθητήρας Λάμδα (Οξυγόνου)', 'description' => 'Αισθητήρας καυσαερίων', 'quantity' => 10, 'purchase_price' => 45.00, 'sale_price' => 110.00],
            ['code' => 'SNR-MAF', 'name' => 'Αισθητήρας Μάζας Αέρα (MAF)', 'description' => 'Μετρητής αέρα', 'quantity' => 5, 'purchase_price' => 60.00, 'sale_price' => 140.00],
            ['code' => 'SNR-ABS', 'name' => 'Αισθητήρας ABS', 'description' => 'Αισθητήρας στροφών τροχού', 'quantity' => 12, 'purchase_price' => 20.00, 'sale_price' => 50.00],
            ['code' => 'SNR-CMP', 'name' => 'Αισθητήρας Εκκεντροφόρου', 'description' => 'Αισθητήρας θέσης', 'quantity' => 8, 'purchase_price' => 25.00, 'sale_price' => 65.00],
            ['code' => 'SNR-CKP', 'name' => 'Αισθητήρας Στροφάλου', 'description' => 'Αισθητήρας στροφών κινητήρα', 'quantity' => 8, 'purchase_price' => 30.00, 'sale_price' => 75.00],

            // Ηλεκτρικά
            ['code' => 'EL-LMP-H7', 'name' => 'Λάμπα H7', 'description' => 'Λάμπα αλογόνου (1 τεμ)', 'quantity' => 100, 'purchase_price' => 3.50, 'sale_price' => 10.00],
            ['code' => 'EL-LMP-H4', 'name' => 'Λάμπα H4', 'description' => 'Λάμπα αλογόνου (1 τεμ)', 'quantity' => 80, 'purchase_price' => 3.00, 'sale_price' => 9.00],
            ['code' => 'EL-LMP-LED', 'name' => 'Σετ Λάμπες LED H7', 'description' => 'Λάμπες LED υψηλής φωτεινότητας', 'quantity' => 15, 'purchase_price' => 25.00, 'sale_price' => 65.00],
            ['code' => 'EL-ALT', 'name' => 'Δυναμό (Εναλλακτήρας)', 'description' => 'Ανακατασκευασμένο δυναμό', 'quantity' => 3, 'purchase_price' => 120.00, 'sale_price' => 250.00],
            ['code' => 'EL-STR', 'name' => 'Μίζα', 'description' => 'Ανακατασκευασμένη μίζα', 'quantity' => 3, 'purchase_price' => 110.00, 'sale_price' => 230.00],

            // Ψύξη
            ['code' => 'RAD-M', 'name' => 'Ψυγείο Νερού', 'description' => 'Κύριο ψυγείο κινητήρα', 'quantity' => 5, 'purchase_price' => 70.00, 'sale_price' => 160.00],
            ['code' => 'WTR-PMP', 'name' => 'Αντλία Νερού', 'description' => 'Αντλία κυκλοφορίας', 'quantity' => 10, 'purchase_price' => 35.00, 'sale_price' => 85.00],
            ['code' => 'THR-STAT', 'name' => 'Θερμοστάτης', 'description' => 'Θερμοστάτης με περίβλημα', 'quantity' => 15, 'purchase_price' => 18.00, 'sale_price' => 45.00],

            // Συμπλέκτης / Μετάδοση
            ['code' => 'CLU-KIT', 'name' => 'Σετ Συμπλέκτη', 'description' => 'Πλατώ, δίσκος, ρουλεμάν', 'quantity' => 5, 'purchase_price' => 95.00, 'sale_price' => 220.00],
            ['code' => 'CLU-FLW', 'name' => 'Βολάν Διπλής Μάζας', 'description' => 'Σφόνδυλος κινητήρα', 'quantity' => 2, 'purchase_price' => 250.00, 'sale_price' => 550.00],
            ['code' => 'DRV-JNT', 'name' => 'Μπιλιοφόρος', 'description' => 'Σύνδεσμος ημιαξονίου', 'quantity' => 10, 'purchase_price' => 35.00, 'sale_price' => 85.00],
            ['code' => 'DRV-BT', 'name' => 'Φούσκα Ημιαξονίου', 'description' => 'Προστατευτικό κάλυμμα', 'quantity' => 30, 'purchase_price' => 6.00, 'sale_price' => 18.00],

            // Αναλώσιμα / Διάφορα
            ['code' => 'WIP-FR', 'name' => 'Υαλοκαθαριστήρες Εμπρός (Σετ)', 'description' => 'Σετ 2 τεμαχίων', 'quantity' => 40, 'purchase_price' => 12.00, 'sale_price' => 28.00],
            ['code' => 'WIP-RR', 'name' => 'Υαλοκαθαριστήρας Πίσω', 'description' => '1 τεμάχιο', 'quantity' => 20, 'purchase_price' => 6.00, 'sale_price' => 15.00],
            ['code' => 'MISC-CLNR', 'name' => 'Καθαριστικό Φρένων (Brake Cleaner)', 'description' => 'Σπρέι καθαρισμού', 'quantity' => 100, 'purchase_price' => 2.00, 'sale_price' => 6.00],
        ];

        foreach ($parts as $part) {
            Part::updateOrCreate(
                ['code' => $part['code']],
                $part
            );
        }
    }
}
