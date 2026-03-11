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
    /**
     * Register Mitra
     */
    use ApiResponse;
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mitra,email',
            'phone' => 'required|string'
        ], [
            'name.required' => 'Nama mitra wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor telepon wajib diisi'
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
                'Mitra berhasil didaftarkan dengan status pending. Menunggu persetujuan admin.',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mendaftarkan mitra: ' . $e->getMessage(), null, 500);
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

        return $this->successResponse($mitra, 'Data mitra berhasil diambil');
    }

    /**
     * Get Detail Mitra
     */
    public function show($id)
    {
        $mitra = Mitra::with('users')->find($id);

        if (!$mitra) {
            return $this->errorResponse('Mitra tidak ditemukan', null, 404);
        }

        return $this->successResponse($mitra, 'Detail mitra berhasil diambil');
    }

    /**
     * Update Fee Mitra
     */
    public function updateFee(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:percent,flat',
            'value' => 'required|numeric|min:0'
        ], [
            'type.required' => 'Tipe fee wajib diisi',
            'type.in' => 'Tipe fee harus percent atau flat',
            'value.required' => 'Nilai fee wajib diisi',
            'value.numeric' => 'Nilai fee harus berupa angka',
            'value.min' => 'Nilai fee minimal 0'
        ]);

        $mitra = Mitra::find($id);
        if (!$mitra) {
            return $this->errorResponse('Mitra tidak ditemukan', null, 404);
        }

        // Update atau create mitra fee
        $fee = $mitra->partnerFees()->updateOrCreate(
            ['mitra_id' => $id],
            [
                'type' => $request->type,
                'value' => $request->value,
                'active' => true
            ]
        );

        return $this->successResponse([
            'fee' => $fee,
            'message' => 'Fee mitra berhasil diperbarui'
        ], 'Fee mitra berhasil diperbarui');
    }

    /**
     * Approve Mitra
     */
    public function approve($id)
    {
        $mitra = Mitra::find($id);
        
        if (!$mitra) {
            return $this->errorResponse('Mitra tidak ditemukan', null, 404);
        }

        if ($mitra->status === 'active') {
            return $this->errorResponse('Mitra sudah aktif', null, 400);
        }

        if ($mitra->status === 'rejected') {
            return $this->errorResponse('Tidak dapat menyetujui mitra yang sudah ditolak', null, 400);
        }

        // Update status mitra
        $mitra->update(['status' => 'active']);

        return $this->successResponse('Mitra berhasil disetujui', [
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
        ], [
            'reason.max' => 'Alasan maksimal 500 karakter'
        ]);

        $mitra = Mitra::find($id);
        
        if (!$mitra) {
            return $this->errorResponse('Mitra tidak ditemukan', null, 404);
        }

        if ($mitra->status === 'active') {
            return $this->errorResponse('Tidak dapat menolak mitra yang sudah aktif', null, 400);
        }

        if ($mitra->status === 'rejected') {
            return $this->errorResponse('Mitra sudah ditolak sebelumnya', null, 400);
        }

        // Cek apakah mitra punya transaksi
        if ($mitra->transactions()->exists()) {
            return $this->errorResponse('Tidak dapat menolak mitra yang memiliki transaksi', null, 400);
        }

        // Cek apakah mitra punya saldo
        if ($mitra->balance > 0) {
            return $this->errorResponse('Tidak dapat menolak mitra yang memiliki saldo', null, 400);
        }

        // Update status mitra
        $mitra->update(['status' => 'rejected']);

        return $this->successResponse('Mitra berhasil ditolak', [
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
        ], [
            'reason.max' => 'Alasan maksimal 500 karakter'
        ]);

        $mitra = Mitra::find($id);
        
        if (!$mitra) {
            return $this->errorResponse('Mitra tidak ditemukan', null, 404);
        }

        if ($mitra->status !== 'active') {
            return $this->errorResponse('Hanya mitra aktif yang dapat dinonaktifkan', null, 400);
        }

        // Cek apakah ada transaksi pending/paid
        $pendingTransactions = $mitra->transactions()
            ->whereIn('status', ['pending', 'paid'])
            ->count();

        if ($pendingTransactions > 0) {
            return $this->errorResponse('Tidak dapat menonaktifkan mitra yang memiliki transaksi pending/paid', null, 400);
        }

        // Update status mitra
        $mitra->update(['status' => 'inactive']);

        return $this->successResponse('Mitra berhasil dinonaktifkan', [
            'mitra' => $mitra,
            'reason' => $request->reason
        ]);
    } 
}
