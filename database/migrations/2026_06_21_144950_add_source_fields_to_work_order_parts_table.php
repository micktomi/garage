<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_parts', function (Blueprint $table) {
            $table->string('source')->default('from_stock')->after('part_id');
            $table->string('description')->nullable()->after('source');
            $table->decimal('unit_cost', 10, 2)->nullable()->after('description');
            $table->text('note')->nullable()->after('line_total');
        });

        // Make part_id nullable (existing rows already have values, safe to alter)
        Schema::table('work_order_parts', function (Blueprint $table) {
            $table->foreignId('part_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_order_parts', function (Blueprint $table) {
            $table->dropColumn(['source', 'description', 'unit_cost', 'note']);
            $table->foreignId('part_id')->nullable(false)->change();
        });
    }
};
