<?php

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separates "the order is open" from "the car is physically here".
 *
 * An open order whose vehicle has left the workshop — the usual case while a
 * part is on order — keeps its status but drops out of the shop-floor view.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->boolean('in_shop')->default(true)->after('status');
            $table->timestamp('checked_in_at')->nullable()->after('in_shop');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
        });

        // Every existing order was created with the vehicle on site, so the
        // creation timestamp is the only honest check-in time available.
        DB::table('work_orders')->update([
            'checked_in_at' => DB::raw('created_at'),
        ]);

        DB::table('work_orders')
            ->whereIn('status', WorkOrderStatus::openValues())
            ->update(['in_shop' => true]);

        // Delivered and cancelled orders left the workshop when they closed.
        DB::table('work_orders')
            ->whereNotIn('status', WorkOrderStatus::openValues())
            ->update([
                'in_shop' => false,
                'checked_out_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['in_shop', 'checked_in_at', 'checked_out_at']);
        });
    }
};
