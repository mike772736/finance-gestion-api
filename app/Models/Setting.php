<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
   protected $fillable = [
       'user_id',
       'currency',
       'language',
       'theme',
       'notifications',
       'monthly_budget',
       'budget_alert_enabled',
       'low_balance_threshold',
       'low_balance_alert_enabled',
       'due_soon_days',
       'due_soon_alert_enabled',
   ];

   protected $casts = [
       'notifications' => 'boolean',
       'budget_alert_enabled' => 'boolean',
       'low_balance_alert_enabled' => 'boolean',
       'due_soon_alert_enabled' => 'boolean',
   ];
}
