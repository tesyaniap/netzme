<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'origin_city_id' => $this->origin_city_id,
            'destination_city_id' => $this->destination_city_id,
            'departure_terminal_id' => $this->departure_terminal_id,
            'arrival_terminal_id' => $this->arrival_terminal_id,
            'origin_city' => new CityResource($this->whenLoaded('originCity')),
            'destination_city' => new CityResource($this->whenLoaded('destinationCity')),
            'departure_terminal' => new TerminalResource($this->whenLoaded('departureTerminal')),
            'arrival_terminal' => new TerminalResource($this->whenLoaded('arrivalTerminal')),
            'distance' => $this->distance,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
