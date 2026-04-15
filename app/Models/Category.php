<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    protected $fillable = [
        'name',
        'nature',
        'description',
        'budget_amount', // On garde uniquement celui-là
        'user_id'
    ];

    // On s'assure que budget_amount est toujours traité comme un nombre
    protected $casts = [
        'budget_amount' => 'float',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

   public function getSpentThisMonth()
{
    try {
        $start = now()->startOfMonth()->format('Y-m-d');
        $end = now()->endOfMonth()->format('Y-m-d');

        // On vérifie si la table et les colonnes existent pour éviter la 500
        return \App\Models\Transaction::where('user_id', $this->user_id)
            ->where('type', 'depense')
            ->whereBetween('transaction_date', [$start, $end])
            ->where(function ($query) {
                $query->where('category_id', $this->id)
                      ->orWhere('budget_id', $this->id);
            })
            ->sum('amount') ?? 0;
    } catch (\Exception $e) {
        // Si ça plante (ex: colonne manquante), on renvoie 0 au lieu d'une erreur 500
        \Illuminate\Support\Facades\Log::error("Erreur budget : " . $e->getMessage());
        return 0;
    }
}

    public function getRemainingBudget()
    {
        $spent = $this->getSpentThisMonth();
        $total = (float) ($this->budget_amount ?? 0);
        return max(0, $total - $spent);
    }

    public function getPercentageSpent()
    {
        $total = (float) ($this->budget_amount ?? 0);
        if ($total <= 0) {
            return 0;
        }
        $spent = $this->getSpentThisMonth();
        // On ne met pas de min(100) ici car le Front-end gère les dépassements (couleur rouge)
        return ($spent / $total) * 100;
    }
}