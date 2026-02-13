<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    use ApiResponse;

    /**
     * Payment Callback harusnya dari Provider
     */
    public function payment(Request $request)
    {
        $request->validate([
            'trx_code' => 'required|string',
            'status' => 'required|in:success,failed',
            'payment_ref' => 'nullable|string',
        ]);

        try {
            $transaction = Transaction::where('trx_code', $request->trx_code)->first();

            if (!$transaction) {
                Log::warning('Payment callback: Transaction not found', ['trx_code' => $request->trx_code]);
                return $this->errorResponse('Transaction not found', [], 404);
            }

            DB::beginTransaction();

            // Update transaction payment status
            if ($request->status === 'success') {
                $transaction->update([
                    'status' => 'paid',
                    'provider_response' => array_merge(
                        $transaction->provider_response ?? [],
                        ['payment' => $request->all()]
                    ),
                ]);

                Log::info('Payment callback: Success', ['trx_code' => $request->trx_code]);
            } else {
                // Payment failed, kembalikan dana
                if ($transaction->status === 'paid') {
                    $mitra = $transaction->mitra;
                    $mitra->update(['balance' => $mitra->balance + $transaction->amount]);
                }

                $transaction->update([
                    'status' => 'failed',
                    'provider_response' => array_merge(
                        $transaction->provider_response ?? [],
                        ['payment' => $request->all()]
                    ),
                ]);

                Log::warning('Payment callback: Failed', ['trx_code' => $request->trx_code]);
            }

            DB::commit();

            return $this->successResponse('Payment callback processed', [
                'trx_code' => $transaction->trx_code,
                'status' => $transaction->status,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment callback error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Callback processing failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ticket Callback harusnya dr Provider
     */
    public function ticket(Request $request)
    {
        $request->validate([
            'trx_code' => 'required|string',
            'status' => 'required|in:issued,cancelled,failed',
            'ticket_number' => 'nullable|string',
            'qr_code' => 'nullable|string',
        ]);

        try {
            $transaction = Transaction::where('trx_code', $request->trx_code)->first();

            if (!$transaction) {
                Log::warning('Ticket callback: Transaction not found', ['trx_code' => $request->trx_code]);
                return $this->errorResponse('Transaction not found', [], 404);
            }

            DB::beginTransaction();

            // Update transaction berdasar status ticket
            if ($request->status === 'issued') {
                $transaction->update([
                    'status' => 'issued',
                    'provider_response' => array_merge(
                        $transaction->provider_response ?? [],
                        ['ticket' => $request->all()]
                    ),
                ]);

                Log::info('Ticket callback: Issued', ['trx_code' => $request->trx_code]);

            } elseif ($request->status === 'cancelled') {
                // Refund balance
                $mitra = $transaction->mitra;
                $mitra->update(['balance' => $mitra->balance + $transaction->amount]);

                $transaction->update([
                    'status' => 'cancelled',
                    'provider_response' => array_merge(
                        $transaction->provider_response ?? [],
                        ['ticket' => $request->all()]
                    ),
                ]);

                Log::warning('Ticket callback: Cancelled', ['trx_code' => $request->trx_code]);

            } else {
                $transaction->update([
                    'status' => 'failed',
                    'provider_response' => array_merge(
                        $transaction->provider_response ?? [],
                        ['ticket' => $request->all()]
                    ),
                ]);

                Log::error('Ticket callback: Failed', ['trx_code' => $request->trx_code]);
            }

            DB::commit();

            return $this->successResponse('Ticket callback processed', [
                'trx_code' => $transaction->trx_code,
                'status' => $transaction->status,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ticket callback error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Callback processing failed', ['error' => $e->getMessage()], 500);
        }
    }
}
