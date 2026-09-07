<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('afm', 20)->nullable()->unique()->after('full_name');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('fuel', 50)->nullable()->after('model');
            $table->unsignedInteger('engine_cc')->nullable()->after('fuel');
            $table->date('first_registered_at')->nullable()->after('engine_cc');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['afm']);
            $table->dropColumn('afm');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['fuel', 'engine_cc', 'first_registered_at']);
        });
    }
};
