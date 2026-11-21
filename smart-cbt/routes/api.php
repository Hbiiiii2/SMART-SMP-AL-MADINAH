<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CbtApiController;

/*
|--------------------------------------------------------------------------
| CBT REST API Routes (PRD Version 2.0 Integration Contract)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health Check (Public service check)
    Route::get('/health', [CbtApiController::class, 'health'])->name('api.v1.health');

    // Protected API Endpoints for External Service Integration (E-Rapor Portal)
    Route::middleware([\App\Http\Middleware\ServiceAuthMiddleware::class, 'throttle:60,1'])->group(function () {
        // Exams
        Route::get('/exams', [CbtApiController::class, 'getExams'])->name('api.v1.exams');
        Route::get('/exams/{id}', [CbtApiController::class, 'getExamDetail'])->name('api.v1.exams.detail');

        // Attempts
        Route::get('/exam-attempts', [CbtApiController::class, 'getExamAttempts'])->name('api.v1.attempts');
        Route::get('/exam-attempts/{id}', [CbtApiController::class, 'getExamAttemptDetail'])->name('api.v1.attempts.detail');

        // Finalized Exam Results (Gradebook Integration)
        Route::get('/exam-results', [CbtApiController::class, 'getExamResults'])->name('api.v1.results');
        Route::get('/exam-results/{id}', [CbtApiController::class, 'getExamResultDetail'])->name('api.v1.results.detail');
    });
});
