<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
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
            'provider_id' => $this->provider_id,
            'insurer_id' => $this->insurer_id,
            'specialty' => $this->specialty,
            'batch_id' => $this->batch_id,
            'encounter_date' => $this->encounter_date,
            'submission_date' => $this->submission_date,
            'priority_level' => $this->priority_level,
            'total_value' => $this->total_value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
