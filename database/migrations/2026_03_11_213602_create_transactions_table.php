<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
        
        // C'est cette ligne qui manquait pour lier les dépenses aux budgets !
        $table->foreignId('budget_id')->nullable()->constrained('categories')->onDelete('set null');
        
        $table->string('description');
        $table->decimal('amount', 15, 2);
        $table->decimal('budget_impact', 15, 2)->default(0); // Requis par ton addTransaction
        $table->enum('type', ['revenu', 'depense', 'dette']);
        $table->date('transaction_date');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
