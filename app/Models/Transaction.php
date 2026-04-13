<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'budget_id',
        'description',
        'amount',
        'type',
        'transaction_date',
        'budget_impact'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'budget_impact' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    protected static function booted()
    {
        // IMPORTANT: Les dépenses ne modifient PAS le budget_amount
        // Elles sont simplement enregistrées et comptées dans getSpentThisMonth()
        // Le reste du budget = budget_amount - spent_this_month (calculé dynamiquement)
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function budget()
    {
        return $this->belongsTo(Category::class, 'budget_id');
    }
}
