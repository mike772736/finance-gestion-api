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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('budget_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->decimal('budget_impact', 10, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'budget_id')) {
                $table->dropForeign(['budget_id']);
                $table->dropColumn('budget_id');
            }
            if (Schema::hasColumn('transactions', 'budget_impact')) {
                $table->dropColumn('budget_impact');
            }
        });
    }
};
