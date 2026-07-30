<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('work_orders')
            ->where('status', 'in_progress')
            ->where('blocking_reason', 'waiting_parts')
            ->update(['status' => 'awaiting_parts']);

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('blocking_reason');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('blocking_reason')->nullable()->after('status');
        });

        DB::table('work_orders')
            ->where('status', 'awaiting_parts')
            ->update([
                'status' => 'in_progress',
                'blocking_reason' => 'waiting_parts',
            ]);
    }
};
