<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeBusinessData extends Command
{
    protected $signature = 'garage:purge-business-data {--force : Skip confirmation prompt}';

    protected $description = 'Διαγράφει όλα τα επιχειρησιακά δεδομένα (customers, vehicles, work orders κ.λπ.). ΔΕΝ πειράζει users/migrations/sessions.';

    // Σειρά διαγραφής: πρώτα τα child tables, μετά τα parent
    private array $tables = [
        'work_order_parts',
        'work_orders',
        'appointments',
        'vehicles',
        'customers',
        'parts',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=yellow>===== COUNTS ΠΡΙΝ ΤΗ ΔΙΑΓΡΑΦΗ =====</>');
        $this->showCounts();

        $this->newLine();
        $this->line('<fg=red>Θα διαγραφούν ΟΛΑ τα παραπάνω records.</>');
        $this->line('<fg=green>ΔΕΝ πειράζονται: users, sessions, password_reset_tokens, migrations, vehicle_models.</>');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Συνέχεια;', false)) {
            $this->info('Ακυρώθηκε. Καμία αλλαγή.');
            return self::SUCCESS;
        }

        DB::statement('PRAGMA foreign_keys = OFF;');

        try {
            DB::beginTransaction();

            foreach ($this->tables as $table) {
                $deleted = DB::table($table)->delete();
                $this->line("  <fg=cyan>{$table}</>: {$deleted} records διαγράφηκαν");
            }

            // Επαναφορά auto-increment counters (SQLite sqlite_sequence)
            foreach ($this->tables as $table) {
                DB::table('sqlite_sequence')->where('name', $table)->delete();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Σφάλμα κατά τη διαγραφή: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->newLine();
        $this->line('<fg=yellow>===== COUNTS ΜΕΤΑ ΤΗ ΔΙΑΓΡΑΦΗ =====</>');
        $this->showCounts();

        $this->newLine();
        $this->info('Η βάση είναι καθαρή. Users και vehicle_models παρέμειναν αναλλοίωτα.');

        return self::SUCCESS;
    }

    private function showCounts(): void
    {
        $rows = [];
        foreach ($this->tables as $table) {
            $rows[] = [$table, DB::table($table)->count()];
        }
        $this->table(['Table', 'Records'], $rows);
    }
}
