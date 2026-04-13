<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{

    // Rapport journalier (revenus/dépenses) + détails (nature/description via catégorie)
    public function daily(Request $request)
    {
        $userId = auth()->id();

        $from = $request->query('from');
        $to = $request->query('to');

        $fromDate = $from ? \Carbon\Carbon::parse($from)->startOfDay() : \Carbon\Carbon::now()->subDays(29)->startOfDay();
        $toDate = $to ? \Carbon\Carbon::parse($to)->endOfDay() : \Carbon\Carbon::now()->endOfDay();

        $driver = DB::connection()->getDriverName();
        $dayExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', transaction_date)"
            : 'DATE(transaction_date)';

        $summary = Transaction::where('user_id', $userId)
            ->whereBetween('transaction_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->select(
                DB::raw("$dayExpr as day"),
                DB::raw('SUM(CASE WHEN type="revenu" THEN amount ELSE 0 END) as revenus'),
                DB::raw('SUM(CASE WHEN type="depense" THEN amount ELSE 0 END) as depenses')
            )
            ->groupBy(DB::raw($dayExpr))
            ->orderBy('day', 'asc')
            ->get();

        $transactions = Transaction::with(['category:id,name,nature,description'])
            ->where('user_id', $userId)
            ->whereBetween('transaction_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'summary' => $summary,
            'transactions' => $transactions,
        ]);
    }

    // Rapport mensuel
    public function monthly()
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', transaction_date)"
            : 'DATE_FORMAT(transaction_date, "%Y-%m")';

        $report = Transaction::where('user_id', auth()->id())
            ->select(
                DB::raw("$monthExpr as month"),
                DB::raw('SUM(CASE WHEN type="revenu" THEN amount ELSE 0 END) as revenus'),
                DB::raw('SUM(CASE WHEN type="depense" THEN amount ELSE 0 END) as depenses')
            )
            ->groupBy(DB::raw($monthExpr))
            ->orderBy('month', 'asc')
            ->get();

        return response()->json($report);
    }


    // Rapport annuel
    public function yearly()
    {
        $driver = DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite'
            ? "strftime('%Y', transaction_date)"
            : 'YEAR(transaction_date)';

        $report = Transaction::where('user_id', auth()->id())
            ->select(
                DB::raw("$yearExpr as year"),
                DB::raw('SUM(CASE WHEN type="revenu" THEN amount ELSE 0 END) as revenus'),
                DB::raw('SUM(CASE WHEN type="depense" THEN amount ELSE 0 END) as depenses')
            )
            ->groupBy(DB::raw($yearExpr))
            ->orderBy('year', 'asc')
            ->get();

        return response()->json($report);
    }

}