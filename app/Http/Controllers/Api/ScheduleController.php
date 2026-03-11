<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Get Semua schedules
     */
    public function index()
    {
        $schedules = Schedule::with(['vehicle', 'route'])->get();
        
        return response()->json([
            'success' => true,
            'data' => ScheduleResource::collection($schedules)
        ]);
    }

    /**
     * Create New Schedule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'departure_time' => 'required|date_format:H:i',
            'arrival_time' => 'required|date_format:H:i|after:departure_time',
            'price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule = Schedule::create($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new ScheduleResource($schedule->load(['vehicle', 'route']))
        ], 201);
    }

    /**
    * Get Detail Schedule
    */
    public function show($id)
    {
        $schedule = Schedule::with(['vehicle', 'route'])->find($id);
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ScheduleResource($schedule)
        ]);
    }

    /**
     * Update Schedule
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::find($id);
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'exists:vehicles,id',
            'route_id' => 'exists:routes,id',
            'departure_time' => 'date_format:H:i',
            'arrival_time' => 'date_format:H:i|after:departure_time',
            'price' => 'numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule->update($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new ScheduleResource($schedule->load(['vehicle', 'route']))
        ]);
    }

    /**
     * Delete Schedule
     */
    public function destroy($id)
    {
        $schedule = Schedule::find($id);
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        $schedule->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
    }
}