<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use App\Models\TransactionPassenger;
use App\Models\TransactionFee;
use App\Models\PartnerFeeLedger;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransactionController extends Controller
{
    use ApiResponse;

    /**
     * Mrncari transaksi yang tersedia
     */
    public function search(Request $request)
    {
        $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'travel_date' => 'required|date',
        ]);

        $schedules = [
            [
                'provider_code' => 'BUS001',
                'route' => $request->origin . ' - ' . $request->destination,
                'departure_time' => '08:00',
                'price' => 150000,
                'available_seats' => 20
            ]
        ];
        return $this->successResponse('Bus schedules retrieved', $schedules);
    }

    /**
     * Seat-Map Bus transaksi
     */

    public function seatMap(Request $request)
    {
        $request->validate([
            'provider_code' => 'required|string',
        ]);

        $seatMap = [
            'seats' => [
                ['number' => 'A1', 'status' => 'available'],
                ['number' => 'A2', 'status' => 'booked'],
            ]                
        ];
        return $this->successResponse('Seat map retrieved', $seatMap);
    }

    /**
     * Book Transaction (seat - map)
    **/
    public function book(Request $request)
    {
        $request->validate([
            'provider_code' => 'required|string',
            'travel_date' => 'required|date',
            'seats' => 'required|array',
            'passengers' => 'required|array',
            'passengers.*.name' => 'required|string',
            'passengers.*.identity_number' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $trxCode = 'TRX' . now()->format('YmdHis');
            $amount = count($request->seats) * 150000;

            $transaction = Transaction::create([
                'trx_code' => $trxCode,
                'mitra_id' => $request->user()->mitra_id,   
                'user_id' => $request->user()->id,
                'provider_code' => $request->provider_code,
                'route' => 'Jakarta - Bandung',
                'travel_date' => $request->travel_date,
                'payment_type' => 'deposit',
                'amount' => $amount,
                'status' => 'pending',
                'provider_response' => [],
            ]);

            foreach ($request->passengers as $index => $passenger) {
                TransactionPassenger::create([
                    'transaction_id' => $transaction->id,
                    'name' => $passenger['name'],
                    'identity_number' => $passenger['identity_number'],
                    'seat_number' => $request->seats[$index],
                ]);
            }

            DB::commit();

            return $this->successResponse('Booking successful', [
                'trx_code' => $trxCode,
                'status' => 'pending',
                'amount' => $amount,
                'expired_at' => now()->addMinutes(30),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Booking failed', ['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * bayar transaksi (seat-map)
     */
    public function pay(Request $request)
    {
        $request->validate([
            'trx_code' => 'required|string',
        ]);

        $transaction = Transaction::where('trx_code', $request->trx_code)->firstOrFail();

        if ($transaction->status !== 'pending') {
            return $this->errorResponse('Transaction already processed', [], 400);
        }

        $mitra = $transaction->mitra;

        if ($mitra->balance < $transaction->amount) {
            return $this->errorResponse('Insufficient balance', [], 400);
        }

        DB::beginTransaction();
        try {
            // lock mitra untuk mengatasi kondisi race
            $mitra = $mitra->lockForUpdate()->find($mitra->id);
            
            //re - check balance setelah lock
            if ($mitra->balance < $transaction->amount) {
                DB::rollBack();
                return $this->errorResponse('insufficient balance', [], 400);
            }

            $balanceBefore = $mitra->balance;
            $balanceAfter = $balanceBefore - $transaction->amount;

            //update balance
            $mitra->balance = $balanceAfter;
            $mitra->save();

            //update transaction
            $transaction->update(['status' => 'paid']);

            DB::commit();

            return $this->successResponse('Payment successful', [
                'trx_code' => $transaction->trx_code,
                'status' => 'paid',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Payment failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * get trx code transaction
     */
    public function show($trxCode)
    {
        $transaction = Transaction::with(['mitra', 'user', 'passengers'])
            ->where('trx_code', $trxCode)
            ->firstOrFail();

        // Mitra only see their own
        if (request()->user()->hasRole('mitra') && $transaction->mitra_id !== request()->user()->mitra_id) {
            return $this->errorResponse('Unauthorized', [], 403);
        }

        return $this->successResponse('Transaction retrieved', $transaction);
    }

    /**
     * trx transaction code issue
     */
    public function issue($trxCode)
    {
        $transaction = Transaction::where('trx_code', $trxCode)->firstOrFail();

        if ($transaction->status !== 'paid') {
            return $this->errorResponse('Transaction must be paid first', [], 400);
        }

        DB::beginTransaction();
        try {
            // Lock mitra untuk update balance fee
            $mitra = Mitra::lockForUpdate()->find($transaction->mitra_id);
            
            // Update status
            $transaction->update(['status' => 'issued']);

            // Calculate and save fee
            $feeAmount = $transaction->amount * 0.05;

            TransactionFee::create([
                'transaction_id' => $transaction->id,
                'mitra_id' => $transaction->mitra_id,
                'fee_type' => 'percent',
                'fee_value' => 5,
                'fee_amount' => $feeAmount,
            ]);

            $balanceBefore = $mitra->balance;
            $balanceAfter = $balanceBefore + $feeAmount;

            PartnerFeeLedger::create([
                'mitra_id' => $transaction->mitra_id,
                'transaction_id' => $transaction->id,
                'amount' => $feeAmount,
                'type' => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'Fee from transaction ' . $trxCode,
            ]);

            // Update balance mitra dengan fee
            $mitra->balance = $balanceAfter;
            $mitra->save();

            DB::commit();

            return $this->successResponse('Ticket issued successfully', [
                'trx_code' => $trxCode,
                'status' => 'issued',
                'fee_earned' => $feeAmount,
                'balance_after' => $balanceAfter,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Issue failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * transaction trx cancel
     */
    public function cancel(Request $request, $trxCode)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $transaction = Transaction::where('trx_code', $trxCode)->firstOrFail();

        if (!in_array($transaction->status, ['pending', 'paid'])) {
            return $this->errorResponse('Cannot cancel this transaction', [], 400);
        }

        DB::beginTransaction();
        try {
            $refundAmount = 0;

            // Refund apabila sudah paid
            if ($transaction->status === 'paid') {
                // Lock mitra untuk refund balance
                $mitra = Mitra::lockForUpdate()->find($transaction->mitra_id);
                $mitra->balance = $mitra->balance + $transaction->amount;
                $mitra->save();
                $refundAmount = $transaction->amount;
            }

            $transaction->update(['status' => 'cancelled']);

            DB::commit();

            return $this->successResponse('Transaction cancelled', [
                'trx_code' => $trxCode,
                'status' => 'cancelled',
                'refund_amount' => $refundAmount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Cancel failed', ['error' => $e->getMessage()], 500);
        }
    }
}