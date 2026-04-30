<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// H.A.N.A akan melakukan patroli setiap jam 10 pagi
Schedule::command('hana:morning-scan')->dailyAt('10:00');
