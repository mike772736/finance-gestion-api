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
            $table->decimal('monthly_budget', 12, 2)->nullable()->after('notifications');
            $table->boolean('budget_alert_enabled')->default(true)->after('monthly_budget');
            $table->decimal('low_balance_threshold', 12, 2)->nullable()->after('budget_alert_enabled');
            $table->boolean('low_balance_alert_enabled')->default(true)->after('low_balance_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_budget',
                'budget_alert_enabled',
                'low_balance_threshold',
                'low_balance_alert_enabled',
            ]);
        });
    }
};
