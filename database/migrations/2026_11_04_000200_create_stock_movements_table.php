<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The stock ledger. parts.quantity stays the fast "how many now" column;
 * from here on it must always equal the sum of its movements
 * (php artisan stock:verify).
 *
 * Existing stock has no history, so every part (archived ones included) with
 * a non-zero quantity gets one opening "adjust" movement for that quantity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 10, 1);
            $table->string('type', 20);
            $table->foreignId('work_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['part_id', 'id']);
        });

        $now = now();

        DB::table('parts')
            ->where('quantity', '!=', 0)
            ->orderBy('id')
            ->get(['id', 'quantity'])
            ->each(fn ($part) => DB::table('stock_movements')->insert([
                'part_id' => $part->id,
                'quantity' => $part->quantity,
                'type' => 'adjust',
                'note' => 'Αρχικό απόθεμα (έναρξη καταγραφής κινήσεων)',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
