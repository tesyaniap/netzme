<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketReschedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    /**
     * Get Ticket Data for PDF Generation
     * 
     * Return formatted ticket data for frontend PDF generation
     */
    public function getTicketData($id)
    {
        $user = auth()->user();
        $query = Ticket::with([
            'transaction:id,trx_code,mitra_id,customer_name,customer_phone,status,amount,created_at',
            'schedule.vehicle.partner:id,name,code,phone',
            'schedule.route.originCity:id,name,province',
            'schedule.route.destinationCity:id,name,province', 
            'schedule.route.departureTerminal:id,name,address',
            'schedule.route.arrivalTerminal:id,name,address',
            'seat:id,seat_number,row,column'
        ]);
        
        // Mitra hanya bisa lihat ticket dari vehicle miliknya
        if ($user->hasRole('mitra')) {
            $query->whereHas('transaction', function ($q) use ($user) {
                $q->where('mitra_id', $user->mitra_id);
            });
        }
        
        $ticket = $query->find($id);
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }

        // Format data khusus untuk PDF generation
        $ticketData = [
            // Basic ticket info
            'ticket_id' => $ticket->id,
            'booking_code' => $ticket->transaction->trx_code,
            'status' => $ticket->status,
            'issued_date' => $ticket->created_at->format('d F Y H:i'),
            
            // Passenger info — ambil dari transaction karena passenger_id bisa null
            'passenger' => [
                'name'  => $ticket->transaction->customer_name,
                'phone' => $ticket->transaction->customer_phone,
            ],
            
            // Route info
            'route' => [
                'origin' => [
                    'city' => $ticket->schedule->route->originCity->name,
                    'province' => $ticket->schedule->route->originCity->province,
                    'terminal' => $ticket->schedule->route->departureTerminal->name,
                    'terminal_address' => $ticket->schedule->route->departureTerminal->address,
                ],
                'destination' => [
                    'city' => $ticket->schedule->route->destinationCity->name,
                    'province' => $ticket->schedule->route->destinationCity->province,
                    'terminal' => $ticket->schedule->route->arrivalTerminal->name,
                    'terminal_address' => $ticket->schedule->route->arrivalTerminal->address,
                ]
            ],
            
            // Schedule info
            'schedule' => [
                'travel_date' => $ticket->schedule->travel_date,
                'departure_time' => $ticket->schedule->departure_time,
                'arrival_time' => $ticket->schedule->arrival_time,
                'formatted_travel_date' => \Carbon\Carbon::parse($ticket->schedule->travel_date)->format('d F Y'),
                'formatted_departure_time' => \Carbon\Carbon::parse($ticket->schedule->departure_time)->format('H:i'),
                'formatted_arrival_time' => \Carbon\Carbon::parse($ticket->schedule->arrival_time)->format('H:i'),
            ],
            
            // Vehicle info
            'vehicle' => [
                'name' => $ticket->schedule->vehicle->name,
                'plate_number' => $ticket->schedule->vehicle->plate_number,
                'seat_layout' => $ticket->schedule->vehicle->seat_layout,
                'seat_capacity' => $ticket->schedule->vehicle->seat_capacity,
            ],
            
            // Seat info
            'seat' => [
                'number' => $ticket->seat->seat_number,
                'row' => $ticket->seat->row,
                'column' => $ticket->seat->column,
                'position' => 'Baris ' . $ticket->seat->row . ', Kolom ' . $ticket->seat->column,
            ],
            
            // Partner/Operator info
            'operator' => [
                'name'    => $ticket->schedule->vehicle->partner->name,
                'code'    => $ticket->schedule->vehicle->partner->code,
                'phone'   => $ticket->schedule->vehicle->partner->phone ?? '-',
            ],
            
            // Price info
            'pricing' => [
                'ticket_price' => $ticket->price,
                'total_amount' => $ticket->transaction->amount,
                'formatted_ticket_price' => 'Rp ' . number_format($ticket->price, 0, ',', '.'),
                'formatted_total_amount' => 'Rp ' . number_format($ticket->transaction->amount, 0, ',', '.'),
            ],
            
            // QR Code data
            'qr_data' => [
                'booking_code' => $ticket->transaction->trx_code,
                'passenger_name' => $ticket->transaction->customer_name,
                'route' => $ticket->schedule->route->originCity->name . ' - ' . $ticket->schedule->route->destinationCity->name,
                'travel_date' => $ticket->schedule->travel_date,
                'departure_time' => $ticket->schedule->departure_time,
                'seat_number' => $ticket->seat->seat_number,
                'vehicle' => $ticket->schedule->vehicle->plate_number,
                'operator' => $ticket->schedule->vehicle->partner->name,
            ],
            
            // Additional info for PDF
            'pdf_info' => [
                'generated_at' => now()->format('d F Y H:i:s'),
                'valid_until' => \Carbon\Carbon::parse($ticket->schedule->travel_date)->addDays(1)->format('d F Y'),
                'terms' => [
                    'Tiket ini berlaku untuk 1 (satu) orang penumpang',
                    'Harap tiba di terminal 30 menit sebelum keberangkatan',
                    'Tiket yang sudah dibeli tidak dapat dikembalikan',
                    'Tunjukkan tiket ini kepada petugas saat naik bus',
                    'Simpan tiket ini sampai perjalanan selesai'
                ],
                'emergency_contact' => [
                    'operator_phone' => $ticket->schedule->vehicle->partner->phone ?? '021-1234567',
                    'customer_service' => '0804-1-TIKET (84538)'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Ticket data retrieved successfully',
            'data' => $ticketData
        ]);
    }

    /**
     * Get Detail Ticket
     */
    public function show($id)
    {
        $user = auth()->user();
        $query = Ticket::with([
            'transaction:id,trx_code,mitra_id,customer_name,customer_phone,status',
            'schedule.vehicle.partner:id,name,code',
            'schedule.route.originCity:id,name',
            'schedule.route.destinationCity:id,name', 
            'schedule.route.departureTerminal:id,name',
            'schedule.route.arrivalTerminal:id,name',
            'seat:id,seat_number,row,column',
            'reschedules'
        ]);
        
        // Mitra hanya bisa lihat ticket dari vehicle miliknya
        if ($user->hasRole('mitra')) {
            $query->whereHas('transaction', function ($q) use ($user) {
                $q->where('mitra_id', $user->mitra_id);
            });
        }
        
        $ticket = $query->find($id);
        
        if (!$ticket) {
            // Debug info untuk development
            $allTickets = Ticket::with('transaction')->get();
            $userInfo = [
                'user_id' => $user->id,
                'mitra_id' => $user->mitra_id,
                'roles' => $user->getRoleNames()
            ];
            
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
                'debug' => [
                    'user_info' => $userInfo,
                    'requested_ticket_id' => $id,
                    'available_tickets' => $allTickets->map(function($t) {
                        return [
                            'id' => $t->id,
                            'transaction_id' => $t->transaction_id,
                            'mitra_id' => $t->transaction ? $t->transaction->mitra_id : null,
                            'status' => $t->status
                        ];
                    })
                ]
            ], 404);
        }

        // Format response yang lebih informatif
        $response = [
            'id' => $ticket->id,
            'transaction_id' => $ticket->transaction_id,
            'passenger_id' => $ticket->passenger_id,
            'schedule_id' => $ticket->schedule_id,
            'seat_id' => $ticket->seat_id,
            'price' => $ticket->price,
            'status' => $ticket->status,
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at,
            'transaction' => $ticket->transaction ? [
                'id' => $ticket->transaction->id,
                'trx_code' => $ticket->transaction->trx_code,
                'customer_name' => $ticket->transaction->customer_name,
                'customer_phone' => $ticket->transaction->customer_phone,
                'status' => $ticket->transaction->status
            ] : null,
            'schedule' => $ticket->schedule ? [
                'id' => $ticket->schedule->id,
                'departure_time' => $ticket->schedule->departure_time,
                'arrival_time' => $ticket->schedule->arrival_time,
                'price' => $ticket->schedule->price,
                'travel_date' => $ticket->schedule->travel_date,
                'vehicle' => $ticket->schedule->vehicle ? [
                    'name' => $ticket->schedule->vehicle->name,
                    'plate_number' => $ticket->schedule->vehicle->plate_number,
                    'partner' => $ticket->schedule->vehicle->partner ? [
                        'name' => $ticket->schedule->vehicle->partner->name,
                        'code' => $ticket->schedule->vehicle->partner->code
                    ] : null
                ] : null,
                'route' => $ticket->schedule->route ? [
                    'origin_city' => $ticket->schedule->route->originCity ? $ticket->schedule->route->originCity->name : null,
                    'destination_city' => $ticket->schedule->route->destinationCity ? $ticket->schedule->route->destinationCity->name : null,
                    'departure_terminal' => $ticket->schedule->route->departureTerminal ? $ticket->schedule->route->departureTerminal->name : null,
                    'arrival_terminal' => $ticket->schedule->route->arrivalTerminal ? $ticket->schedule->route->arrivalTerminal->name : null
                ] : null
            ] : null,
            'seat' => $ticket->seat ? [
                'id' => $ticket->seat->id,
                'seat_number' => $ticket->seat->seat_number,
                'row' => $ticket->seat->row,
                'column' => $ticket->seat->column
            ] : null,
            'passenger' => null, // passenger_id is null for non-registered customers
            'reschedules' => $ticket->reschedules
        ];

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Reschedule Ticket
     */
    public function reschedule(Request $request, $id)
    {
        $user = auth()->user();
        $query = Ticket::query();
        
        // Mitra hanya bisa reschedule ticket dari vehicle miliknya
        if ($user->hasRole('mitra')) {
            $query->whereHas('schedule.vehicle', function ($q) use ($user) {
                $q->where('partner_id', $user->mitra_id);
            });
        }
        
        $ticket = $query->find($id);
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }

        if ($ticket->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Only paid tickets can be rescheduled'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'new_schedule_id' => 'required|exists:schedules,id',
            'new_seat_id' => 'required|exists:seats,id',
            'reschedule_fee' => 'numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create reschedule record
        TicketReschedule::create([
            'ticket_id' => $ticket->id,
            'old_schedule_id' => $ticket->schedule_id,
            'new_schedule_id' => $request->new_schedule_id,
            'old_seat_id' => $ticket->seat_id,
            'new_seat_id' => $request->new_seat_id,
            'reschedule_fee' => $request->reschedule_fee ?? 0,
            'rescheduled_at' => now()
        ]);

        // Update ticket
        $ticket->update([
            'schedule_id' => $request->new_schedule_id,
            'seat_id' => $request->new_seat_id,
            'status' => 'rescheduled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket rescheduled successfully',
            'data' => new TicketResource($ticket->load(['passenger', 'schedule', 'seat', 'reschedules']))
        ]);
    }
}