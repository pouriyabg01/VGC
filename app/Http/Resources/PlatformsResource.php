<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'platforms' => $this->platforms->map(function ($platforms) {
                return [
                    'id' => $platforms->id,
                    'nickname' => $platforms->nickname,
                    'platform' => $platforms->platform,
                ];
            })->values()
        ];
    }
}
