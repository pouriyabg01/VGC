<?php

use App\Enums\Platforms\PlatformEnum;

it('stores uppercase values and renders display labels', function () {
    expect(PlatformEnum::PC->value)->toBe('PC')
        ->and(PlatformEnum::PLAYSTATION->value)->toBe('PLAYSTATION')
        ->and(PlatformEnum::XBOX->value)->toBe('XBOX')
        ->and(PlatformEnum::MOBILE->value)->toBe('MOBILE');

    expect(PlatformEnum::PLAYSTATION->label())->toBe('PlayStation')
        ->and(PlatformEnum::XBOX->label())->toBe('Xbox');
});

it('exposes options as value => label for select inputs', function () {
    expect(PlatformEnum::options())->toBe([
        'PC' => 'PC',
        'PLAYSTATION' => 'PlayStation',
        'XBOX' => 'Xbox',
        'MOBILE' => 'Mobile',
    ]);
});

it('rejects the legacy spellings that predated the enum', function () {
    foreach (['pc', 'ps', 'xbox', 'mobile', 'Playstation', 'Xbox', 'Mobile'] as $legacy) {
        expect(PlatformEnum::tryFrom($legacy))->toBeNull("'{$legacy}' must not resolve");
    }
});
