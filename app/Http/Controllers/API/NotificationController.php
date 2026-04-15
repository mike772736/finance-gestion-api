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

        // On s'assure que les réglages existent
        $settings = Setting::firstOrCreate(
            ['user_id' => $userId],
            [
                'currency' => 'FCFA',
                'language' => 'fr',
                'theme' => 'light',
                'notifications' => true,
                'monthly_budget' => 0,
                'budget_alert_enabled' => true,
                'low_balance_threshold' => 0,
                'low_balance_alert_enabled' => true,
                'due_soon_days' => 7,
                'due_soon_alert_enabled' => true,
            ]
        );

        // On entoure les alertes par un try/catch pour que si une échoue, 
        // l'utilisateur reçoive quand même ses notifications existantes.
        try {
            $this->createBudgetAlert($userId, $settings);
            $this->createCategoryBudgetAlerts($userId, $settings);
            $this->createLowBalanceAlert($userId, $settings);
            $this->createDueSoonDebtAlerts($userId, $settings);
            $this->createOverdueDebtAlerts($userId);
        } catch (\Exception $e) {
            // Log l'erreur en silence pour ne pas casser l'API
        }

        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function createBudgetAlert(int $userId, Setting $settings)
    {
        $budget = (float)($settings->monthly_budget ?? 0);
        if (!$settings->budget_alert_enabled || $budget <= 0) return;

        $expenses = (float) Transaction::where('user_id', $userId)
            ->where('type', 'depense')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        if ($expenses > $budget) {
            $message = "📌 Budget mensuel dépassé : " . number_format($expenses, 0, ',', ' ') . " FCFA dépensés.";
            $this->createOnce($userId, $message);
        }
    }

    private function createCategoryBudgetAlerts(int $userId, Setting $settings)
    {
        if (!$settings->budget_alert_enabled) return;

        $categories = Category::where('user_id', $userId)
            ->where('budget_amount', '>', 0) 
            ->get();

        foreach ($categories as $category) {
            $limit = (float) $category->budget_amount;
            $spent = (float) Transaction::where('user_id', $userId)
                ->where('category_id', $category->id)
                ->where('type', 'depense')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('amount');

            if ($spent > $limit) {
                $message = "⚠ Budget catégorie dépassé (" . $category->name . ") : " . number_format($spent, 0, ',', ' ') . " FCFA.";
                $this->createOnce($userId, $message);
            }
        }
    }

    private function createLowBalanceAlert(int $userId, Setting $settings)
    {
        $threshold = (float)($settings->low_balance_threshold ?? 0);
        if (!$settings->low_balance_alert_enabled || $threshold <= 0) return;

        $revenues = (float) Transaction::where('user_id', $userId)->where('type', 'revenu')->sum('amount');
        $expenses = (float) Transaction::where('user_id', $userId)->where('type', 'depense')->sum('amount');
        $balance = $revenues - $expenses;

        if ($balance < $threshold) {
            $message = "⚠ Solde faible : votre trésorerie est de " . number_format($balance, 0, ',', ' ') . " FCFA.";
            $this->createOnce($userId, $message);
        }
    }

    private function createDueSoonDebtAlerts(int $userId, Setting $settings)
    {
        if (!$settings->due_soon_alert_enabled || !$settings->due_soon_days) return;

        $today = Carbon::today();
        $dueMax = Carbon::today()->addDays((int)$settings->due_soon_days);

        $dueSoon = Debt::where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->whereBetween('due_date', [$today, $dueMax])
            ->get();

        foreach ($dueSoon as $debt) {
            $message = "⚠ Dette à venir : " . $debt->creditor_name . " due le " . $debt->due_date->format('d/m/Y');
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
            $message = "⏰ Dette en retard : " . $debt->creditor_name . " était due le " . $debt->due_date->format('d/m/Y');
            $this->createOnce($userId, $message);
        }
    }

    private function createOnce(int $userId, string $message)
    {
        $exists = Notification::where('user_id', $userId)
            ->where('message', $message)
            ->where('created_at', '>', now()->subHours(24))
            ->exists();

        if (!$exists) {
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

    public function destroy($id)
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->delete();
        return response()->json(["message" => "Notification supprimée"]);
    }
}