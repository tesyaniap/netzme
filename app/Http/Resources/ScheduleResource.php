<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'route_id' => $this->route_id,
            'departure_time' => $this->departure_time,
            'arrival_time' => $this->arrival_time,
            'price' => $this->price,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'route' => new RouteResource($this->whenLoaded('route')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}