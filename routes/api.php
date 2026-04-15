<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\DebtController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


/*
|--------------------------------------------------------------------------
| AUTHENTIFICATION
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



/*
|--------------------------------------------------------------------------
| ROUTES PROTEGEES (UTILISATEUR CONNECTÉ)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    */

    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user', [UserController::class, 'update']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);

    /*
    |--------------------------------------------------------------------------
    | TRANSACTIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);


    /*
    |--------------------------------------------------------------------------
    | CATEGORIES
    |--------------------------------------------------------------------------
    */

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::get('/categories/summary/budgets', [CategoryController::class, 'summary']);


    /*
    |--------------------------------------------------------------------------
    | RAPPORTS
    |--------------------------------------------------------------------------
    */

    Route::get('/reports/daily', [ReportController::class, 'daily']);
    Route::get('/reports/monthly', [ReportController::class, 'monthly']);
    Route::get('/reports/yearly', [ReportController::class, 'yearly']);


    /*
    |--------------------------------------------------------------------------
    | DETTES
    |--------------------------------------------------------------------------
    */

    Route::get('/debts', [DebtController::class, 'index']);
    Route::post('/debts', [DebtController::class, 'store']);
    Route::get('/debts/{id}', [DebtController::class, 'show']);
    Route::put('/debts/{id}', [DebtController::class, 'update']);
    Route::delete('/debts/{id}', [DebtController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | PARAMETRES
    |--------------------------------------------------------------------------
    */

    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);

// Cette route va nettoyer ta base de données quand tu la visiteras
    Route::get('/nettoyage-ultime', function () {
    try {
        // 1. Désactiver les contraintes de clés étrangères pour PostgreSQL
        DB::statement('SET CONSTRAINTS ALL DEFERRED'); 
        // Ou plus radical pour Postgres :
        $tables = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
        foreach ($tables as $table) {
            DB::statement('DROP TABLE IF EXISTS ' . $table->tablename . ' CASCADE');
        }

        // 2. Relancer les migrations proprement
        Artisan::call('migrate', ['--force' => true]);

        return "Destruction et reconstruction réussies ! Ta base est 100% propre.";
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return "Erreur lors du nettoyage : " . $e->getMessage();
    }
    });

});