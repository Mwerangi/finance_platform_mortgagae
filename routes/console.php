<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily tasks
Schedule::command('loans:update-overdue-installments')
    ->daily()
    ->at('00:05')
    ->description('Mark overdue installments and calculate days past due');
