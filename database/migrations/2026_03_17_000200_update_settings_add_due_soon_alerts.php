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
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('due_soon_days')->default(7)->after('low_balance_alert_enabled');
            $table->boolean('due_soon_alert_enabled')->default(true)->after('due_soon_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['due_soon_days', 'due_soon_alert_enabled']);
        });
    }
};
