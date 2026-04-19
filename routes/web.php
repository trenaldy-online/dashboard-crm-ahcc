<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AiSummaryController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\ChatListController;

// Jika orang membuka halaman utama, langsung arahkan ke dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Pintu masuk untuk melihat dashboard
Route::get('/dashboard', [DashboardController::class, 'index']);

// --- Rute AI CRM ---
Route::post('/generate-summary/{client_number}', [AiSummaryController::class, 'generate'])->name('ai.summary');

// Rute untuk melihat halaman meja kerja CRM
Route::get('/crm-board', [CrmController::class, 'index'])->name('crm.board');

// Rute untuk aksi tombol Setujui dan Tolak (Hapus)
Route::post('/crm-board/approve/{client_number}', [CrmController::class, 'approve'])->name('crm.approve');
Route::post('/crm-board/reject/{client_number}', [CrmController::class, 'reject'])->name('crm.reject');

// Rute untuk Laporan Manajemen
Route::get('/management-report', [ReportController::class, 'index'])->name('report.index');

// Rute untuk meminta AI membuatkan draf pesan Follow-Up
Route::post('/crm-board/follow-up/{client_number}', [App\Http\Controllers\AiSummaryController::class, 'generateFollowUp'])->name('ai.followup');

// Rute untuk menampilkan Dashboard API Telemetry
Route::get('/api-telemetry', [TelemetryController::class, 'index'])->name('telemetry.index');

// Rute untuk Halaman Daftar Chat (Split View)
Route::get('/daftar-chat', [ChatListController::class, 'index'])->name('chat.list');