<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Delete" on these records becomes archive. ADD COLUMN needs no table
 * rebuild on SQLite, so existing rows are untouched (deleted_at = NULL).
 */
return new class extends Migration
{
    private const TABLES = ['customers', 'vehicles', 'work_orders', 'parts'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
