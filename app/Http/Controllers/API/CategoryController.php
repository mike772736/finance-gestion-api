<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth; // Ajouté pour identifier l'utilisateur

class CategoryController extends Controller
{
    public function index()
    {
        // On remplace Category::all() par le filtre user_id
        $categories = Category::where('user_id', Auth::id())->get();        
        
        return response()->json($categories->map(function($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'nature' => $category->nature,
                'description' => $category->description,
                'budget_amount' => $category->budget_amount,
                'spent_this_month' => $category->getSpentThisMonth(),
                'remaining_budget' => $category->getRemainingBudget(),
                'percentage_spent' => $category->getPercentageSpent(),
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at
            ];
        }));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nature' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'budget_amount' => 'nullable|numeric|min:0'
        ]);

        // On injecte l'user_id de l'utilisateur connecté
        $validated['user_id'] = Auth::id();

        $category = Category::create($validated);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'nature' => $category->nature,
            'description' => $category->description,
            'budget_amount' => $category->budget_amount,
            'spent_this_month' => 0,
            'remaining_budget' => $category->budget_amount,
            'percentage_spent' => 0,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nature' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'budget_amount' => 'nullable|numeric|min:0'
        ]);

        // On cherche la catégorie appartenant à l'utilisateur
        $category = Category::where('user_id', Auth::id())->find($id);
        
        if (!$category) {
            return response()->json(['error' => 'Catégorie non trouvée'], 404);
        }

        $category->update($validated);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'nature' => $category->nature,
            'description' => $category->description,
            'budget_amount' => $category->budget_amount,
            'spent_this_month' => $category->getSpentThisMonth(),
            'remaining_budget' => $category->getRemainingBudget(),
            'percentage_spent' => $category->getPercentageSpent(),
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at
        ]);
    }

    public function destroy($id)
    {
        // Sécurité : on ne peut supprimer que ses propres catégories
        $category = Category::where('user_id', Auth::id())->find($id);
        
        if (!$category) {
            return response()->json(['error' => 'Catégorie non trouvée'], 404);
        }

        $category->delete();

        return response()->json([
            "message" => "Catégorie supprimée"
        ]);
    }

    public function summary()
    {
        // On remplace Category::all() par le filtre user_id
        $categories = Category::where('user_id', Auth::id())->get();
        
        $totalBudget = $categories->sum('budget_amount');
        $totalSpent = 0;
        
        $budgetData = $categories->map(function($category) use (&$totalSpent) {
            $spent = $category->getSpentThisMonth();
            $totalSpent += $spent;
            
            return [
                'name' => $category->name,
                'nature' => $category->nature,
                'budget_amount' => $category->budget_amount,
                'spent_this_month' => $spent,
                'remaining_budget' => $category->getRemainingBudget(),
                'percentage_spent' => $category->getPercentageSpent(),
                'status' => $spent > $category->budget_amount ? 'exceeded' : 'on_track'
            ];
        });

        return response()->json([
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_remaining' => max(0, $totalBudget - $totalSpent),
            'overall_percentage' => $totalBudget > 0 ? min(100, ($totalSpent / $totalBudget) * 100) : 0,
            'budgets' => $budgetData
        ]);
    }
}