<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TerminalResource;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TerminalController extends Controller
{
    /**
     * Get Semua Terminals
     * Filter by city_id
     */
    public function index(Request $request)
    {
        $query = Terminal::with('city');
        
        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        }
        
        $terminals = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => TerminalResource::collection($terminals)
        ]);
    }

    /**
     * Create New Terminal
    */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $terminal = Terminal::create($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new TerminalResource($terminal->load('city'))
        ], 201);
    }

    /**
     * Get Detail Terminal
     */
    public function show($id)
    {
        $terminal = Terminal::with('city')->find($id);
        
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TerminalResource($terminal)
        ]);
    }

    /**
     * Update Terminal
     */
    public function update(Request $request, $id)
    {
        $terminal = Terminal::find($id);
        
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'city_id' => 'exists:cities,id',
            'name' => 'string',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $terminal->update($request->all());
        
        return response()->json([
            'success' => true,
            'data' => new TerminalResource($terminal->load('city'))
        ]);
    }

    /**
     * Delete Terminal
     */
    public function destroy($id)
    {
        $terminal = Terminal::find($id);
        
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal not found'
            ], 404);
        }

        $terminal->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Terminal deleted successfully'
        ]);
    }
}
