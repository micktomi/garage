<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
$table->foreignId('customer_id')->constrained()->cascadeOnDelete();
$table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
$table->dateTime('appointment_date');
$table->text('description')->nullable();
$table->string('status')->default('scheduled');
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
