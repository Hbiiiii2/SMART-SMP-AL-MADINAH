<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Mock time to 13:45:00
\Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-08-25 13:45:00', 'Asia/Jakarta'));

$service = app(App\Services\AttendanceService::class);
echo json_encode($service->processRfidScan('0003803162'), JSON_PRETTY_PRINT);
