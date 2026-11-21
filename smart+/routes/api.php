<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ERapporController;
use App\Http\Controllers\Api\RfidApiController;
use App\Http\Controllers\Api\AttendanceGpsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- RFID & GPS Attendance Routes ---
Route::name('api.')->group(function () {
    Route::post('/rfid/scan', [RfidApiController::class, 'scan'])->name('rfid.scan');
    Route::get('/rfid/stats', [RfidApiController::class, 'stats'])->name('rfid.stats');
    Route::post('/rfid/auto-mark-alfa', [RfidApiController::class, 'autoMarkAlfa'])->name('rfid.auto-mark-alfa');

    Route::post('/gps/check-in', [AttendanceGpsController::class, 'checkIn'])->name('gps.check-in');
    Route::post('/gps/check-out', [AttendanceGpsController::class, 'checkOut'])->name('gps.check-out');
});

// --- Standard REST API v1 (PRD Version 2.0 Integration Contract) ---
Route::prefix('v1')->group(function () {
    // Health Check (Public service check)
    Route::get('/health', [\App\Http\Controllers\Api\V1\SmartApiController::class, 'health'])->name('api.v1.smart.health');

    // Protected API Endpoints
    Route::middleware([\App\Http\Middleware\SmartServiceAuthMiddleware::class])->group(function () {
        // Students & Classes
        Route::get('/students', [\App\Http\Controllers\Api\V1\SmartApiController::class, 'getStudents'])->name('api.v1.smart.students');
        Route::get('/classes', [\App\Http\Controllers\Api\V1\SmartApiController::class, 'getClasses'])->name('api.v1.smart.classes');

        // Attendance Records
        Route::get('/attendance/records', [\App\Http\Controllers\Api\V1\SmartApiController::class, 'getAttendanceRecords'])->name('api.v1.smart.attendance.records');
        Route::get('/attendance/records/{id}', [\App\Http\Controllers\Api\V1\SmartApiController::class, 'getAttendanceRecordDetail'])->name('api.v1.smart.attendance.records.detail');

        // Attendance Summaries (for E-Rapor Report Card)
        Route::get('/attendance/summaries', [\App\Http\Controllers\Api\V1\SmartApiController::class, 'getAttendanceSummaries'])->name('api.v1.smart.attendance.summaries');

        // School Calendar & Holidays
        Route::get('/attendance/calendar', [\App\Http\Controllers\Api\V1\SmartApiController::class, 'getAttendanceCalendar'])->name('api.v1.smart.attendance.calendar');
    });
});


