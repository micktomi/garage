<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payments against work orders (no VAT, no documents, no numbering) and the
 * moment an order was completed, which is when its balance becomes a debt.
 *
 * completed_at backfill for already completed orders: checked_out_at when
 * present (set when the order closed), otherwise updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('checked_out_at');
        });

        DB::table('work_orders')
            ->where('status', 'completed')
            ->update(['completed_at' => DB::raw('COALESCE(checked_out_at, updated_at)')]);

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 20);
            $table->timestamp('paid_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
