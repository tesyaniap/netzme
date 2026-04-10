<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
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
            'code' => $this->code,
            'province' => $this->province,
            'terminals_count' => $this->when(isset($this->terminals_count), $this->terminals_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
