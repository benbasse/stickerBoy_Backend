<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Vérifier les paiements NabooPay en attente toutes les 5 minutes
Schedule::command('orders:check-pending --hours=24')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
