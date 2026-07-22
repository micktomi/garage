<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = config('admin.email');
        $adminPassword = config('admin.password');
        $usedRandomPassword = blank($adminPassword);

        if ($usedRandomPassword) {
            $adminPassword = Str::password(24);
        }

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            ['name' => 'Διαχειριστής', 'password' => Hash::make($adminPassword)]
        );

        if ($usedRandomPassword && $admin->wasRecentlyCreated) {
            $this->command?->warn("ADMIN_PASSWORD is not set. Generated password for the newly created admin: {$adminPassword}");
        }

        $this->call([
            VehicleModelSeeder::class,
            PartSeeder::class,
        ]);
    }
}
