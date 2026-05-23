<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'number_of_people' => $this->number_of_people,
            'total_price' => $this->total_price,
            'travel_date' => $this->travel_date,
            'user' => $this->whenLoaded('user'),
            'arrangement' => $this->whenLoaded('arrangement'), 
        ];
    }
}
