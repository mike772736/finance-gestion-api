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
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Indispensable pour Auth::id()
        $table->string('name');
        $table->string('nature')->nullable(); // Pour "Essentiel", "Loisir", etc.
        $table->text('description')->nullable();
        $table->decimal('budget_amount', 15, 2)->default(0); // Le montant du budget !
        $table->enum('type', ['revenu', 'depense'])->default('depense'); 
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
