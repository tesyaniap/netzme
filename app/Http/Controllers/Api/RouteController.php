<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
    /**
     * Get Semua Routes
     */
    public function index()
    {
        $routes = Route::with([
            'originCity',
            'destinationCity',
            'departureTerminal',
            'arrivalTerminal'
        ])->get();
        
        return response()->json([
            'success' => true,
            'data' => RouteResource::collection($routes)
        ]);
    }

    /**
     * Create New Route
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_city_id' => 'required|exists:cities,id',
            'destination_city_id' => 'required|exists:cities,id|different:origin_city_id',
            'departure_terminal_id' => 'required|exists:terminals,id',
            'arrival_terminal_id' => 'required|exists:terminals,id|different:departure_terminal_id',
            'distance' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $route = Route::create($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new RouteResource($route->load(['originCity', 'destinationCity', 'departureTerminal', 'arrivalTerminal']))
        ], 201);
    }

    /**
     * Get Detail Route
     */
    public function show($id)
    {
        $route = Route::with(['originCity', 'destinationCity', 'departureTerminal', 'arrivalTerminal'])->find($id);
        
        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RouteResource($route)
        ]);
    }

    /**
    * Update Route
    */
    public function update(Request $request, $id)
    {
        $route = Route::find($id);
        
        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'origin_city_id' => 'exists:cities,id',
            'destination_city_id' => 'exists:cities,id|different:origin_city_id',
            'departure_terminal_id' => 'exists:terminals,id',
            'arrival_terminal_id' => 'exists:terminals,id|different:departure_terminal_id',
            'distance' => 'integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $route->update($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new RouteResource($route->load(['originCity', 'destinationCity', 'departureTerminal', 'arrivalTerminal']))
        ]);
    }

    /**
     * Delete Route
     */
    public function destroy($id)
    {
        $route = Route::find($id);
        
        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        $route->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Route deleted successfully'
        ]);
    }
}