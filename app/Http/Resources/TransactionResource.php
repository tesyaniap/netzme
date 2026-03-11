<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trx_code' => $this->trx_code,
            'mitra_id' => $this->mitra_id,
            'user_id' => $this->user_id,
            'schedule_id' => $this->schedule_id,
            'provider_code' => $this->provider_code,
            'route' => $this->route,
            'travel_date' => $this->travel_date,
            'payment_type' => $this->payment_type,
            'passenger_count' => $this->passenger_count,
            'base_price' => $this->base_price,
            'admin_fee' => $this->admin_fee,
            'service_fee' => $this->service_fee,
            'amount' => $this->amount,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'notes' => $this->notes,
            'booked_at' => $this->booked_at,
            'paid_at' => $this->paid_at,
            'issued_at' => $this->issued_at,
            'cancelled_at' => $this->cancelled_at,
            'provider_response' => $this->provider_response,
            'mitra' => $this->whenLoaded('mitra', function () {
                return [
                    'id' => $this->mitra->id,
                    'name' => $this->mitra->name,
                    'code' => $this->mitra->code
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email
                ];
            }),
            'schedule' => new ScheduleResource($this->whenLoaded('schedule')),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}