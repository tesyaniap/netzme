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