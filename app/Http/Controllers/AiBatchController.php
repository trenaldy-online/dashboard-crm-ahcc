<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaChat;
use App\Jobs\ProcessAiSummaryJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiBatchController extends Controller
{
    // Memicu pekerjaan massal
    public function startBatch()
    {
        // Cari semua nomor unik yang punya aktivitas hari ini
        $today = Carbon::today()->toDateString();
        
        $clientsToday = WaChat::whereDate('chat_time', $today)
                              ->select('client_number')
                              ->distinct()
                              ->pluck('client_number');

        if ($clientsToday->isEmpty()) {
            return response()->json(['message' => 'Tidak ada chat pasien hari ini.'], 404);
        }

        // Siapkan antrean (Jobs)
        $jobs = [];
        foreach ($clientsToday as $client) {
            $jobs[] = new ProcessAiSummaryJob($client);
        }

        // Lemparkan antrean ke latar belakang
        $batch = Bus::batch($jobs)->name('Rekap AI Harian')->dispatch();

        return response()->json([
            'message' => 'Proses AI sedang berjalan di latar belakang.',
            'batch_id' => $batch->id
        ]);
    }

    // Mengecek sudah berapa persen selesai (Untuk Progress Bar di UI)
    public function checkProgress(Request $request)
    {
        $batchId = $request->query('id');
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json(['progress' => 0, 'finished' => false]);
        }

        return response()->json([
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'total_jobs' => $batch->totalJobs,
            'processed' => $batch->processedJobs()
        ]);
    }

    // Mengambil hasil rekapitulasi hari ini untuk ditampilkan di UI
    public function getRecapResult()
    {
        $today = \Carbon\Carbon::today()->toDateString();
        
        // Ambil data yang diupdate hari ini
        $results = \App\Models\LeadSummary::whereDate('updated_at', $today)
                    ->orderBy('updated_at', 'desc')
                    ->get(['client_number', 'pipeline_status', 'is_human_validated']);

        return response()->json(['success' => true, 'results' => $results]);
    }
}