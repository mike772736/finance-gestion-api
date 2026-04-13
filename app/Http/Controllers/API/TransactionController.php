<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Transaction;

class TransactionController extends Controller
{

    public function index()
    {
        return Transaction::where('user_id', auth()->id())->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:revenu,depense,dette',
            'category_id' => 'nullable|exists:categories,id',
            'budget_id' => 'nullable|exists:categories,id',
            'transaction_date' => 'nullable|date',
        ]);

        $transactionData = [
            'user_id' => auth()->id(),
            'category_id' => $request->category_id,
            'description' => $request->description,
            'amount' => $request->amount,
            'type' => $request->type,
            'transaction_date' => $request->transaction_date ?? now()->toDateString()
        ];

        if (Schema::hasColumn('transactions', 'budget_id')) {
            $transactionData['budget_id'] = $request->budget_id;
        }

        $transaction = Transaction::create($transactionData);

        return response()->json($transaction, 201);
    }

    public function show($id)
    {
        $transaction = Transaction::where('user_id', auth()->id())->findOrFail($id);
        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'type' => 'sometimes|in:revenu,depense,dette',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'budget_id' => 'sometimes|nullable|exists:categories,id',
        ]);

        $updatable = ['description', 'amount', 'type', 'category_id', 'transaction_date'];
        if (Schema::hasColumn('transactions', 'budget_id')) {
            $updatable[] = 'budget_id';
        }

        $transaction->update($request->only($updatable));

        return response()->json($transaction);
    }

    public function destroy($id)
    {
        $transaction = Transaction::where('user_id', auth()->id())->findOrFail($id);
        $transaction->delete();

        return response()->json([
            "message" => "Transaction supprimée"
        ]);
    }
}