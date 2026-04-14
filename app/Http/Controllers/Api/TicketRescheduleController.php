<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReschedule;
use App\Models\Schedule;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TicketRescheduleController extends Controller
{
    /**
     * Get available schedules for reschedule (admin only)
     */
    public function getAvailableSchedules(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx_code'    => 'required_without:ticket_id|exists:transactions,trx_code',
            'ticket_id'   => 'required_without:trx_code|exists:tickets,id',
            'travel_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Resolve ticket_id dari trx_code jika tidak dikirim langsung
        if ($request->filled('trx_code') && !$request->filled('ticket_id')) {
            $firstTicket = Ticket::whereHas('transaction', fn($q) => $q->where('trx_code', $request->trx_code))
                ->where('status', Ticket::STATUS_PAID)
                ->first();
            if (!$firstTicket) {
                return response()->json(['success' => false, 'message' => 'Tidak ada tiket paid untuk transaksi ini'], 404);
            }
            $ticketId = $firstTicket->id;
        } else {
            $ticketId = $request->ticket_id;
        }

        $ticket = Ticket::with('schedule.route')->findOrFail($ticketId);

        $query = Schedule::with(['route.originCity', 'route.destinationCity', 'vehicle'])
            ->where('id', '!=', $ticket->schedule_id)
            ->whereHas('vehicle');

        if ($request->filled('travel_date')) {
            $query->whereDate('travel_date', $request->travel_date);
        }
        // Tanpa filter tanggal = tampilkan semua jadwal yang ada

        $schedules = $query->get()->map(function ($s) {
            $totalSeats  = $s->vehicle ? $s->vehicle->seat_capacity : 0;
            $bookedSeats = Ticket::where('schedule_id', $s->id)
                ->whereHas('transaction', fn($q) => $q->whereIn('status', ['pending', 'paid', 'issued']))
                ->count();
            $s->available_seats = max(0, $totalSeats - $bookedSeats);
            $s->travel_date     = $s->travel_date ? $s->travel_date->format('Y-m-d') : null;

            $originCity      = optional(optional($s->route)->originCity)->name ?? '-';
            $destinationCity = optional(optional($s->route)->destinationCity)->name ?? '-';

            return [
                'id'             => $s->id,
                'travel_date'    => $s->travel_date,
                'departure_time' => substr($s->departure_time, 0, 5),
                'arrival_time'   => substr($s->arrival_time, 0, 5),
                'price'          => $s->price,
                'available_seats'=> $s->available_seats,
                'route'          => [
                    'origin_city'      => $originCity,
                    'destination_city' => $destinationCity,
                ],
            ];
        })->values();

        return response()->json([
            'success'        => true,
            'data'           => $schedules,
            'current_ticket' => $ticket
        ]);
    }

    /**
     * Get available seats for selected schedule (admin only)
     */
    public function getAvailableSeats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|exists:schedules,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $schedule = Schedule::with('vehicle')->findOrFail($request->schedule_id);

        $bookedSeatIds = Ticket::where('schedule_id', $request->schedule_id)
            ->whereHas('transaction', function ($q) {
                $q->whereIn('status', ['pending', 'paid', 'issued']);
            })
            ->pluck('seat_id')
            ->toArray();

        $seats = Seat::where('vehicle_id', $schedule->vehicle_id)
            ->whereNotIn('id', $bookedSeatIds)
            ->orderBy('row')
            ->orderBy('column')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $seats
        ]);
    }

    /**
     * Calculate reschedule fee (admin only)
     */
    public function calculateRescheduleFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id'       => 'required|exists:tickets,id',
            'new_schedule_id' => 'required|exists:schedules,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $ticket      = Ticket::with('schedule')->findOrFail($request->ticket_id);
        $newSchedule = Schedule::findOrFail($request->new_schedule_id);
        $feeData     = $this->calculateFeeInternal($ticket, $newSchedule);

        return response()->json([
            'success' => true,
            'data'    => array_merge($feeData, [
                'formatted_total_fee' => 'Rp ' . number_format($feeData['total_fee'], 0, ',', '.')
            ])
        ]);
    }

    /**
     * Process reschedule for all tickets in a transaction (admin only)
     */
    public function rescheduleTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx_code'                         => 'required|exists:transactions,trx_code',
            'new_schedule_id'                  => 'required|exists:schedules,id',
            'seat_assignments'                 => 'required|array|min:1',
            'seat_assignments.*.ticket_id'     => 'required|exists:tickets,id',
            'seat_assignments.*.new_seat_id'   => 'required|exists:seats,id',
            'reason'                           => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $newSchedule = Schedule::findOrFail($request->new_schedule_id);

            // Cek duplikat kursi
            $newSeatIds = collect($request->seat_assignments)->pluck('new_seat_id');
            if ($newSeatIds->count() !== $newSeatIds->unique()->count()) {
                throw new \Exception('Kursi yang dipilih tidak boleh sama antar penumpang');
            }

            $reschedules = [];
            $totalFee    = 0;
            $firstTicket = null;

            foreach ($request->seat_assignments as $assignment) {
                $ticket = Ticket::with('schedule')
                    ->where('id', $assignment['ticket_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) {
                    throw new \Exception('Tiket #' . $assignment['ticket_id'] . ' tidak ditemukan');
                }
                if (!$ticket->canBeRescheduled()) {
                    throw new \Exception('Tiket #' . $assignment['ticket_id'] . ' tidak bisa di-reschedule. Status saat ini: ' . $ticket->status . ' (harus paid)');
                }

                $newSeat = Seat::where('id', $assignment['new_seat_id'])
                    ->where('vehicle_id', $newSchedule->vehicle_id)
                    ->lockForUpdate()
                    ->first();

                if (!$newSeat) {
                    throw new \Exception('Kursi tidak sesuai dengan kendaraan jadwal baru');
                }

                $seatTaken = Ticket::where('schedule_id', $request->new_schedule_id)
                    ->where('seat_id', $assignment['new_seat_id'])
                    ->where('id', '!=', $ticket->id)
                    ->whereHas('transaction', fn($q) => $q->whereIn('status', ['pending', 'paid', 'issued']))
                    ->exists();

                if ($seatTaken) {
                    throw new \Exception('Kursi ' . $newSeat->seat_number . ' sudah terpakai');
                }

                $feeData  = $this->calculateFeeInternal($ticket, $newSchedule);
                $totalFee += $feeData['total_fee'];

                $reschedules[] = [
                    'ticket'      => $ticket,
                    'new_seat_id' => $assignment['new_seat_id'],
                    'fee'         => $feeData['total_fee'],
                ];

                if (!$firstTicket) $firstTicket = $ticket;
            }

            // Semua validasi lolos — proses reschedule
            foreach ($reschedules as $item) {
                $ticket = $item['ticket'];

                TicketReschedule::create([
                    'ticket_id'       => $ticket->id,
                    'old_schedule_id' => $ticket->schedule_id,
                    'new_schedule_id' => $request->new_schedule_id,
                    'old_seat_id'     => $ticket->seat_id,
                    'new_seat_id'     => $item['new_seat_id'],
                    'reschedule_fee'  => $item['fee'],
                    'rescheduled_at'  => now(),
                ]);

                $ticket->update([
                    'schedule_id' => $request->new_schedule_id,
                    'seat_id'     => $item['new_seat_id'],
                    'status'      => Ticket::STATUS_RESCHEDULED,
                ]);
            }

            // Potong saldo mitra jika ada biaya
            if ($totalFee > 0 && $firstTicket) {
                $this->processFee($firstTicket, $totalFee);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reschedule berhasil',
                'data'    => [
                    'trx_code'    => $request->trx_code,
                    'total_fee'   => $totalFee,
                    'rescheduled' => count($reschedules),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get reschedule history for a ticket (admin only)
     */
    public function getRescheduleHistory($ticketId)
    {
        $ticket = Ticket::with([
            'reschedules.oldSchedule.route.originCity',
            'reschedules.oldSchedule.route.destinationCity',
            'reschedules.newSchedule.route.originCity',
            'reschedules.newSchedule.route.destinationCity',
            'reschedules.oldSeat',
            'reschedules.newSeat',
        ])->findOrFail($ticketId);

        return response()->json([
            'success' => true,
            'data'    => [
                'ticket'             => $ticket,
                'reschedule_history' => $ticket->reschedules()->orderBy('rescheduled_at', 'desc')->get()
            ]
        ]);
    }

    // ── Private helpers ──────────────────────────────────────

    private function calculateFeeInternal($ticket, $newSchedule): array
    {
        $priceDiff       = max(0, $newSchedule->price - $ticket->schedule->price);
        $baseRescheduleFee = config('app.reschedule_fee', 25000);

        $oldDate = Carbon::parse($ticket->schedule->travel_date);
        $newDate = Carbon::parse($newSchedule->travel_date);
        $daysDiff = abs($oldDate->diffInDays($newDate));

        $rescheduleFee = $daysDiff > 7 ? $baseRescheduleFee * 0.5 : $baseRescheduleFee;

        return [
            'base_reschedule_fee' => $rescheduleFee,
            'price_difference'    => $priceDiff,
            'total_fee'           => $rescheduleFee + $priceDiff,
            'old_price'           => $ticket->schedule->price,
            'new_price'           => $newSchedule->price,
            'days_difference'     => $daysDiff,
        ];
    }

    private function processFee($ticket, float $fee): void
    {
        $mitra = $ticket->transaction->mitra;
        if ($mitra->balance < $fee) {
            throw new \Exception('Saldo mitra tidak cukup untuk biaya reschedule');
        }
        $mitra->decrement('balance', $fee);
    }
}
