<?php

use App\Enums\Platforms\PlatformEnum;
use App\Models\Platform;
use App\Models\Tournament;
use App\Models\User;

it('serves the landing page under the VGC brand', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain(config('app.name'))
        ->and($html)->not->toContain('Focuslane')
        ->and($html)->not->toContain('to-do lists');
});

it('shows each player on the tournament platform with their nickname', function () {
    $t = Tournament::factory()->create(['platform' => PlatformEnum::XBOX, 'capacity' => 8]);
    $user = User::factory()->create(['name' => 'Pouriya']);
    Platform::factory()->for($user)->on(PlatformEnum::XBOX)->create(['nickname' => 'xbox_tag']);
    Platform::factory()->for($user)->on(PlatformEnum::PC)->create(['nickname' => 'pc_tag']);
    $t->players()->attach($user->id);

    $html = $this->get(route('tournament', $t))->assertOk()->getContent();

    expect($html)->toContain('Pouriya')
        ->and($html)->toContain('xbox_tag')   // the account they are entered with
        ->and($html)->not->toContain('pc_tag');
});

it('tells a player why sign-up is unavailable rather than showing the button', function () {
    $t = Tournament::factory()->create(['platform' => PlatformEnum::XBOX, 'capacity' => 8]);

    // Subscribed, wrong platform -> platform notice, not the subscription line.
    $html = $this->actingAs(playerOn(PlatformEnum::PLAYSTATION))
        ->get(route('tournament', $t))->assertOk()->getContent();
    expect($html)->toContain('Xbox account required')
        ->and($html)->not->toContain('You need an active subscription');

    // No subscription -> subscription line, not the platform notice.
    $user = User::factory()->create();
    Platform::factory()->for($user)->on(PlatformEnum::XBOX)->create();
    $html = $this->actingAs($user)->get(route('tournament', $t))->assertOk()->getContent();
    expect($html)->toContain('You need an active subscription')
        ->and($html)->not->toContain('account required');
});

it('offers every platform in the profile select, valued by enum', function () {
    $this->actingAs(User::factory()->create());

    $html = $this->get(route('profile'))->assertOk()->getContent();

    foreach (PlatformEnum::cases() as $case) {
        expect($html)->toContain('value="'.$case->value.'"')
            ->and($html)->toContain('>'.$case->label().'</option>');
    }
});

it('points a signed-in visitor at the brackets, not at signing up', function () {
    $html = $this->actingAs(User::factory()->create())->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('View tournaments')
        ->and($html)->toContain('id="tournaments"')
        // An account prompt is noise once somebody is already in.
        ->and($html)->not->toContain('Create your account');
});

it('asks a guest to sign up but still shows them the draws', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    // Signing up sight unseen is a leap of faith, so the brackets are one
    // link away for a guest too.
    expect($html)->toContain('Create your account')
        ->and($html)->toContain('View tournaments')
        ->and($html)->toContain('id="tournaments"');
});
