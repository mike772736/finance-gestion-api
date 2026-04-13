<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;


class NotificationController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $settings = Setting::firstOrCreate(
            ['user_id' => $userId],
            [
                'currency' => 'FCFA',
                'language' => 'fr',
                'theme' => 'light',
                'notifications' => true,
                'monthly_budget' => null,
                'budget_alert_enabled' => true,
                'low_balance_threshold' => null,
                'low_balance_alert_enabled' => true,
                'due_soon_days' => 7,
                'due_soon_alert_enabled' => true,
            ]
        );

        // Générer des alertes à chaque appel si nécessaire
        $this->createBudgetAlert($userId, $settings);
        $this->createCategoryBudgetAlerts($userId, $settings);
        $this->createLowBalanceAlert($userId, $settings);
        $this->createDueSoonDebtAlerts($userId, $settings);
        $this->createOverdueDebtAlerts($userId);

        return Notification::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }

    private function createBudgetAlert(int $userId, Setting $settings)
    {
        if (! $settings->budget_alert_enabled || ! $settings->monthly_budget) {
            return;
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $expenses = Transaction::where('user_id', $userId)
            ->where('type', 'depense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        if ($expenses > $settings->monthly_budget) {
            $message = "📌 Budget dépassé : vous avez dépensé " . number_format($expenses, 2, ',', ' ') . " FCFA ce mois-ci (budget : " . number_format($settings->monthly_budget, 2, ',', ' ') . " FCFA).";
            $this->createOnce($userId, $message);
        }
    }

    private function createCategoryBudgetAlerts(int $userId, Setting $settings)
{
    if (! $settings->budget_alert_enabled) {
        return;
    }

    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();

    // 1. SECURITÉ : On ne récupère que les catégories de l'utilisateur connecté (Mike)
    // 2. FILTRE : On ne prend que celles qui ont un budget défini (budget_amount > 0)
    $categories = Category::where('user_id', $userId)
        ->where('budget_amount', '>', 0) 
        ->get();

    foreach ($categories as $category) {
        // On calcule les dépenses pour CETTE catégorie précise
        $expenses = Transaction::where('user_id', $userId)
            ->where('category_id', $category->id)
            ->where('type', 'depense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // On compare avec 'budget_amount' (le nom exact de ta colonne SQL)
        if ($expenses > $category->budget_amount) {
            $message = "⚠ Budget catégorie dépassé (" . $category->name . ") : " . 
                       number_format($expenses, 0, ',', ' ') . " FCFA (budget : " . 
                       number_format($category->budget_amount, 0, ',', ' ') . " FCFA).";
            
            $this->createOnce($userId, $message);
        }
    }
}

    private function createLowBalanceAlert(int $userId, Setting $settings)
    {
        if (! $settings->low_balance_alert_enabled || ! $settings->low_balance_threshold) {
            return;
        }

        // Calcul du solde courant
        $revenues = Transaction::where('user_id', $userId)->where('type', 'revenu')->sum('amount');
        $expenses = Transaction::where('user_id', $userId)->where('type', 'depense')->sum('amount');
        $balance = $revenues - $expenses;

        if ($balance < $settings->low_balance_threshold) {
            $message = "⚠ Solde faible : votre trésorerie est de " . number_format($balance, 2, ',', ' ') . " FCFA (seuil : " . number_format($settings->low_balance_threshold, 2, ',', ' ') . " FCFA).";
            $this->createOnce($userId, $message);
        }
    }

    private function createDueSoonDebtAlerts(int $userId, Setting $settings)
    {
        if (! $settings->due_soon_alert_enabled || ! $settings->due_soon_days) {
            return;
        }

        $today = Carbon::today();
        $dueMax = Carbon::today()->addDays($settings->due_soon_days);

        $dueSoon = Debt::where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->whereBetween('due_date', [$today, $dueMax])
            ->get();

        foreach ($dueSoon as $debt) {
            $message = "⚠ Dette à venir : " . $debt->creditor_name . " (" . number_format($debt->amount, 2, ',', ' ') . " FCFA) due le " . $debt->due_date->format('d/m/Y') . ".";
            $this->createOnce($userId, $message);
        }
    }

    private function createOverdueDebtAlerts(int $userId)
    {
        $overdue = Debt::where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', Carbon::today())
            ->get();

        foreach ($overdue as $debt) {
            $message = "⏰ Dette en retard : " . $debt->creditor_name . " (" . number_format($debt->amount, 2, ',', ' ') . " FCFA) due le " . $debt->due_date->format('d/m/Y') . ".";
            $this->createOnce($userId, $message);
        }
    }

    private function createOnce(int $userId, string $message)
    {
        $exists = Notification::where('user_id', $userId)
            ->where('message', $message)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->exists();

        if (! $exists) {
            Notification::create([
                'user_id' => $userId,
                'message' => $message,
            ]);
        }
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['read' => true]);
        return response()->json($notification);
    }

    public function destroy( $id)
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->delete();
        return response()->json(["message" => "Notification supprimée"]);
    }
    private function createSpendingTrendAlert(int $userId)
{
    $lastWeek = Transaction::where('user_id', $userId)
        ->where('type', 'depense')
        ->whereBetween('transaction_date', [now()->subDays(14), now()->subDays(7)])
        ->sum('amount');

    $thisWeek = Transaction::where('user_id', $userId)
        ->where('type', 'depense')
        ->whereBetween('transaction_date', [now()->subDays(7), now()])
        ->sum('amount');

    if ($thisWeek > $lastWeek * 1.5) { 
        $diff = number_format($thisWeek - $lastWeek, 0, ',', ' ');
        $message = "💡 Analyse : Vos dépenses ont augmenté de {$diff} FCFA par rapport à la semaine dernière. Pensez à vérifier vos sorties récentes.";
        $this->createOnce($userId, $message);
    }
}
}
