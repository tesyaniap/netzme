<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    /**
     * Get Semua Cities
     */
    public function index()
    {
        $cities = City::all();
        
        return response()->json([
            'success' => true,
            'data' => CityResource::collection($cities)
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'province' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $city = City::create($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new CityResource($city)
        ], 201);
    }

    /**
     * Get City (id)
     */
    public function show($id)
    {
        $city = City::find($id);
        
        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CityResource($city)
        ]);
    }

    /**
     * Update City
     */
    public function update(Request $request, $id)
    {
        $city = City::find($id);
        
        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'province' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $city->update($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new CityResource($city)
        ]);
    }

    /**
     * Delete City
     */
    public function destroy($id)
    {
        $city = City::find($id);
        
        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found'
            ], 404);
        }

        $city->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'City deleted successfully'
        ]);
    }
}
