<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('quantity', 10, 1)->default(0)->change();
        });

        Schema::table('work_order_parts', function (Blueprint $table) {
            $table->decimal('quantity', 10, 1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_order_parts', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });
    }
};
