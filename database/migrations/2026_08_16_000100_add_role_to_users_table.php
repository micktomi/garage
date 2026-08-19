<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal role column — no packages, no pivot tables, no permission rows.
 *
 * Defaults to owner on purpose: every account that exists today was created
 * when "logged in" meant "can do everything", so demoting them silently would
 * break the running single-owner installation. New staff accounts are an
 * explicit act.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default(UserRole::Owner->value)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
