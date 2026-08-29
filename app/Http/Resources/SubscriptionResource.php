<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sub_id' => $this->id,
            'user_id' => $this->pivot->user_id,
            'plan_id' => $this->pivot->plan_id,
            'plan_title' => $this->title,
            'status' => $this->pivot->status,
            'tournament_entries_left' => (int) $this->pivot->tournament_entries_left,
            'vs_games_left' => (int) $this->pivot->vs_games_left,
            'started_at' => $this->pivot->created_at
        ];
    }
}
