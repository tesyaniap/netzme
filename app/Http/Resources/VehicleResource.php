<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'plate_number' => $this->plate_number,
            'seat_capacity' => $this->seat_capacity,
            'seat_layout' => $this->seat_layout,
            'status' => $this->status,
            'partner' => [
                'id' => $this->partner->id,
                'name' => $this->partner->name,
                'code' => $this->partner->code,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
