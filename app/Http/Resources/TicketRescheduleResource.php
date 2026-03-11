<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketRescheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'old_schedule_id' => $this->old_schedule_id,
            'new_schedule_id' => $this->new_schedule_id,
            'old_seat_id' => $this->old_seat_id,
            'new_seat_id' => $this->new_seat_id,
            'reschedule_fee' => $this->reschedule_fee,
            'rescheduled_at' => $this->rescheduled_at,
            'old_schedule' => new ScheduleResource($this->whenLoaded('oldSchedule')),
            'new_schedule' => new ScheduleResource($this->whenLoaded('newSchedule')),
            'old_seat' => new SeatResource($this->whenLoaded('oldSeat')),
            'new_seat' => new SeatResource($this->whenLoaded('newSeat')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}