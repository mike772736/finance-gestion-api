<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use Illuminate\Support\Facades\Artisan;

Route::get('/init-db', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return "✅ Base de données migrée avec succès !";
    } catch (\Exception $e) {
        return "❌ Erreur : " . $e->getMessage();
    }
});