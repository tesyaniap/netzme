<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'passenger_id' => $this->passenger_id,
            'schedule_id' => $this->schedule_id,
            'seat_id' => $this->seat_id,
            'price' => $this->price,
            'status' => $this->status,
            'passenger' => $this->whenLoaded('passenger', function () {
                return [
                    'id' => $this->passenger->id,
                    'name' => $this->passenger->name,
                    'email' => $this->passenger->email
                ];
            }),
            'schedule' => new ScheduleResource($this->whenLoaded('schedule')),
            'seat' => new SeatResource($this->whenLoaded('seat')),
            'reschedules' => TicketRescheduleResource::collection($this->whenLoaded('reschedules')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}