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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
$table->foreignId('customer_id')->constrained()->cascadeOnDelete();
$table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
$table->text('problem_description');
$table->text('diagnosis')->nullable();
$table->text('work_performed')->nullable();
$table->decimal('labor_cost', 10, 2)->default(0);
$table->decimal('parts_cost', 10, 2)->default(0);
$table->decimal('total_cost', 10, 2)->default(0);
$table->string('status')->default('new');
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
