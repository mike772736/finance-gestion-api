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
        Schema::table('categories', function (Blueprint $table) {
            // Drop the type column if it exists
            if (Schema::hasColumn('categories', 'type')) {
                $table->dropColumn('type');
            }
            
            // Add new budget-related columns if they don't exist
            if (!Schema::hasColumn('categories', 'nature')) {
                $table->string('nature')->nullable()->comment('Nature du budget (ex: Alimentation, Transport)');
            }
            if (!Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable()->comment('Description détaillée du budget');
            }
            if (!Schema::hasColumn('categories', 'budget_amount')) {
                $table->decimal('budget_amount', 15, 2)->default(0)->comment('Montant mensuel du budget');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Add back the type column if needed
            if (!Schema::hasColumn('categories', 'type')) {
                $table->enum('type', ['revenu', 'depense'])->nullable();
            }
            
            // Remove the new columns if they exist
            if (Schema::hasColumn('categories', 'nature')) {
                $table->dropColumn('nature');
            }
            if (Schema::hasColumn('categories', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('categories', 'budget_amount')) {
                $table->dropColumn('budget_amount');
            }
            
        });
    }
};
