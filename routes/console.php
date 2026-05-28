<?php

use App\Models\LectureSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:lock-expired', function () {
    $count = LectureSession::query()
        ->where('status', 'conducted')
        ->whereNull('locked_at')
        ->whereNotNull('submitted_at')
        ->where('submitted_at', '<=', now()->subHours(24))
        ->update([
            'status' => 'locked',
            'locked_at' => now(),
        ]);

    $this->info("Locked {$count} expired attendance session(s).");
})->purpose('Lock attendance sessions after the 24-hour edit window');

Schedule::command('attendance:lock-expired')->hourly();
