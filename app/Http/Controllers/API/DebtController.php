<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Debt;

class DebtController extends Controller
{
    public function index()
    {
        return Debt::where('user_id', auth()->id())->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'creditor_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,paid,overdue'
        ]);

        $debt = Debt::create([
            'user_id' => auth()->id(),
            'creditor_name' => $request->creditor_name,
            'amount' => $request->amount,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'status' => $request->status ?? 'pending',
        ]);

        return response()->json($debt, 201);
    }

    public function show($id)
    {
        $debt = Debt::where('user_id', auth()->id())->findOrFail($id);
        return response()->json($debt);
    }

    public function update(Request $request, $id)
    {
        $debt = Debt::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'creditor_name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,paid,overdue'
        ]);

        $debt->update($request->only(['creditor_name', 'amount', 'description', 'due_date', 'status']));

        return response()->json($debt);
    }

    public function destroy($id)
    {
        $debt = Debt::where('user_id', auth()->id())->findOrFail($id);
        $debt->delete();

        return response()->json(["message" => "Dette supprimée"]);
    }
}
