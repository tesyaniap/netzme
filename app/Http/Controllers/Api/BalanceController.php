<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Mitra;
use App\Models\TopupHistory;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    use ApiResponse;

    /**
     * Get Balance
     */
    public function index(Request $request)
    {
        if ($request->user()->hasRole('admin')) {
            $balances = Mitra::select('id', 'code', 'name', 'balance')->get();
            $totalBalance = $balances->sum('balance');
            
            return $this->successResponse([
                'total_balance' => $totalBalance,
                'balances' => $balances
            ], 'Balances retrieved successfully');
        }

        $mitra = Mitra::find($request->user()->mitra_id);

        if (!$mitra) {
            return $this->errorResponse('Mitra not found', null, 404);
        }

        return $this->successResponse([
            'mitra_id' => $mitra->id,
            'mitra_name' => $mitra->name,
            'balance' => $mitra->balance,
        ], 'Balance retrieved successfully');
    }

    /**
     * Get Balance Riwayat
     */
    public function histories(Request $request)
    {
        $query = TopupHistory::with(['mitra', 'topup']);

        //admin, filter berdasar mitra_id
        if ($request->user()->hasRole('admin')) {
            if ($request->mitra_id) {
                $query->where('mitra_id', $request->mitra_id);
            }
        } else {
            //mitra hanya lihat histori sendiri
            $query->where('mitra_id', $request->user()->mitra_id);
        }

        $histories = $query->latest()->paginate(15);

        return $this->successResponse('Balance histories retrieved successfully', $histories);
    }
}