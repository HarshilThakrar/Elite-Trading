<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Database Backups (Daily at 1:00 AM)
Schedule::command('backup:run')->dailyAt('01:00');

// Schedule Smart Reorder Generation (Daily at Midnight)
Schedule::command('app:generate-smart-reorder-plan')->daily();

// Schedule Inventory Alerts (Daily at 8:00 AM)
Schedule::command('app:send-inventory-alerts')->dailyAt('08:00');

// Fetch New Emails from IMAP
Schedule::command('mails:fetch')->everyFiveMinutes();
