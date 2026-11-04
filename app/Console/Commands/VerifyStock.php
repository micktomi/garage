<?php

namespace App\Console\Commands;

use App\Models\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyStock extends Command
{
    protected $signature = 'stock:verify';

    protected $description = 'Συγκρίνει το απόθεμα κάθε ανταλλακτικού με το άθροισμα των κινήσεών του και αναφέρει αποκλίσεις.';

    public function handle(): int
    {
        $ledger = DB::table('stock_movements')
            ->selectRaw('part_id, SUM(quantity) as total')
            ->groupBy('part_id')
            ->pluck('total', 'part_id');

        $rows = Part::withTrashed()
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'quantity', 'deleted_at'])
            ->map(function (Part $part) use ($ledger): array {
                $stock = round((float) $part->quantity, 1);
                $movements = round((float) ($ledger[$part->id] ?? 0), 1);

                return [$part->id, $part->code, $part->name.($part->trashed() ? ' (αρχειοθ.)' : ''), $stock, $movements, round($stock - $movements, 1)];
            })
            ->filter(fn (array $row): bool => abs($row[5]) >= 0.05)
            ->values();

        $orphans = $ledger->keys()->diff(Part::withTrashed()->pluck('id'));

        if ($rows->isEmpty() && $orphans->isEmpty()) {
            $this->info('Καμία απόκλιση: το απόθεμα κάθε ανταλλακτικού ισούται με το άθροισμα των κινήσεών του.');

            return self::SUCCESS;
        }

        if ($rows->isNotEmpty()) {
            $this->error("Αποκλίσεις σε {$rows->count()} ανταλλακτικά:");
            $this->table(['ID', 'Κωδικός', 'Όνομα', 'Απόθεμα', 'Άθροισμα κινήσεων', 'Διαφορά'], $rows->all());
        }

        if ($orphans->isNotEmpty()) {
            $this->error('Κινήσεις για ανύπαρκτα ανταλλακτικά: '.$orphans->implode(', '));
        }

        return self::FAILURE;
    }
}
