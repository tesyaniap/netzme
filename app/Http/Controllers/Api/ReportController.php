<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use App\Models\Topup;
use App\Models\TransactionFee;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    // GET /api/v1/reports/transactions
    public function transactions(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:pending,paid,issued,cancelled,failed,success',
            'mitra_id' => 'nullable|exists:mitra,id',
        ]);

        $query = Transaction::with(['mitra', 'transactionFee']);

        // Filter by role
        if ($request->user()->hasRole('mitra')) {
            $query->where('mitra_id', $request->user()->mitra_id);
        } elseif ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        // Filter by date
        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->status) {
            if ($request->status === 'success') {
                $query->whereIn('status', ['paid', 'issued']);
            } else {
                $query->where('status', $request->status);
            }
        }

        $transactions = $query->latest()->get();

        if ($transactions->isEmpty()) {
            return $this->successResponse([], 'Transaction report retrieved');
        }

        // Format data untuk frontend
        $formattedData = $transactions->map(function($transaction) {
            return [
                'id' => $transaction->id,
                'tanggal' => $transaction->created_at,
                'mitra' => $transaction->mitra->name ?? '-',
                'jenis_transaksi' => 'Pembelian Tiket',
                'jumlah' => $transaction->amount ?? 0,
                'fee' => $transaction->transactionFee->fee_amount ?? 0,
                'status' => $transaction->status ?? '-',
            ];
        });

        return $this->successResponse($formattedData, 'Transaction report retrieved');
    }

    // GET /api/v1/reports/topups
    public function topups(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:pending,success,rejected',
            'mitra_id' => 'nullable|exists:mitra,id',
        ]);

        $query = Topup::with(['mitra', 'approver']);

        // Filter by role
        if ($request->user()->hasRole('mitra')) {
            $query->where('mitra_id', $request->user()->mitra_id);
        } elseif ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        // Filter by date
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $topups = $query->latest()->paginate(50);

        // Summary
        $summary = [
            'total_topups' => $query->count(),
            'total_amount' => $query->where('status', 'success')->sum('amount'),
            'by_status' => Topup::selectRaw('status, COUNT(*) as count')
                ->when($request->user()->hasRole('mitra'), function($q) use ($request) {
                    $q->where('mitra_id', $request->user()->mitra_id);
                })
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];

        return $this->successResponse('Topup report retrieved', [
            'summary' => $summary,
            'topups' => $topups,
        ]);
    }

    // GET /api/v1/reports/fees
    public function fees(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'mitra_id' => 'nullable|exists:mitra,id',
        ]);

        $query = TransactionFee::with(['mitra', 'transaction']);

        // Filter by role
        if ($request->user()->hasRole('mitra')) {
            $query->where('mitra_id', $request->user()->mitra_id);
        } elseif ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        // Filter by date
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $fees = $query->latest()->paginate(50);

        // Summary
        $summary = [
            'total_fee' => $query->sum('fee_amount'),
        ];

        // By mitra (admin only)
        if ($request->user()->hasRole('admin')) {
            $summary['by_mitra'] = TransactionFee::select('mitra_id', DB::raw('SUM(fee_amount) as total_fee'))
                ->with('mitra:id,name')
                ->groupBy('mitra_id')
                ->get()
                ->map(function($item) {
                    return [
                        'mitra_id' => $item->mitra_id,
                        'mitra_name' => $item->mitra->name,
                        'total_fee' => $item->total_fee,
                    ];
                });
        }

        return $this->successResponse('Fee report retrieved', [
            'summary' => $summary,
            'fees' => $fees,
        ]);
    }

    // GET /api/v1/reports/balances
    public function balances(Request $request)
    {
        if ($request->user()->hasRole('mitra')) {
            // Mitra only see own balance
            $mitra = Mitra::with(['topups', 'transactions'])
                ->find($request->user()->mitra_id);

            return $this->successResponse('Balance report retrieved', [
                'mitra_id' => $mitra->id,
                'mitra_name' => $mitra->name,
                'balance' => $mitra->balance,
                'last_topup' => $mitra->topups()->latest()->first()?->created_at,
                'last_transaction' => $mitra->transactions()->latest()->first()?->created_at,
            ]);
        }

        // Admin see all mitra balances
        $mitras = Mitra::select('id', 'name', 'balance')
            ->withCount('transactions')
            ->get()
            ->map(function($mitra) {
                return [
                    'mitra_id' => $mitra->id,
                    'mitra_name' => $mitra->name,
                    'balance' => $mitra->balance,
                    'total_transactions' => $mitra->transactions_count,
                ];
            });

        $summary = [
            'total_balance' => Mitra::sum('balance'),
            'total_mitra' => Mitra::count(),
        ];

        return $this->successResponse('Balance report retrieved', [
            'summary' => $summary,
            'balances' => $mitras,
        ]);
    }
}