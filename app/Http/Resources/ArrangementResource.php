<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArrangementResource extends JsonResource
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
            'title' => $this->title,
            'destination' => $this->destination,
            'price' => $this->price,
            'duration_days' => $this->duration_days,
            'description' => $this->description,
            'discount_percent' => $this->discount_percent,
            'is_last_minute' => (bool) $this->is_last_minute,
        ];
    }
}
