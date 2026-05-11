<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatListController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\AiBatchController;
use App\Http\Controllers\ReportController;

// Jadikan halaman Daftar Chat sebagai halaman utama (ketika buka 127.0.0.1:8000)
Route::get('/', function () {
    return redirect()->route('chat.list');
});

// Rute untuk Halaman Daftar Chat (Split View)
Route::get('/daftar-chat', [ChatListController::class, 'index'])->name('chat.list');

// Rute untuk menampilkan Dashboard API Telemetry
Route::get('/api-telemetry', [TelemetryController::class, 'index'])->name('telemetry.index');

// Rute untuk Pipeline Kanban Board
Route::get('/pipeline', [\App\Http\Controllers\PipelineController::class, 'index'])->name('pipeline.index');
Route::post('/pipeline/update-status', [\App\Http\Controllers\PipelineController::class, 'updateStatus'])->name('pipeline.updateStatus');

// Rute untuk memicu proses AI Summary (Batch Job)
Route::post('/pipeline/trigger-ai', [AiBatchController::class, 'startBatch'])->name('pipeline.triggerAi');
Route::get('/pipeline/progress', [AiBatchController::class, 'checkProgress'])->name('pipeline.progress');

// Rute untuk mengambil detail lengkap pasien (untuk Modal di Pipeline)
Route::post('/pipeline/detail', [\App\Http\Controllers\PipelineController::class, 'getDetail'])->name('pipeline.detail');

// Rute untuk mengambil hasil rekap AI terbaru (untuk Modal di Pipeline)
Route::get('/pipeline/recap-result', [\App\Http\Controllers\AiBatchController::class, 'getRecapResult'])->name('pipeline.recapResult');

// Rute untuk update manual (override) data pasien di Pipeline
Route::post('/pipeline/update-manual', [\App\Http\Controllers\PipelineController::class, 'updateManual']);

// Rute untuk halaman Laporan (Report)
Route::get('/laporan', [ReportController::class, 'index'])->name('laporan.index');