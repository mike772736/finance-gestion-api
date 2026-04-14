<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'nature',
        'description',
        'budget_amount',
        'budget',
        'user_id'
        
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Calcule le montant dépensé dans cette catégorie pour le mois en cours
     * Seules les transactions de type "depense" sont comptées
     */
   public function getSpentThisMonth()
{
    // On force le format Y-m-d pour que PostgreSQL ne soit pas perdu
    $start = now()->startOfMonth()->format('Y-m-d');
    $end = now()->endOfMonth()->format('Y-m-d');

    return \App\Models\Transaction::where('user_id', $this->user_id)
        ->where('type', 'depense')
        // On utilise explicitement les dates au format string Y-m-d
        ->whereBetween('transaction_date', [$start, $end])
        ->where(function ($query) {
            $query->where('category_id', $this->id)
                  ->orWhere('budget_id', $this->id);
        })
        ->sum('amount');
}

    /**
     * Calcule le reste du budget pour le mois
     */
    public function getRemainingBudget()
    {
        return max(0, ($this->budget_amount ?? 0) - $this->getSpentThisMonth());
    }

    /**
     * Calcule le pourcentage du budget dépensé
     */
    public function getPercentageSpent()
    {
        if (($this->budget_amount ?? 0) == 0) {
            return 0;
        }
        return min(100, ($this->getSpentThisMonth() / $this->budget_amount) * 100);
    }
}
