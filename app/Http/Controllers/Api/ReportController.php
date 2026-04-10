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

    /**
     * Get Transaction Report
      * Filter by date range, status, mitra
      */
    public function transactions(Request $request)
    {
        $request->validate([
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'travel_date' => 'nullable|date',
            'status'      => 'nullable|in:pending,paid,issued,cancelled,failed,success',
            'mitra_id'    => 'nullable|exists:mitra,id',
            'search'      => 'nullable|string',
        ]);

        $query = Transaction::with(['mitra', 'fee', 'user']);

        if ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->travel_date) {
            $query->whereDate('travel_date', $request->travel_date);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('trx_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($request->status) {
            if ($request->status === 'success') {
                $query->whereIn('status', ['paid', 'issued']);
            } else {
                $query->where('status', $request->status);
            }
        }

        $transactions = $query->latest('id')->paginate($request->per_page ?? 5);

        if ($transactions->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Transaction report retrieved',
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0
            ]);
        }

        // Format data untuk frontend
        $formattedData = $transactions->map(function($transaction) {
            return [
                'id' => $transaction->id,
                'trx_code' => $transaction->trx_code,
                'tanggal' => $transaction->created_at ?? now(),
                'mitra' => $transaction->mitra->name ?? '-',
                'jenis_transaksi' => 'Pembelian Tiket',
                'jumlah' => $transaction->amount ?? 0,
                'fee' => $transaction->fee->fee_amount ?? 0,
                'status' => $transaction->status ?? '-',
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Transaction report retrieved',
            'data' => $formattedData,
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'total' => $transactions->total()
        ]);
    }

    /**
     * Get Topup Report
     * Filter by date range, status, mitra
     */
    public function topups(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:pending,success,rejected',
            'mitra_id' => 'nullable|exists:mitra,id',
        ]);

        $query = Topup::with(['mitra', 'approver']);

        if ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $topups = $query->latest()->paginate($request->per_page ?? 5);

        return response()->json([
            'status' => true,
            'message' => 'Topup report retrieved',
            'data' => $topups->items(),
            'current_page' => $topups->currentPage(),
            'last_page' => $topups->lastPage(),
            'total' => $topups->total()
        ]);
    }

    /**
     * Get Fee Report
     * Filter by date range, mitra
     */
    public function fees(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'mitra_id' => 'nullable|exists:mitra,id',
        ]);

        $query = TransactionFee::with(['mitra', 'transaction']);

        if ($request->mitra_id) {
            $query->where('mitra_id', $request->mitra_id);
        }

        if ($request->start_date) {
            $query->whereHas('transaction', function($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->start_date);
            });
        }
        if ($request->end_date) {
            $query->whereHas('transaction', function($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->end_date);
            });
        }

        $fees = $query->latest('id')->paginate($request->per_page ?? 5);

        return response()->json([
            'status' => true,
            'message' => 'Fee report retrieved',
            'data' => $fees->items(),
            'current_page' => $fees->currentPage(),
            'last_page' => $fees->lastPage(),
            'total' => $fees->total()
        ]);
    }

    /**
     * Get Balance Report
     * Filter by mitra
     */
    public function balances(Request $request)
    {
        $mitras = Mitra::select('id', 'name', 'balance')
            ->withCount('transactions')
            ->paginate($request->per_page ?? 5);

        $formattedData = $mitras->map(function($mitra) {
            return [
                'mitra_id' => $mitra->id,
                'mitra_name' => $mitra->name,
                'balance' => $mitra->balance,
                'total_transactions' => $mitra->transactions_count,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Balance report retrieved',
            'data' => $formattedData,
            'current_page' => $mitras->currentPage(),
            'last_page' => $mitras->lastPage(),
            'total' => $mitras->total()
        ]);
    }
}