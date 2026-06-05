<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->unsignedInteger('current_mileage')->nullable()->after('work_performed');
            $table->date('next_service_date')->nullable()->after('current_mileage');
            $table->unsignedInteger('next_service_mileage')->nullable()->after('next_service_date');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn([
                'current_mileage',
                'next_service_date',
                'next_service_mileage',
            ]);
        });
    }
};
