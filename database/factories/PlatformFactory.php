<?php

namespace Database\Factories;

use App\Enums\Platforms\PlatformEnum;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Platform>
 */
class PlatformFactory extends Factory
{
    protected $model = Platform::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nickname' => fake()->userName(),
            'platform' => fake()->randomElement(PlatformEnum::cases()),
        ];
    }

    /**
     * Pin the account to one platform, e.g. to match the tournament a player
     * signed up for.
     */
    public function on(PlatformEnum $platform): static
    {
        return $this->state(fn () => ['platform' => $platform]);
    }
}
