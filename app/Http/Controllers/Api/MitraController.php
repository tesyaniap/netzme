<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Mitra;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MitraController extends Controller
{
    use ApiResponse;
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mitra,email',
            'phone' => 'required|string'
        ]);

        try {
            // Generate kode mitra
            $code = 'MTR' . strtoupper(substr(uniqid(), -6));

            $mitra = Mitra::create([
            
                'code' => $code,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => 'pending',
                'balance' => 0
            ]);

            return $this->successResponse(
                $mitra,
                'Mitra registered successfully with pending status. Wait for admin approval.',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to register mitra: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get Semua Mitra
     */
    public function index(Request $request)
    {
        $mitra = Mitra::with('users')
            ->when($request->status, function($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->get();

        return $this->successResponse($mitra, 'Mitra retrieved successfully');
    }

    /**
     * Get Detail Mitra
     */
    public function show($id)
    {
        $mitra = Mitra::with('users')->find($id);

        if (!$mitra) {
            return $this->errorResponse('Mitra not found', null, 404);
        }

        return $this->successResponse($mitra, 'Mitra retrieved successfully');
    }

    /**
     * Update Fee Mitra
     */
    public function updateFee(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:percent,flat',
            'value' => 'required|numeric|min:0'
        ]);

        $mitra = Mitra::find($id);
        if (!$mitra) {
            return $this->errorResponse('Mitra not found', null, 404);
        }

        // Update atau create mitra fee
        $mitra->partnerFees()->updateOrCreate(
            ['mitra_id' => $id],
            [
                'type' => $request->type,
                'value' => $request->value,
                'active' => true
            ]
        );

        return $this->successResponse('Mitra fee updated successfully', null);
    }

    /**
     * Approve Mitra
     */
    public function approve($id)
    {
        $mitra = Mitra::find($id);
        
        if (!$mitra) {
            return $this->errorResponse('Mitra not found', null, 404);
        }

        if ($mitra->status === 'active') {
            return $this->errorResponse('Mitra already active', null, 400);
        }

        if ($mitra->status === 'rejected') {
            return $this->errorResponse('Cannot approve rejected mitra', null, 400);
        }

        // Update status mitra
        $mitra->update(['status' => 'active']);

        return $this->successResponse('Mitra approved successfully', [
            'mitra' => $mitra
        ]);
    }

    /**
     * Reject Mitra
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $mitra = Mitra::find($id);
        
        if (!$mitra) {
            return $this->errorResponse('Mitra not found', null, 404);
        }

        if ($mitra->status === 'active') {
            return $this->errorResponse('Cannot reject active mitra', null, 400);
        }

        if ($mitra->status === 'rejected') {
            return $this->errorResponse('Mitra already rejected', null, 400);
        }

        // Cek apakah mitra punya transaksi
        if ($mitra->transactions()->exists()) {
            return $this->errorResponse('Cannot reject mitra with existing transactions', null, 400);
        }

        // Cek apakah mitra punya saldo
        if ($mitra->balance > 0) {
            return $this->errorResponse('Cannot reject mitra with balance', null, 400);
        }

        // Update status mitra
        $mitra->update(['status' => 'rejected']);

        return $this->successResponse('Mitra rejected successfully', [
            'mitra' => $mitra,
            'reason' => $request->reason
        ]);
    }

    /**
     * Deactivate Mitra
     */
    public function deactivate(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $mitra = Mitra::find($id);
        
        if (!$mitra) {
            return $this->errorResponse('Mitra not found', null, 404);
        }

        if ($mitra->status !== 'active') {
            return $this->errorResponse('Only active mitra can be deactivated', null, 400);
        }

        // Cek apakah ada transaksi pending/paid
        $pendingTransactions = $mitra->transactions()
            ->whereIn('status', ['pending', 'paid'])
            ->count();

        if ($pendingTransactions > 0) {
            return $this->errorResponse('Cannot deactivate mitra with pending/paid transactions', null, 400);
        }

        // Update status mitra
        $mitra->update(['status' => 'inactive']);

        return $this->successResponse('Mitra deactivated successfully', [
            'mitra' => $mitra,
            'reason' => $request->reason
        ]);
    } 
}
