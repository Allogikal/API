<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'table' => $this->table,
            'status' => $this->status,
            'workers' => $this->users,
            'positions' => $this->positions,
            'price_all' => $this->price_all,
        ];
    }
}
