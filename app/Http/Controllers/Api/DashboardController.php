<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Models\Mitra;
use App\Models\Transaction;
use App\Models\Topup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Dashboard Admin
     */
    public function admin(Request $request)
    {
        $today = today();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $data = [
            'total_transactions_today' => Transaction::whereDate('created_at', $today)->count(),
            'total_transactions_month' => Transaction::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
            'total_deposit' => Topup::where('status', 'success')->sum('amount'),
            'total_fee_mitra' => DB::table('transaction_fees')->sum('fee_amount'),
            'chart_transactions' => Transaction::selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'recent_activities' => collect()
                ->merge(Transaction::with(['mitra', 'user'])->latest()->limit(10)->get()->map(function($t) {
                    return [
                        'id' => 'trx-' . $t->id,
                        'type' => 'transaction',
                        'trx_code' => $t->trx_code,
                        'route' => $t->route,
                        'amount' => $t->amount,
                        'status' => $t->status,
                        'created_at' => $t->created_at,
                    ];
                }))
                ->merge(Topup::with(['mitra'])->latest()->limit(10)->get()->map(function($t) {
                    return [
                        'id' => 'topup-' . $t->id,
                        'type' => 'topup',
                        'trx_code' => 'TOPUP-' . $t->id,
                        'route' => 'Top Up - ' . ($t->mitra->name ?? '-'),
                        'amount' => $t->amount,
                        'status' => $t->status,
                        'created_at' => $t->created_at,
                    ];
                }))
                ->sortByDesc('created_at')
                ->take(10)
                ->values(),
        ];

        return $this->successResponse($data, 'Admin dashboard data retrieved');
    }

    /**
     * Dashboard Mitra
     */
    public function mitra(Request $request)
    {
        $user = $request->user();
        $mitra = $user->mitra;

        if (!$mitra) {
            return $this->errorResponse('Mitra not found', null, 404);
        }

        $data = [
            'balance' => $mitra->balance,
            'total_transactions' => Transaction::where('mitra_id', $mitra->id)->count(),
            'total_fee_earned' => DB::table('transaction_fees')
                ->where('mitra_id', $mitra->id)
                ->sum('fee_amount'),
            'chart_transactions' => Transaction::where('mitra_id', $mitra->id)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return $this->successResponse($data, 'Mitra dashboard data retrieved');
    }
}