<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Get all vehicles
     * 
     * Admin can see all vehicles, Mitra can only see their own vehicles
     */
    public function index()
    {
        $user = auth()->user();
        
        // Debug info
        $roles = $user->getRoleNames();
        $hasAdminRole = $user->hasRole('admin');
        
        if (!$hasAdminRole) {
            return response()->json([
                'debug' => [
                    'user_id' => $user->id,
                    'user_roles' => $roles,
                    'has_admin_role' => $hasAdminRole
                ],
                'message' => 'Debug: User does not have admin role'
            ], 403);
        }
        
        $vehicles = Vehicle::with('partner')->get();
        
        return response()->json([
            'success' => true,
            'data' => VehicleResource::collection($vehicles)
        ]);
    }

    /**
     * Create a single vehicle
     * 
     * Only admin can create vehicles
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'plate_number' => 'required|string|unique:vehicles',
            'seat_capacity' => 'required|integer|min:1',
            'seat_layout' => 'required|in:2-2,2-3,1-2',
            'partner_id' => 'required|exists:mitra,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicle = Vehicle::create($request->all());
        return response()->json([
            'success' => true,
            'data' => new VehicleResource($vehicle->load('partner'))
        ], 201);
    }

    /**
     * Generate bulk vehicle form
     * 
     * Generate form data for bulk vehicle creation based on count
     */
    public function generateBulkForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|exists:mitra,id',
            'count' => 'required|integer|min:1|max:50',
            'base_name' => 'required|string',
            'seat_capacity' => 'required|integer|min:1',
            'seat_layout' => 'required|in:2-2,2-3,1-2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $partner = Mitra::find($request->partner_id);
        $vehicles = [];
        
        for ($i = 1; $i <= $request->count; $i++) {
            $vehicles[] = [
                'index' => $i,
                'name' => $request->base_name . ' ' . $i,
                'plate_number' => strtoupper($partner->code) . ' ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'seat_capacity' => $request->seat_capacity,
                'seat_layout' => $request->seat_layout
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'partner' => [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'code' => $partner->code
                ],
                'vehicles' => $vehicles,
                'form_config' => [
                    'base_name' => $request->base_name,
                    'seat_capacity' => $request->seat_capacity,
                    'seat_layout' => $request->seat_layout,
                    'count' => $request->count
                ]
            ]
        ]);
    }

    /**
     * Bulk create vehicles from form data
     * 
     * Create multiple vehicles from submitted form data
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|exists:mitra,id',
            'vehicles' => 'required|array|min:1|max:50',
            'vehicles.*.name' => 'required|string',
            'vehicles.*.plate_number' => 'required|string|unique:vehicles,plate_number',
            'vehicles.*.seat_capacity' => 'required|integer|min:1',
            'vehicles.*.seat_layout' => 'required|in:2-2,2-3,1-2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicleData = [];
        foreach ($request->vehicles as $vehicle) {
            $vehicleData[] = [
                'name' => $vehicle['name'],
                'plate_number' => $vehicle['plate_number'],
                'seat_capacity' => $vehicle['seat_capacity'],
                'seat_layout' => $vehicle['seat_layout'],
                'partner_id' => $request->partner_id,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        Vehicle::insert($vehicleData);

        return response()->json([
            'success' => true,
            'message' => "Successfully created " . count($request->vehicles) . " vehicles",
            'data' => VehicleResource::collection(
                Vehicle::where('partner_id', $request->partner_id)
                       ->latest()
                       ->take(count($request->vehicles))
                       ->with('partner')
                       ->get()
            )
        ], 201);
    }

    /**
     * Get vehicle details
     * 
     * Admin can see any vehicle, Mitra can only see their own vehicles
     */
    public function show($id)
    {
        $user = auth()->user();
        
        if ($user->hasRole('admin')) {
            $vehicle = Vehicle::with('partner')->find($id);
        } else {
            // Mitra hanya bisa lihat vehicle miliknya
            $vehicle = Vehicle::with('partner')
                            ->where('partner_id', $user->mitra_id)
                            ->find($id);
        }
        
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new VehicleResource($vehicle)
        ]);
    }

    /**
     * Update vehicle
     * 
     * Only admin can update vehicles
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);
        
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'plate_number' => 'string|unique:vehicles,plate_number,' . $id,
            'seat_capacity' => 'integer|min:1',
            'seat_layout' => 'in:2-2,2-3,1-2',
            'partner_id' => 'exists:mitra,id',
            'status' => 'in:active,maintenance,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicle->update($request->all());
        return response()->json([
            'success' => true,
            'data' => new VehicleResource($vehicle->load('partner'))
        ]);
    }

    /**
     * Delete vehicle
     * 
     * Only admin can delete vehicles
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::find($id);
        
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found'
            ], 404);
        }

        $vehicle->delete();
        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully'
        ]);
    }
}