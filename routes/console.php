<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('enrollments:close-expired')->dailyAt('00:05');

Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt('02:00');

Schedule::command('push:send-review-reminders')->dailyAt('08:00');

Schedule::command('gdpr:export --cleanup-only')->dailyAt('03:00');
