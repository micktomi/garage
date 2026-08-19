<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optimistic concurrency token for work orders.
 *
 * A counter rather than a timestamp comparison on purpose: updated_at has
 * one-second resolution on every database this app supports, so two edits
 * inside the same second are indistinguishable — and "inside the same second"
 * is exactly the window a lost update lives in.
 *
 * Not nullable and defaulted to 0 so existing rows and rows created outside
 * the guarded forms (Filament create, the AI assistant, seeders) all start
 * from a real value instead of a null that every comparison would have to
 * special-case.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->unsignedInteger('lock_version')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('lock_version');
        });
    }
};
