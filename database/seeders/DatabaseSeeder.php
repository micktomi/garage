<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@garage.local'],
            ['name' => 'Διαχειριστής', 'password' => bcrypt('password')]
        );

        $this->call([
            VehicleModelSeeder::class,
            PartSeeder::class,
        ]);
    }
}
