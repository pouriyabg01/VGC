<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// A match only stalls its tournament for as long as nobody notices, so the
// deadline sweep runs often rather than nightly.
Schedule::command('matches:forfeit-overdue')->everyFifteenMinutes()->withoutOverlapping();
