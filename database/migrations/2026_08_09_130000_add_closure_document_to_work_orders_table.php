<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a work order was closed (needed for the ΑΑΔΕ UpdateClient
 * entryCompletion call): what document was issued, or — if none — why not.
 * Both nullable: existing/open orders have neither yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('closure_document')->nullable()->after('checked_out_at');
            $table->string('non_issue_reason')->nullable()->after('closure_document');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['closure_document', 'non_issue_reason']);
        });
    }
};
