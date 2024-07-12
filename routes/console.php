<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command('tier4:notify-staff-overdue-meetings')->dailyAt('07:00');
Schedule::command('tier4:trim-activity-table')->sundays()->at('04:00');
