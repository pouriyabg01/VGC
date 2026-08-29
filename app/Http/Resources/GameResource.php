<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            // The stored path is an implementation detail of the disk; a
            // client needs something it can fetch.
            'image_url' => $this->imageUrl(),
            // Whether the site puts this game on today. Only the rest can be
            // voted for.
            'is_active' => (bool) $this->is_active,
            'votes' => $this->voteCount(),
            'votes_target' => (int) $this->votes_target,
            'vote_percent' => $this->votePercent(),
        ];
    }
}
