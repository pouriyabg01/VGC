<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tournament_id' => $this->tournamentMatch->tournament->id,
            'match_id' => $this->tournamentMatch->id,
            'user_id' => $request->user()->id,
            'scored_goals' => $this->scored_goals,
            'conceded_goals' => $this->conceded_goals,
        ];
    }
}
