<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Traits\ApiResponse;
use App\Models\Transaction;
use App\Models\TransactionPassenger;
use App\Models\TransactionFee;
use App\Models\PartnerFeeLedger;
use App\Models\Mitra;
use App\Models\Schedule;
use App\Models\Seat;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    use ApiResponse;

    /**
     * Search available schedules
     */
    public function search(Request $request)
    {
        $request->validate([
            'origin' => 'required',
            'destination' => 'required', 
            'travel_date' => 'nullable|date',
        ]);

        // Get all schedules with relations
        $query = Schedule::with(['vehicle.partner', 'route.originCity', 'route.destinationCity']);
        
        // Filter berdasarkan travel_date jika ada
        if ($request->travel_date) {
            // Jika schedule memiliki travel_date spesifik, harus match
            // Jika schedule tidak memiliki travel_date (null), berarti template harian yang bisa digunakan
            $query->where(function($q) use ($request) {
                $q->whereDate('travel_date', $request->travel_date)
                  ->orWhereNull('travel_date'); // Include template schedules
            });
        }
        
        // Filter berdasarkan route
        if (is_numeric($request->origin) && is_numeric($request->destination)) {
            // Jika origin dan destination adalah route_id yang sama
            if ($request->origin == $request->destination) {
                $query->where('route_id', $request->origin);
            } else {
                // Jika berbeda, cari route yang sesuai
                $query->whereHas('route', function ($q) use ($request) {
                    $q->where('id', $request->origin)
                      ->orWhere('id', $request->destination);
                });
            }
        } else {
            // Filter berdasarkan nama kota
            $query->whereHas('route.originCity', function ($q) use ($request) {
                if (is_numeric($request->origin)) {
                    $q->where('id', $request->origin);
                } else {
                    $q->where('name', 'LIKE', '%' . $request->origin . '%');
                }
            })->whereHas('route.destinationCity', function ($q) use ($request) {
                if (is_numeric($request->destination)) {
                    $q->where('id', $request->destination);
                } else {
                    $q->where('name', 'LIKE', '%' . $request->destination . '%');
                }
            });
        }
        
        $schedules = $query->get();
        
        if ($schedules->isEmpty()) {
            // Debug info untuk development
            $allSchedules = Schedule::with(['vehicle.partner', 'route.originCity', 'route.destinationCity'])->get();
            $allRoutes = \App\Models\Route::with(['originCity', 'destinationCity'])->get();
            
            return $this->errorResponse('No schedules available for the selected route', [
                'search_params' => [
                    'origin' => $request->origin,
                    'destination' => $request->destination,
                    'travel_date' => $request->travel_date
                ],
                'debug_info' => [
                    'total_schedules_in_db' => $allSchedules->count(),
                    'total_routes_in_db' => $allRoutes->count(),
                    'available_routes' => $allRoutes->map(function($route) {
                        return [
                            'id' => $route->id,
                            'route' => ($route->originCity ? $route->originCity->name : 'Unknown') . ' → ' . ($route->destinationCity ? $route->destinationCity->name : 'Unknown')
                        ];
                    }),
                    'available_schedules' => $allSchedules->map(function($schedule) {
                        return [
                            'id' => $schedule->id,
                            'route_id' => $schedule->route_id,
                            'travel_date' => $schedule->travel_date,
                            'route' => $schedule->route ? 
                                ($schedule->route->originCity ? $schedule->route->originCity->name : 'Unknown') . ' → ' . 
                                ($schedule->route->destinationCity ? $schedule->route->destinationCity->name : 'Unknown') : null
                        ];
                    })
                ]
            ], 404);
        }
        
        return $this->successResponse([
            'total_schedules' => $schedules->count(),
            'search_params' => [
                'origin' => $request->origin,
                'destination' => $request->destination,
                'travel_date' => $request->travel_date
            ],
            'schedules' => $schedules
        ], 'Schedules found');
    }

    /**
     * Get seat map for schedule
     */
    public function seatMap(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'travel_date' => 'required|date',
        ]);

        $schedule = Schedule::with(['vehicle.seats', 'route.originCity', 'route.destinationCity', 'route.departureTerminal', 'route.arrivalTerminal'])->find($request->schedule_id);
        
        if (!$schedule) {
            return $this->errorResponse('Schedule not found', [], 404);
        }

        if (!$schedule->vehicle) {
            return $this->errorResponse('Vehicle not found for this schedule', [], 404);
        }

        $seats = $schedule->vehicle->seats()->orderBy('row')->orderBy('column')->get();
        
        if ($seats->isEmpty()) {
            // Auto-generate seats if they don't exist
            $this->generateSeatsForVehicle($schedule->vehicle);
            $seats = $schedule->vehicle->seats()->orderBy('row')->orderBy('column')->get();
            
            if ($seats->isEmpty()) {
                return $this->errorResponse('Failed to generate seats for this vehicle', [
                    'vehicle_id' => $schedule->vehicle_id,
                    'seat_capacity' => $schedule->vehicle->seat_capacity
                ], 500);
            }
        }
        
        // Get booked seats for this schedule and date
        $bookedSeatIds = Ticket::whereHas('transaction', function ($query) use ($request) {
                $query->where('travel_date', $request->travel_date)
                      ->whereIn('status', ['pending', 'paid', 'issued']);
            })
            ->where('schedule_id', $request->schedule_id)
            ->pluck('seat_id')
            ->toArray();

        // Map seats with availability status
        $seatMap = $seats->map(function ($seat) use ($bookedSeatIds) {
            return [
                'id' => $seat->id,
                'seat_number' => $seat->seat_number,
                'row' => $seat->row,
                'column' => $seat->column,
                'status' => in_array($seat->id, $bookedSeatIds) ? 'booked' : 'available'
            ];
        });

        // Group seats by row for easier frontend rendering
        $seatsByRow = $seatMap->groupBy('row');

        return $this->successResponse([
            'schedule' => [
                'id' => $schedule->id,
                'departure_time' => $schedule->departure_time,
                'arrival_time' => $schedule->arrival_time,
                'price' => $schedule->price,
                'travel_date' => $schedule->travel_date,
                'route' => [
                    'origin' => $schedule->route->originCity ? $schedule->route->originCity->name : 'Unknown',
                    'destination' => $schedule->route->destinationCity ? $schedule->route->destinationCity->name : 'Unknown',
                    'departure_terminal' => $schedule->route->departureTerminal ? $schedule->route->departureTerminal->name : 'Unknown',
                    'arrival_terminal' => $schedule->route->arrivalTerminal ? $schedule->route->arrivalTerminal->name : 'Unknown'
                ],
                'vehicle' => [
                    'name' => $schedule->vehicle->name,
                    'plate_number' => $schedule->vehicle->plate_number,
                    'seat_capacity' => $schedule->vehicle->seat_capacity,
                    'seat_layout' => $schedule->vehicle->seat_layout
                ]
            ],
            'seat_summary' => [
                'total_seats' => $seats->count(),
                'available_seats' => $seatMap->where('status', 'available')->count(),
                'booked_seats' => $seatMap->where('status', 'booked')->count()
            ],
            'seats' => $seatMap,
            'seats_by_row' => $seatsByRow,
            'travel_date' => $request->travel_date
        ], 'Seat map retrieved');
    }

    /**
     * Book tickets
     */
    public function book(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'travel_date' => 'required|date',
            'seats' => 'required|array|min:1',
            'seats.*' => 'required|integer|exists:seats,id',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'passengers' => 'required|array|min:1',
            'passengers.*.name' => 'required|string|max:255',
            'passengers.*.identity_number' => 'required|string|max:50',
        ]);

        // Validasi jumlah seats dan passengers harus sama
        if (count($request->seats) !== count($request->passengers)) {
            return $this->errorResponse('Number of seats must match number of passengers', [], 422);
        }

        DB::beginTransaction();
        try {
            $schedule = Schedule::with(['vehicle.partner', 'route'])->find($request->schedule_id);
            
            if (!$schedule) {
                return $this->errorResponse('Schedule not found', [], 404);
            }

            // Optional: Validasi travel_date dengan schedule travel_date
            // Jika schedule memiliki travel_date spesifik, harus match
            // Jika schedule tidak memiliki travel_date, berarti template harian
            if ($schedule->travel_date) {
                $scheduleDate = \Carbon\Carbon::parse($schedule->travel_date)->format('Y-m-d');
                $requestDate = \Carbon\Carbon::parse($request->travel_date)->format('Y-m-d');
                
                if ($scheduleDate != $requestDate) {
                    return $this->errorResponse('Travel date does not match schedule date', [
                        'schedule_date' => $scheduleDate,
                        'requested_date' => $requestDate,
                        'message' => 'This schedule is only available for ' . $scheduleDate
                    ], 422);
                }
            }
            // Jika schedule->travel_date null, berarti schedule template yang bisa digunakan untuk tanggal manapun

            // Validasi seats belong to the same vehicle as schedule
            $validSeats = Seat::whereIn('id', $request->seats)
                             ->where('vehicle_id', $schedule->vehicle_id)
                             ->get();
            
            if ($validSeats->count() !== count($request->seats)) {
                return $this->errorResponse('Some seats do not belong to this vehicle', [], 422);
            }
            
            // Check seat availability untuk tanggal ini
            $bookedSeats = Ticket::whereHas('transaction', function ($query) use ($request) {
                    $query->where('travel_date', $request->travel_date)
                          ->whereIn('status', ['pending', 'paid', 'issued']);
                })
                ->where('schedule_id', $request->schedule_id)
                ->whereIn('seat_id', $request->seats)
                ->exists();

            if ($bookedSeats) {
                return $this->errorResponse('Some seats are already booked for this date', [], 400);
            }

            // Generate transaction code
            $trxCode = 'TRX' . now()->format('YmdHis') . strtoupper(Str::random(4));
            
            $passengerCount = count($request->passengers);
            $basePrice = $schedule->price;
            $adminFee = 5000; // Fixed admin fee
            $serviceFee = 2500; // Fixed service fee
            $totalAmount = ($basePrice * $passengerCount) + $adminFee + $serviceFee;

            // Create transaction
            $transaction = Transaction::create([
                'trx_code' => $trxCode,
                'mitra_id' => $request->user()->mitra_id,
                'user_id' => $request->user()->id,
                'schedule_id' => $request->schedule_id,
                'provider_code' => $schedule->vehicle->partner->code,
                'route' => ($schedule->route->originCity ? $schedule->route->originCity->name : 'Unknown') . ' - ' . ($schedule->route->destinationCity ? $schedule->route->destinationCity->name : 'Unknown'),
                'travel_date' => $request->travel_date,
                'payment_type' => 'deposit',
                'passenger_count' => $passengerCount,
                'base_price' => $basePrice,
                'admin_fee' => $adminFee,
                'service_fee' => $serviceFee,
                'amount' => $totalAmount,
                'status' => 'pending',
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'booked_at' => now(),
                'provider_response' => [],
            ]);

            // Create tickets for each passenger
            foreach ($request->passengers as $index => $passenger) {
                $seat = $validSeats->where('id', $request->seats[$index])->first();
                
                Ticket::create([
                    'transaction_id' => $transaction->id,
                    'passenger_id' => null, // Will be set if passenger is a registered user
                    'schedule_id' => $request->schedule_id,
                    'seat_id' => $request->seats[$index],
                    'price' => $basePrice,
                    'status' => 'booked'
                ]);

                // Create passenger record for compatibility
                TransactionPassenger::create([
                    'transaction_id' => $transaction->id,
                    'name' => $passenger['name'],
                    'identity_number' => $passenger['identity_number'],
                    'seat_number' => $seat->seat_number,
                ]);
            }

            DB::commit();

            return $this->successResponse([
                'trx_code' => $trxCode,
                'status' => 'pending',
                'amount' => $totalAmount,
                'passenger_count' => $passengerCount,
                'base_price' => $basePrice,
                'admin_fee' => $adminFee,
                'service_fee' => $serviceFee,
                'expired_at' => now()->addMinutes(30)->toISOString(),
                'schedule' => [
                    'id' => $schedule->id,
                    'departure_time' => $schedule->departure_time,
                    'arrival_time' => $schedule->arrival_time,
                    'route' => ($schedule->route->originCity ? $schedule->route->originCity->name : 'Unknown') . ' → ' . ($schedule->route->destinationCity ? $schedule->route->destinationCity->name : 'Unknown'),
                    'vehicle' => $schedule->vehicle->name . ' (' . $schedule->vehicle->plate_number . ')'
                ],
                'seats_booked' => $validSeats->pluck('seat_number')->toArray()
            ], 'Booking successful', 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Booking failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    /**
     * Process payment
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
            $mitra = $mitra->lockForUpdate()->find($mitra->id);
            
            if ($mitra->balance < $transaction->amount) {
                DB::rollBack();
                return $this->errorResponse('Insufficient balance', [], 400);
            }

            $balanceBefore = $mitra->balance;
            $balanceAfter = $balanceBefore - $transaction->amount;

            $mitra->balance = $balanceAfter;
            $mitra->save();

            $transaction->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);

            // Update tickets status
            $transaction->tickets()->update(['status' => 'paid']);

            DB::commit();

            return $this->successResponse([
                'trx_code' => $transaction->trx_code,
                'status' => 'paid',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ], 'Payment successful');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Payment failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get transaction details
     */
    public function detail($trxCode)
    {
        $query = Transaction::with([
            'mitra:id,name,code',
            'user:id,name,email', 
            'schedule.vehicle:id,name,plate_number,seat_capacity,seat_layout',
            'schedule.vehicle.partner:id,name,code',
            'schedule.route.originCity:id,name',
            'schedule.route.destinationCity:id,name',
            'schedule.route.departureTerminal:id,name',
            'schedule.route.arrivalTerminal:id,name',
            'tickets.seat:id,seat_number,row,column',
            'passengers:id,transaction_id,name,identity_number,seat_number'
        ]);
        
        // Mitra only see their own transactions
        if (request()->user()->hasRole('mitra')) {
            $query->where('mitra_id', request()->user()->mitra_id);
        }
        
        $transaction = $query->where('trx_code', $trxCode)->first();
        
        if (!$transaction) {
            return $this->errorResponse('Transaction not found', [], 404);
        }

        // Format response dengan informasi lengkap
        $response = [
            'transaction' => [
                'id' => $transaction->id,
                'trx_code' => $transaction->trx_code,
                'status' => $transaction->status,
                'travel_date' => $transaction->travel_date ? $transaction->travel_date->format('Y-m-d') : null,
                'base_price' => $transaction->base_price,
                'admin_fee' => $transaction->admin_fee,
                'service_fee' => $transaction->service_fee,
                'amount' => $transaction->amount,
                'payment_type' => $transaction->payment_type,
                'customer_name' => $transaction->customer_name,
                'customer_phone' => $transaction->customer_phone,
                'customer_email' => $transaction->customer_email,
                'notes' => $transaction->notes,
                'booked_at' => $transaction->booked_at,
                'paid_at' => $transaction->paid_at,
                'issued_at' => $transaction->issued_at,
                'cancelled_at' => $transaction->cancelled_at,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at
            ],
            'schedule' => $transaction->schedule ? [
                'id' => $transaction->schedule->id,
                'departure_time' => $transaction->schedule->departure_time,
                'arrival_time' => $transaction->schedule->arrival_time,
                'price' => $transaction->schedule->price,
                'travel_date' => $transaction->schedule->travel_date
                    ? $transaction->schedule->travel_date->format('Y-m-d')
                    : null,
                'vehicle' => $transaction->schedule->vehicle ? [
                    'id' => $transaction->schedule->vehicle->id,
                    'name' => $transaction->schedule->vehicle->name,
                    'plate_number' => $transaction->schedule->vehicle->plate_number,
                    'seat_capacity' => $transaction->schedule->vehicle->seat_capacity,
                    'seat_layout' => $transaction->schedule->vehicle->seat_layout,
                    'partner' => $transaction->schedule->vehicle->partner ? [
                        'id' => $transaction->schedule->vehicle->partner->id,
                        'name' => $transaction->schedule->vehicle->partner->name,
                        'code' => $transaction->schedule->vehicle->partner->code
                    ] : null
                ] : null,
                'route' => $transaction->schedule->route ? [
                    'id' => $transaction->schedule->route->id,
                    'origin_city' => $transaction->schedule->route->originCity ? $transaction->schedule->route->originCity->name : 'Unknown',
                    'destination_city' => $transaction->schedule->route->destinationCity ? $transaction->schedule->route->destinationCity->name : 'Unknown',
                    'departure_terminal' => $transaction->schedule->route->departureTerminal ? $transaction->schedule->route->departureTerminal->name : 'Unknown',
                    'arrival_terminal' => $transaction->schedule->route->arrivalTerminal ? $transaction->schedule->route->arrivalTerminal->name : 'Unknown'
                ] : null
            ] : null,
            'tickets' => $transaction->tickets->map(function($ticket) {
                return [
                    'id' => $ticket->id,
                    'status' => $ticket->status,
                    'price' => $ticket->price,
                    'seat' => $ticket->seat ? [
                        'id' => $ticket->seat->id,
                        'seat_number' => $ticket->seat->seat_number,
                        'row' => $ticket->seat->row,
                        'column' => $ticket->seat->column
                    ] : null
                ];
            }),
            'passengers' => $transaction->passengers->map(function($passenger) {
                return [
                    'id' => $passenger->id,
                    'name' => $passenger->name,
                    'identity_number' => $passenger->identity_number,
                    'seat_number' => $passenger->seat_number
                ];
            }),
            'mitra' => $transaction->mitra ? [
                'id' => $transaction->mitra->id,
                'name' => $transaction->mitra->name,
                'code' => $transaction->mitra->code
            ] : null,
            'user' => $transaction->user ? [
                'id' => $transaction->user->id,
                'name' => $transaction->user->name,
                'email' => $transaction->user->email
            ] : null
        ];

        return $this->successResponse($response, 'Transaction retrieved');
    }

    /**
     * Issue tickets
     */
    public function issue($trxCode)
    {
        $transaction = Transaction::where('trx_code', $trxCode)->firstOrFail();

        if ($transaction->status !== 'paid') {
            return $this->errorResponse('Transaction must be paid first', [], 400);
        }

        DB::beginTransaction();
        try {
            $mitra = Mitra::lockForUpdate()->find($transaction->mitra_id);
            
            $transaction->update([
                'status' => 'issued',
                'issued_at' => now()
            ]);

            // Update tickets status
            $transaction->tickets()->update(['status' => 'issued']);

            // Calculate and record fee
            $partnerFee = $mitra->partnerFees()->where('active', true)->first();
            
            if (!$partnerFee) {
                $feeType = 'percent';
                $feeValue = 5;
                $feeAmount = $transaction->amount * 0.05;
            } else {
                $feeType = $partnerFee->type;
                $feeValue = $partnerFee->value;
                
                if ($feeType === 'percent') {
                    $feeAmount = $transaction->amount * ($feeValue / 100);
                } else {
                    $feeAmount = $feeValue;
                }
            }

            TransactionFee::create([
                'transaction_id' => $transaction->id,
                'mitra_id' => $transaction->mitra_id,
                'fee_type' => $feeType,
                'fee_value' => $feeValue,
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

            $mitra->balance = $balanceAfter;
            $mitra->save();

            DB::commit();

            return $this->successResponse([
                'trx_code' => $trxCode,
                'status' => 'issued',
                'fee_earned' => $feeAmount,
                'balance_after' => $balanceAfter,
            ], 'Tickets issued successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Issue failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel transaction
     */
    public function cancel(Request $request, $trxCode)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $query = Transaction::query();
        
        // Mitra only cancel their own transactions
        if (request()->user()->hasRole('mitra')) {
            $query->where('mitra_id', request()->user()->mitra_id);
        }
        
        $transaction = $query->where('trx_code', $trxCode)->first();
        
        if (!$transaction) {
            return $this->errorResponse('Transaction not found', [], 404);
        }

        if (!in_array($transaction->status, ['pending', 'paid'])) {
            return $this->errorResponse('Cannot cancel this transaction', [], 400);
        }

        DB::beginTransaction();
        try {
            $refundAmount = 0;

            if ($transaction->status === 'paid') {
                $mitra = Mitra::lockForUpdate()->find($transaction->mitra_id);
                $mitra->balance = $mitra->balance + $transaction->amount;
                $mitra->save();
                $refundAmount = $transaction->amount;
            }

            $transaction->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $request->reason
            ]);

            // Update tickets status
            $transaction->tickets()->update(['status' => 'cancelled']);

            DB::commit();

            return $this->successResponse([
                'trx_code' => $trxCode,
                'status' => 'cancelled',
                'refund_amount' => $refundAmount,
            ], 'Transaction cancelled');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Cancel failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get transaction statistics for mitra
     * period: today | month | year
     */
    public function statistics(Request $request)
    {
        $request->validate([
            'period' => 'required|in:today,month,year',
            'month'  => 'nullable|integer|min:1|max:12',
            'year'   => 'nullable|integer|min:2000',
        ]);

        $period = $request->period;
        $month  = $request->month;
        $year   = $request->year ?? date('Y');

        $query = Transaction::where('mitra_id', $request->user()->mitra_id)
                            ->where('status', 'issued');

        if ($period === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($period === 'month') {
            if (!$month) {
                return $this->errorResponse('Parameter month wajib diisi untuk period=month', [], 422);
            }
            $query->whereMonth('created_at', $month)
                  ->whereYear('created_at', $year);
        } elseif ($period === 'year') {
            $query->whereYear('created_at', $year);
        }

        $count  = $query->count();
        $amount = $query->sum('amount');

        return response()->json([
            'success' => true,
            'message' => [
                'count'  => $count,
                'amount' => (int) $amount,
            ],
        ]);
    }

    /**
     * Get paginated transaction history for mitra
     */
    public function history(Request $request)
    {
        $request->validate([
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $request->per_page ?? 10;

        $transactions = Transaction::where('mitra_id', $request->user()->mitra_id)
                                   ->with(['schedule.route.originCity', 'schedule.route.destinationCity'])
                                   ->orderBy('created_at', 'desc')
                                   ->paginate($perPage);

        $data = $transactions->map(function ($trx) {
            $route = null;
            if ($trx->schedule && $trx->schedule->route) {
                $origin      = optional($trx->schedule->route->originCity)->name ?? 'Unknown';
                $destination = optional($trx->schedule->route->destinationCity)->name ?? 'Unknown';
                $route       = $origin . ' - ' . $destination;
            } else {
                $route = $trx->route; // fallback to stored route string
            }

            return [
                'id'          => $trx->id,
                'trx_code'    => $trx->trx_code,
                'route'       => $route,
                'amount'      => (int) $trx->amount,
                'status'      => $trx->status,
                'travel_date' => $trx->travel_date ? $trx->travel_date->format('Y-m-d') : null,
                'created_at'  => $trx->created_at ? $trx->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'data'         => $data,
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
            ],
        ]);
    }

    /**
     * Print ticket data for a transaction
     */
    public function print($trxCode)
    {
        $transaction = Transaction::where('trx_code', $trxCode)
                                  ->where('mitra_id', request()->user()->mitra_id)
                                  ->with([
                                      'schedule.route.originCity',
                                      'schedule.route.destinationCity',
                                      'schedule.route.departureTerminal',
                                      'schedule.route.arrivalTerminal',
                                      'schedule.vehicle',
                                      'tickets.seat',
                                      'passengers',
                                      'mitra',
                                  ])
                                  ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if ($transaction->status !== 'issued') {
            return response()->json([
                'success' => false,
                'message' => 'Tiket belum bisa dicetak',
            ], 400);
        }

        $schedule = $transaction->schedule;
        $route    = $schedule ? $schedule->route : null;
        $vehicle  = $schedule ? $schedule->vehicle : null;

        // Seat numbers from tickets
        $seatNumbers = $transaction->tickets
            ->filter(fn($t) => $t->seat)
            ->map(fn($t) => $t->seat->seat_number)
            ->join(', ');

        // Passengers
        $passengers = $transaction->passengers->map(function ($p) {
            return [
                'name'            => $p->name,
                'identity_number' => $p->identity_number,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => [
                'trx_code'             => $transaction->trx_code,
                'origin'               => $route && $route->originCity      ? $route->originCity->name      : 'Unknown',
                'origin_terminal'      => $route && $route->departureTerminal ? $route->departureTerminal->name : 'Unknown',
                'destination'          => $route && $route->destinationCity  ? $route->destinationCity->name  : 'Unknown',
                'destination_terminal' => $route && $route->arrivalTerminal  ? $route->arrivalTerminal->name  : 'Unknown',
                'travel_date'          => $transaction->travel_date ? $transaction->travel_date->format('Y-m-d') : null,
                'departure_time'       => $schedule ? $schedule->departure_time : null,
                'class'                => $vehicle ? $vehicle->seat_layout : null,
                'seat_numbers'         => $seatNumbers,
                'passengers'           => $passengers,
                'customer_name'        => $transaction->customer_name,
                'customer_phone'       => $transaction->customer_phone,
                'customer_email'       => $transaction->customer_email,
                'amount'               => (int) $transaction->amount,
                'status'               => $transaction->status,
                'agent_name'           => $transaction->mitra ? $transaction->mitra->name : null,
            ],
        ]);
    }

    /**
     * Get list of schedules with available seats
     */
    public function schedules(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $query = Schedule::with([
            'vehicle',
            'route.originCity',
            'route.destinationCity',
        ]);

        if ($request->date_from) {
            // Include schedules with specific travel_date >= date_from OR template schedules (travel_date = null)
            $query->where(function($q) use ($request) {
                $q->whereDate('travel_date', '>=', $request->date_from)
                  ->orWhereNull('travel_date');
            });
        }

        if ($request->date_to) {
            // Only apply date_to filter if travel_date is not null
            $query->where(function($q) use ($request) {
                $q->whereDate('travel_date', '<=', $request->date_to)
                  ->orWhereNull('travel_date');
            });
        }

        $schedules = $query->orderBy('travel_date')->orderBy('departure_time')->get();

        $data = $schedules->map(function ($schedule) {
            $totalSeats = $schedule->vehicle ? $schedule->vehicle->seat_capacity : 0;

            // Count booked/paid/issued tickets for this schedule
            $bookedSeats = Ticket::where('schedule_id', $schedule->id)
                ->whereHas('transaction', function ($q) {
                    $q->whereIn('status', ['pending', 'paid', 'issued']);
                })
                ->count();

            $availableSeats = max(0, $totalSeats - $bookedSeats);

            $origin      = optional(optional($schedule->route)->originCity)->name ?? 'Unknown';
            $destination = optional(optional($schedule->route)->destinationCity)->name ?? 'Unknown';

            return [
                'id'              => $schedule->id,
                'route'           => $origin . ' - ' . $destination,
                'departure_time'  => $schedule->departure_time,
                'arrival_time'    => $schedule->arrival_time,
                'travel_date'     => $schedule->travel_date ? $schedule->travel_date->format('Y-m-d') : null,
                'price'           => (string) $schedule->price,
                'available_seats' => $availableSeats,
                'total_seats'     => $totalSeats,
                'vehicle'         => $schedule->vehicle ? [
                    'id' => $schedule->vehicle->id,
                    'name' => $schedule->vehicle->name,
                    'plate_number' => $schedule->vehicle->plate_number,
                    'seat_capacity' => $schedule->vehicle->seat_capacity,
                ] : null
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => [
                'schedules' => $data,
                'total_schedules' => $data->count(),
                'debug_info' => [
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                    'total_schedules_in_db' => Schedule::count(),
                    'schedules_with_vehicles' => Schedule::whereHas('vehicle')->count(),
                    'schedules_with_routes' => Schedule::whereHas('route')->count(),
                ]
            ],
        ]);
    }

    /**
     * Auto-generate seats for a vehicle
     */
    private function generateSeatsForVehicle($vehicle)
    {
        // Skip if seats already exist
        if ($vehicle->seats()->count() > 0) {
            return;
        }

        $seatLayout = $vehicle->class === 'eksekutif' ? '2-1' : ($vehicle->class === 'bisnis' ? '2-2' : '2-3');
        $seatsPerRow = $seatLayout === '2-1' ? 3 : ($seatLayout === '2-2' ? 4 : 5);
        $rows = ceil($vehicle->seat_capacity / $seatsPerRow);

        $seatNumber = 1;
        for ($row = 1; $row <= $rows; $row++) {
            for ($col = 1; $col <= $seatsPerRow; $col++) {
                if ($seatNumber > $vehicle->seat_capacity) break;

                Seat::create([
                    'vehicle_id' => $vehicle->id,
                    'seat_number' => str_pad($seatNumber, 2, '0', STR_PAD_LEFT),
                    'row' => $row,
                    'column' => $col,
                    'is_available' => true
                ]);

                $seatNumber++;
            }
        }
    }
}