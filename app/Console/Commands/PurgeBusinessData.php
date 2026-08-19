<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeBusinessData extends Command
{
    protected $signature = 'garage:purge-business-data {--force : Skip confirmation prompt}';

    protected $description = 'Διαγράφει όλα τα επιχειρησιακά δεδομένα (customers, vehicles, work orders κ.λπ.). ΔΕΝ πειράζει users/migrations/sessions.';

    // Σειρά διαγραφής: πρώτα τα child tables, μετά τα parent.
    //
    // Το ΑΑΔΕ outbox/transmissions ΠΡΕΠΕΙ να καθαρίζονται μαζί: τα entries
    // δείχνουν σε work orders μέσω local_entity_id (χωρίς foreign key), οπότε
    // αν μείνουν πίσω, ένα μελλοντικό work order μπορεί να «κληρονομήσει» το
    // dclId ενός διαγραμμένου μέσω OutboxManager::resolveDclId().
    private array $tables = [
        'aade_dcl_transmissions',
        'aade_dcl_outbox',
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

            // ΔΕΝ γίνεται reset των auto-increment counters (sqlite_sequence).
            // Τα ids δεν επιτρέπεται να ξαναχρησιμοποιηθούν: ένα work order id
            // είναι ταυτόχρονα το local_entity_id προς την ΑΑΔΕ, οπότε reused
            // id σημαίνει ότι νέα εντολή μπορεί να συνδεθεί με παλιό dclId.
            // Το AUTOINCREMENT της SQLite κρατά το high-water mark από μόνο του
            // όσο η γραμμή του πίνακα μένει ανέπαφη.

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Σφάλμα κατά τη διαγραφή: '.$e->getMessage());

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
