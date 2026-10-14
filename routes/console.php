<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled background tasks for conversions
Schedule::command('conversions:sync-stuck')->everyFifteenMinutes();
Schedule::command('conversions:cleanup')->daily();
