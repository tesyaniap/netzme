<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeatResource;
use App\Models\Seat;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeatController extends Controller
{
    /**
     * Get Semua Seats
     */
    public function index(Request $request)
    {
        $query = Seat::with('vehicle');
        
        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        
        $seats = $query->orderBy('row')->orderBy('column')->get();
        
        return response()->json([
            'success' => true,
            'data' => SeatResource::collection($seats)
        ]);
    }

    /**
     * Create New Seat
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'seat_number' => 'required|string',
            'row' => 'required|integer|min:1',
            'column' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $seat = Seat::create($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new SeatResource($seat->load('vehicle'))
        ], 201);
    }

    /**
     * Generate Seats
     */
    public function generateSeats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicle = Vehicle::find($request->vehicle_id);
        
        // Hapus seats yang sudah ada
        Seat::where('vehicle_id', $vehicle->id)->delete();
        
        $seats = [];
        $seatCount = 0;
        
        // Generate seats berdasarkan layout
        switch ($vehicle->seat_layout) {
            case '2-2': // 2 kursi kiri, 2 kursi kanan
                $rows = ceil($vehicle->seat_capacity / 4);
                for ($row = 1; $row <= $rows; $row++) {
                    $rowLetter = chr(64 + $row); // A, B, C, dst
                    for ($col = 1; $col <= 4; $col++) {
                        if ($seatCount < $vehicle->seat_capacity) {
                            $seatNumber = $rowLetter . $col;
                            $seats[] = [
                                'vehicle_id' => $vehicle->id,
                                'seat_number' => $seatNumber,
                                'row' => $row,
                                'column' => $col,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                            $seatCount++;
                        }
                    }
                }
                break;
                
            case '2-3': // 2 kursi kiri, 3 kursi kanan
                $rows = ceil($vehicle->seat_capacity / 5);
                for ($row = 1; $row <= $rows; $row++) {
                    $rowLetter = chr(64 + $row); // A, B, C, dst
                    for ($col = 1; $col <= 5; $col++) {
                        if ($seatCount < $vehicle->seat_capacity) {
                            $seatNumber = $rowLetter . $col;
                            $seats[] = [
                                'vehicle_id' => $vehicle->id,
                                'seat_number' => $seatNumber,
                                'row' => $row,
                                'column' => $col,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                            $seatCount++;
                        }
                    }
                }
                break;
                
            case '1-2': // 1 kursi kiri, 2 kursi kanan
                $rows = ceil($vehicle->seat_capacity / 3);
                for ($row = 1; $row <= $rows; $row++) {
                    $rowLetter = chr(64 + $row); // A, B, C, dst
                    for ($col = 1; $col <= 3; $col++) {
                        if ($seatCount < $vehicle->seat_capacity) {
                            $seatNumber = $rowLetter . $col;
                            $seats[] = [
                                'vehicle_id' => $vehicle->id,
                                'seat_number' => $seatNumber,
                                'row' => $row,
                                'column' => $col,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                            $seatCount++;
                        }
                    }
                }
                break;
        }
        
        Seat::insert($seats);
        
        return response()->json([
            'success' => true,
            'message' => "Successfully generated {$vehicle->seat_capacity} seats for vehicle {$vehicle->name}",
            'data' => SeatResource::collection(
                Seat::where('vehicle_id', $vehicle->id)
                    ->orderBy('row')
                    ->orderBy('column')
                    ->get()
            )
        ]);
    }

    /**
     * Get Detail Seat
     */
    public function show($id)
    {
        $seat = Seat::with('vehicle')->find($id);
        
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Seat not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SeatResource($seat)
        ]);
    }

    /**
    * Update Seat
    */
    public function update(Request $request, $id)
    {
        $seat = Seat::find($id);
        
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Seat not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'exists:vehicles,id',
            'seat_number' => 'string',
            'row' => 'integer|min:1',
            'column' => 'integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $seat->update($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new SeatResource($seat->load('vehicle'))
        ]);
    }

    /**
     * Delete Seat
     */
    public function destroy($id)
    {
        $seat = Seat::find($id);
        
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Seat not found'
            ], 404);
        }

        $seat->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Seat deleted successfully'
        ]);
    }
}