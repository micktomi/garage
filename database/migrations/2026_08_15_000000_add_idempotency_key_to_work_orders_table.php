<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replay guard for the /workshop create form. A double-clicked or re-POSTed
 * submission would otherwise produce a second work order — and therefore a
 * second SendClient, since the ΑΑΔΕ outbox keys idempotency on the work order
 * id and correctly treats two ids as two submissions.
 *
 * Nullable: work orders created outside that form (Filament, the AI assistant,
 * seeders) carry no token, and every database this app supports allows
 * repeated NULLs in a unique index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
