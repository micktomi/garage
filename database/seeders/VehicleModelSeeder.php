<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleModelSeeder extends Seeder
{
    /**
     * Starter set μαρκών/μοντέλων για την ελληνική αγορά.
     *
     * ΣΗΜΕΙΩΣΗ: Αυτό είναι ενα curated αρχικό σύνολο, ΟΧΙ πλήρης κατάλογος.
     * Στη φόρμα το πεδίο «Μοντέλο» δέχεται και ελεύθερο κείμενο, οπότε ενα κενό
     * εδώ ΔΕΝ μπλοκάρει τον χρήστη — απλώς δεν θα προταθεί στο autocomplete.
     * Επέκτεινέ το όποτε δεις μοντέλο που λείπει.
     */
    public function run(): void
    {
        $data = [
            'Volkswagen'    => ['Polo', 'Golf', 'Passat', 'Tiguan', 'Touran', 'Up', 'Caddy', 'Transporter', 'T-Roc', 'T-Cross', 'Jetta'],
            'Opel'          => ['Corsa', 'Astra', 'Insignia', 'Mokka', 'Crossland', 'Grandland', 'Zafira', 'Meriva', 'Adam', 'Vectra', 'Combo'],
            'Toyota'        => ['Aygo', 'Yaris', 'Corolla', 'Auris', 'C-HR', 'RAV4', 'Avensis', 'Hilux', 'Prius', 'Camry', 'Proace'],
            'Ford'          => ['Ka', 'Fiesta', 'Focus', 'Puma', 'Kuga', 'EcoSport', 'Mondeo', 'C-Max', 'Galaxy', 'Transit', 'Ranger'],
            'Fiat'          => ['Panda', 'Punto', '500', '500L', '500X', 'Tipo', 'Bravo', 'Stilo', 'Qubo', 'Doblo'],
            'Nissan'        => ['Micra', 'Note', 'Juke', 'Qashqai', 'X-Trail', 'Pulsar', 'Almera', 'Leaf', 'Navara'],
            'Hyundai'       => ['i10', 'i20', 'i30', 'i40', 'Tucson', 'Kona', 'Santa Fe', 'ix35', 'Accent', 'Getz', 'Bayon'],
            'Kia'           => ['Picanto', 'Rio', 'Ceed', 'Sportage', 'Stonic', 'Niro', 'Sorento', 'Venga', 'Soul', 'Xceed'],
            'Peugeot'       => ['107', '108', '206', '207', '208', '308', '2008', '3008', '5008', 'Partner', 'Boxer'],
            'Citroen'       => ['C1', 'C2', 'C3', 'C4', 'C3 Aircross', 'C5 Aircross', 'Berlingo', 'Saxo', 'Xsara', 'Picasso'],
            'Renault'       => ['Twingo', 'Clio', 'Megane', 'Captur', 'Kadjar', 'Scenic', 'Kangoo', 'Laguna', 'Espace'],
            'Seat'          => ['Ibiza', 'Leon', 'Arona', 'Ateca', 'Alhambra', 'Cordoba', 'Toledo'],
            'Skoda'         => ['Citigo', 'Fabia', 'Scala', 'Octavia', 'Superb', 'Kamiq', 'Karoq', 'Kodiaq', 'Rapid', 'Yeti'],
            'Suzuki'        => ['Alto', 'Celerio', 'Ignis', 'Swift', 'Baleno', 'SX4', 'Vitara', 'Jimny'],
            'Honda'         => ['Jazz', 'Civic', 'Accord', 'HR-V', 'CR-V', 'Insight'],
            'Mazda'         => ['2', '3', '6', 'CX-3', 'CX-30', 'CX-5', 'MX-5'],
            'Mitsubishi'    => ['Colt', 'Space Star', 'Lancer', 'ASX', 'Outlander', 'Eclipse Cross', 'L200', 'Pajero'],
            'Mercedes-Benz' => ['A-Class', 'B-Class', 'C-Class', 'E-Class', 'CLA', 'GLA', 'GLC', 'Vito', 'Sprinter', 'ML'],
            'BMW'           => ['1 Series', '2 Series', '3 Series', '4 Series', '5 Series', 'X1', 'X3', 'X5', 'Z4'],
            'Audi'          => ['A1', 'A3', 'A4', 'A5', 'A6', 'Q2', 'Q3', 'Q5', 'TT'],
            'Dacia'         => ['Sandero', 'Logan', 'Duster', 'Lodgy', 'Dokker', 'Spring'],
            'Smart'         => ['ForTwo', 'ForFour'],
            'Mini'          => ['Cooper', 'One', 'Clubman', 'Countryman'],
            'Alfa Romeo'    => ['MiTo', 'Giulietta', '147', '156', '159', 'Giulia', 'Stelvio'],
            'Lancia'        => ['Ypsilon', 'Musa', 'Delta'],
            'Volvo'         => ['V40', 'V60', 'V70', 'S60', 'XC40', 'XC60', 'XC90'],
            'Chevrolet'     => ['Matiz', 'Spark', 'Aveo', 'Kalos', 'Cruze', 'Captiva'],
            'Subaru'        => ['Impreza', 'XV', 'Forester', 'Legacy', 'Outback'],
        ];

        $rows = [];
        foreach ($data as $make => $models) {
            foreach ($models as $model) {
                $rows[] = ['make' => $make, 'model' => $model];
            }
        }

        // Idempotent: καθάρισε & ξαναγέμισε, ώστε re-seed να μη διπλασιάζει.
        DB::table('vehicle_models')->delete();

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('vehicle_models')->insert($chunk);
        }
    }
}
