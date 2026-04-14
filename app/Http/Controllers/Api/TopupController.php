<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Topup;
use App\Models\TopupHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TopupController extends Controller
{
    use ApiResponse;

    /**
     * Get List Topup
     * Filter by mitra, status
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        
        $query = Topup::with(['mitra', 'approver']);

        // Mitra hanya lihat topup sendiri
        if ($request->user()->hasRole('mitra')) {
            $query->where('mitra_id', $request->user()->mitra_id);
        }

        $topups = $query->latest()->paginate($perPage);

        // Tambahkan URL lengkap untuk proof_file
        $topups->getCollection()->transform(function ($topup) {
            if ($topup->proof_file) {
                // Handle path dengan atau tanpa subfolder
                $path = $topup->proof_file;
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    $topup->proof_file_url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                } else {
                    // Fallback: coba dengan prefix topups/
                    $topup->proof_file_url = url('storage/' . $path);
                }
            } else {
                $topup->proof_file_url = null;
            }
            return $topup;
        });

        return $this->successResponse($topups, 'Topups retrieved successfully');
    }

    /**
     * Create Topup Request
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'required|in:transfer,va,ewallet',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $mitraId = $request->user()->hasRole('mitra') 
            ? $request->user()->mitra_id 
            : $request->mitra_id;

        if (!$mitraId) {
            return $this->errorResponse('Mitra ID is required', [], 400);
        }

        $data = [
            'mitra_id' => $mitraId,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
        ];

        if ($request->hasFile('proof_file')) {
            $data['proof_file'] = $request->file('proof_file')->store('topups', 'public');
        }

        $topup = Topup::create($data);

        return $this->successResponse('Topup request created successfully', $topup, 201);
    }

    /**
     * Get Detail Topup
     */
    public function show($id)
    {
        $topup = Topup::with(['mitra', 'approver'])->findOrFail($id);

        // Mitra hanya bisa lihat topup sendiri
        if (request()->user()->hasRole('mitra') && $topup->mitra_id !== request()->user()->mitra_id) {
            return $this->errorResponse('Unauthorized', [], 403);
        }

        return $this->successResponse('Topup retrieved successfully', $topup);
    }

    /**
     * Approve Topup
     */
    public function approve(Request $request, $id)
    {
        $topup = Topup::findOrFail($id);

        if ($topup->status !== 'pending') {
            return $this->errorResponse('Topup already processed', [], 400);
        }

        DB::beginTransaction();
        try {
            $mitra = $topup->mitra;
            $balanceBefore = $mitra->balance;
            $balanceAfter = $balanceBefore + $topup->amount;

            // Update mitra balance
            $mitra->update(['balance' => $balanceAfter]);

            // Update topup status
            $topup->update([
                'status' => 'success',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            // buat topup history
            TopupHistory::create([
                'topup_id' => $topup->id,
                'mitra_id' => $topup->mitra_id,
                'amount' => $topup->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'Topup approved',
            ]);

            DB::commit();

            return $this->successResponse('Topup approved successfully', $topup->fresh(['mitra', 'approver']));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to approve topup', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject Topup
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $topup = Topup::findOrFail($id);

        if ($topup->status !== 'pending') {
            return $this->errorResponse('Topup already processed', [], 400);
        }

        DB::beginTransaction();
        try {
            $topup->update([
                'status' => 'rejected',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'reject_reason' => $request->reason,
            ]);

            // buat topup history untuk rejection (audit trail)
            TopupHistory::create([
                'topup_id' => $topup->id,
                'mitra_id' => $topup->mitra_id,
                'amount' => $topup->amount,
                'balance_before' => $topup->mitra->balance,
                'balance_after' => $topup->mitra->balance, // saldo tidak berubah
                'description' => 'Topup rejected: ' . ($request->reason ?? 'No reason provided'),
            ]);

            DB::commit();

            return $this->successResponse('Topup rejected successfully', $topup->fresh(['mitra', 'approver']));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to reject topup', ['error' => $e->getMessage()], 500);
        }
    }
}
