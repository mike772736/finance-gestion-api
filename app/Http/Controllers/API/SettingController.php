<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{

    public function index()
    {
        $setting = Setting::where('user_id', auth()->id())->first();
        
        if (!$setting) {
            $setting = Setting::create([
                'user_id' => auth()->id(),
                'currency' => 'FCFA',
                'language' => 'fr',
                'theme' => 'light',
                'notifications' => true
            ]);
        }

        return response()->json($setting);
    }

    public function update(Request $request)
{
    // 1. Validation habituelle
    $request->validate([
        'monthly_budget' => 'nullable|numeric|min:0',
        'low_balance_threshold' => 'nullable|numeric|min:0',
        // ... tes autres validations
    ]);

    // 2. Récupération des données
    $data = $request->all();

    // 3. LA CORRECTION : Transformation des chaînes vides en NULL
    // On vérifie si la clé existe et si elle est strictement égale à une chaîne vide
    if (array_key_exists('monthly_budget', $data) && $data['monthly_budget'] === '') {
        $data['monthly_budget'] = null;
    }

    if (array_key_exists('low_balance_threshold', $data) && $data['low_balance_threshold'] === '') {
        $data['low_balance_threshold'] = null;
    }

    // 4. Mise à jour sécurisée
    $setting = Setting::where('user_id', auth()->id())->firstOrFail();
    $setting->update($data);

    return response()->json($setting);
}
}