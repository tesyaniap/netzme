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
     * Get reschedule-able tickets
     */
    public function getRescheduleableTickets(Request $request)
    {
        $query = Ticket::with(['schedule.route.originCity', 'schedule.route.destinationCity', 'seat', 'passenger'])
            ->where('status', Ticket::STATUS_ISSUED);

        // Filter by mitra if user is mitra
        if (auth()->user()->hasRole('mitra')) {
            $query->whereHas('transaction', function($q) {
                $q->where('mitra_id', auth()->user()->mitra->id);
            });
        }

        // Apply search filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                  ->orWhereHas('passenger', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('route_id')) {
            $query->whereHas('schedule', function($q) use ($request) {
                $q->where('route_id', $request->route_id);
            });
        }

        $tickets = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    /**
     * Get available schedules for reschedule
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
            // Filter spesifik tanggal yang diminta
            $query->whereDate('travel_date', $request->travel_date);
        } else {
            // Tampilkan jadwal yang travel_date >= hari ini ATAU travel_date null (template)
            $query->where(function ($q) {
                $q->whereDate('travel_date', '>=', now()->toDateString())
                  ->orWhereNull('travel_date');
            });
        }

        if ($request->get('same_route_only', false)) {
            $query->where('route_id', $ticket->schedule->route_id);
        }

        $schedules = $query->get()->map(function ($s) {
            $totalSeats = $s->vehicle ? $s->vehicle->seat_capacity : 0;
            $bookedSeats = Ticket::where('schedule_id', $s->id)
                ->whereHas('transaction', fn($q) => $q->whereIn('status', ['pending', 'paid', 'issued']))
                ->count();
            $s->available_seats = max(0, $totalSeats - $bookedSeats);
            // Format travel_date sebagai string Y-m-d
            $s->travel_date = $s->travel_date ? $s->travel_date->format('Y-m-d') : null;
            // Flatten route names agar mudah dibaca frontend
            if ($s->route) {
                $s->route->origin_city      = optional($s->route->originCity)->name;
                $s->route->destination_city = optional($s->route->destinationCity)->name;
            }
            return $s;
        })->filter(fn($s) => $s->available_seats > 0)->values();

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'current_ticket' => $ticket
        ]);
    }

    /**
     * Get available seats for selected schedule
     */
    public function getAvailableSeats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|exists:schedules,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule = Schedule::with('vehicle')->findOrFail($request->schedule_id);

        // Seat belong to vehicle, bukan schedule
        // Cari seat_id yang sudah terpakai di schedule ini
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
            'data' => $seats
        ]);
    }

    /**
     * Calculate reschedule fee
     */
    public function calculateRescheduleFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|exists:tickets,id',
            'new_schedule_id' => 'required|exists:schedules,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = Ticket::with('schedule')->findOrFail($request->ticket_id);
        $newSchedule = Schedule::findOrFail($request->new_schedule_id);

        $reschedule_fee = 0;
        $price_difference = 0;

        // Calculate price difference
        if ($newSchedule->price > $ticket->schedule->price) {
            $price_difference = $newSchedule->price - $ticket->schedule->price;
        }

        // Base reschedule fee (configurable)
        $base_reschedule_fee = config('app.reschedule_fee', 25000); // Default 25k

        // Calculate days difference for dynamic fee
        $current_travel_date = Carbon::parse($ticket->schedule->travel_date);
        $new_travel_date = Carbon::parse($newSchedule->travel_date);
        $days_difference = abs($current_travel_date->diffInDays($new_travel_date));

        // Dynamic fee based on days difference
        if ($days_difference > 7) {
            $reschedule_fee = $base_reschedule_fee * 0.5; // 50% discount for far dates
        } else {
            $reschedule_fee = $base_reschedule_fee;
        }

        $total_fee = $reschedule_fee + $price_difference;

        return response()->json([
            'success' => true,
            'data' => [
                'base_reschedule_fee' => $reschedule_fee,
                'price_difference' => $price_difference,
                'total_fee' => $total_fee,
                'old_price' => $ticket->schedule->price,
                'new_price' => $newSchedule->price,
                'days_difference' => $days_difference
            ]
        ]);
    }

    /**
     * Reschedule semua tiket dalam 1 transaksi sekaligus
     */
    public function rescheduleTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx_code'            => 'required|exists:transactions,trx_code',
            'new_schedule_id'     => 'required|exists:schedules,id',
            'seat_assignments'    => 'required|array|min:1',
            'seat_assignments.*.ticket_id' => 'required|exists:tickets,id',
            'seat_assignments.*.new_seat_id' => 'required|exists:seats,id',
            'reason'              => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $newSchedule = Schedule::findOrFail($request->new_schedule_id);

            // Kumpulkan new_seat_id untuk cek duplikat
            $newSeatIds = collect($request->seat_assignments)->pluck('new_seat_id');
            if ($newSeatIds->count() !== $newSeatIds->unique()->count()) {
                throw new \Exception('Kursi yang dipilih tidak boleh sama antar penumpang');
            }

            $reschedules = [];
            $totalFee    = 0;
            $firstTicket = null;

            foreach ($request->seat_assignments as $assignment) {
                $ticket = Ticket::with(['schedule'])
                    ->where('id', $assignment['ticket_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) throw new \Exception('Ticket #' . $assignment['ticket_id'] . ' tidak ditemukan');
                if (!$ticket->canBeRescheduled()) throw new \Exception('Ticket #' . $assignment['ticket_id'] . ' tidak bisa di-reschedule. Hanya tiket berstatus paid yang bisa di-reschedule (status saat ini: ' . $ticket->status . ')');

                // Hak akses mitra: hanya bisa reschedule tiket dari armada miliknya
                if (auth()->user()->hasRole('mitra')) {
                    $mitraId = auth()->user()->mitra_id;
                    $ownedByMitra = \App\Models\Schedule::where('id', $ticket->schedule_id)
                        ->whereHas('vehicle', fn($q) => $q->where('partner_id', $mitraId))
                        ->exists();
                    if (!$ownedByMitra) throw new \Exception('Ticket #' . $assignment['ticket_id'] . ' bukan milik armada Anda');
                }

                $newSeat = Seat::where('id', $assignment['new_seat_id'])
                    ->where('vehicle_id', $newSchedule->vehicle_id)
                    ->lockForUpdate()
                    ->first();

                if (!$newSeat) throw new \Exception('Kursi tidak sesuai dengan kendaraan jadwal baru');

                $seatTaken = Ticket::where('schedule_id', $request->new_schedule_id)
                    ->where('seat_id', $assignment['new_seat_id'])
                    ->where('id', '!=', $ticket->id)
                    ->whereHas('transaction', fn($q) => $q->whereIn('status', ['pending', 'paid', 'issued']))
                    ->exists();

                if ($seatTaken) throw new \Exception('Kursi ' . $newSeat->seat_number . ' sudah terpakai');

                $feeData = $this->calculateRescheduleFeeInternal($ticket, $newSchedule);
                $totalFee += $feeData['total_fee'];

                $reschedules[] = [
                    'ticket'      => $ticket,
                    'new_seat_id' => $assignment['new_seat_id'],
                    'fee'         => $feeData['total_fee'],
                ];

                if (!$firstTicket) $firstTicket = $ticket;
            }

            // Semua validasi lolos — proses
            $rescheduleRecords = [];
            foreach ($reschedules as $item) {
                $ticket = $item['ticket'];

                $rescheduleRecords[] = TicketReschedule::create([
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

            if ($totalFee > 0 && $firstTicket) {
                $this->processRescheduleFee($firstTicket, $totalFee);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => 'Reschedule transaksi berhasil',
                'data'        => [
                    'trx_code'    => $request->trx_code,
                    'total_fee'   => $totalFee,
                    'rescheduled' => count($rescheduleRecords),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Process ticket reschedule (per tiket tunggal)
    public function rescheduleTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id'       => 'required|exists:tickets,id',
            'new_schedule_id' => 'required|exists:schedules,id',
            'new_seat_id'     => 'required|exists:seats,id',
            'reason'          => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Lock & validate ticket
            $ticket = Ticket::with(['schedule'])
                ->where('id', $request->ticket_id)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                throw new \Exception('Ticket not found');
            }

            if (!$ticket->canBeRescheduled()) {
                throw new \Exception('Ticket cannot be rescheduled. Status: ' . $ticket->status);
            }

            // 2. Get new schedule
            $newSchedule = Schedule::findOrFail($request->new_schedule_id);

            // 3. Validate new seat belongs to new schedule's vehicle
            $newSeat = Seat::where('id', $request->new_seat_id)
                ->where('vehicle_id', $newSchedule->vehicle_id)
                ->lockForUpdate()
                ->first();

            if (!$newSeat) {
                throw new \Exception('Seat does not belong to the selected schedule vehicle');
            }

            // 4. Check seat not already taken on new schedule
            $seatTaken = Ticket::where('schedule_id', $request->new_schedule_id)
                ->where('seat_id', $request->new_seat_id)
                ->where('id', '!=', $ticket->id)
                ->whereHas('transaction', function ($q) {
                    $q->whereIn('status', ['pending', 'paid', 'issued']);
                })
                ->exists();

            if ($seatTaken) {
                throw new \Exception('Selected seat is not available');
            }

            // 5. Calculate fee
            $feeData = $this->calculateRescheduleFeeInternal($ticket, $newSchedule);

            // 6. Create reschedule record (only fillable columns)
            $reschedule = TicketReschedule::create([
                'ticket_id'       => $ticket->id,
                'old_schedule_id' => $ticket->schedule_id,
                'new_schedule_id' => $request->new_schedule_id,
                'old_seat_id'     => $ticket->seat_id,
                'new_seat_id'     => $request->new_seat_id,
                'reschedule_fee'  => $feeData['total_fee'],
                'rescheduled_at'  => now(),
            ]);

            // 7. Update ticket — status jadi rescheduled sesuai dokumentasi
            $ticket->update([
                'schedule_id' => $request->new_schedule_id,
                'seat_id'     => $request->new_seat_id,
                'status'      => Ticket::STATUS_RESCHEDULED,
            ]);

            // 8. Process fee deduction if any
            if ($feeData['total_fee'] > 0) {
                $this->processRescheduleFee($ticket, $feeData['total_fee']);
            }

            DB::commit();

            $ticket->load(['schedule.route.originCity', 'schedule.route.destinationCity', 'seat']);

            return response()->json([
                'success' => true,
                'message' => 'Ticket rescheduled successfully',
                'data'    => [
                    'ticket'      => $ticket,
                    'reschedule'  => $reschedule,
                    'fee_charged' => $feeData['total_fee']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get reschedule history for a ticket
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
            'reschedules.rescheduledBy'
        ])->findOrFail($ticketId);

        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => $ticket,
                'reschedule_history' => $ticket->reschedules()->orderBy('rescheduled_at', 'desc')->get()
            ]
        ]);
    }

    /**
     * Internal method to calculate reschedule fee
     */
    private function calculateRescheduleFeeInternal($ticket, $newSchedule)
    {
        $reschedule_fee = 0;
        $price_difference = 0;

        if ($newSchedule->price > $ticket->schedule->price) {
            $price_difference = $newSchedule->price - $ticket->schedule->price;
        }

        $base_reschedule_fee = config('app.reschedule_fee', 25000);

        $current_travel_date = Carbon::parse($ticket->schedule->travel_date);
        $new_travel_date = Carbon::parse($newSchedule->travel_date);
        $days_difference = abs($current_travel_date->diffInDays($new_travel_date));

        if ($days_difference > 7) {
            $reschedule_fee = $base_reschedule_fee * 0.5;
        } else {
            $reschedule_fee = $base_reschedule_fee;
        }

        return [
            'base_reschedule_fee' => $reschedule_fee,
            'price_difference' => $price_difference,
            'total_fee' => $reschedule_fee + $price_difference
        ];
    }

    /**
     * Process reschedule fee (deduct from mitra balance)
     */
    private function processRescheduleFee($ticket, $fee)
    {
        $mitra = $ticket->transaction->mitra;
        
        if ($mitra->balance < $fee) {
            throw new \Exception('Insufficient mitra balance for reschedule fee');
        }

        $mitra->decrement('balance', $fee);
    }
}