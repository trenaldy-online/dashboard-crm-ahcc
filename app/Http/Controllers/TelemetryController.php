<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaChat; 
use App\Models\LeadSummary;
use Carbon\Carbon;
use App\Models\DailyBriefing;

class TelemetryController extends Controller
{
    public function index()
    {
        $labels = [];
        $totalChatMasuk = [];

        // Looping untuk mengambil data 7 hari terakhir
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now('Asia/Jakarta')->subDays($i);
            $dateString = $date->format('Y-m-d');
            
            $labels[] = $date->format('d M'); 
            // Menghitung jumlah chat masuk dari Extension
            $totalChatMasuk[] = WaChat::whereDate('chat_time', $dateString)->count();
        }

        // Pengaman tampilan view jika data asli belum ada
        $totalChatDisummary = array_fill(0, 7, 0); 
        $recentSummaries = collect([]); 

        // =========================================================
        // DATA UNTUK H.A.N.A MORNING BRIEFING
        // =========================================================
        $allLeads = LeadSummary::all();
        $today = Carbon::today('Asia/Jakarta');

        $briefing = [
            // 1. Pasien yang menggantung/harus difollow-up (TAMPILKAN SEMUA)
            'follow_up' => $allLeads->where('perlu_follow_up', true)
                                    ->whereNotIn('pipeline_status', ['deal', 'batal'])
                                    ->sortByDesc('lead_score'), 
            
            // 2. Hot Leads (Pasien dengan sinyal beli kuat >= 60)
            'hot_leads' => $allLeads->where('lead_score', '>=', 60)
                                    ->where('perlu_follow_up', false)
                                    ->whereNotIn('pipeline_status', ['deal', 'batal'])
                                    ->sortByDesc('lead_score')
                                    ->take(5),

            // 3. Pasien baru masuk hari ini
            'baru_hari_ini' => $allLeads->where('created_at', '>=', $today)
                                        ->sortByDesc('created_at')
                                        ->take(5)
        ];

        // =========================================================
        // AMBIL SURAT BRIEFING NARATIF & RIWAYAT (Untuk Lonceng)
        // =========================================================
        
        // Ambil surat hari ini untuk Pop-up utama
        $briefingPagi = DailyBriefing::latest('tanggal_briefing')->first();
        
        // AMBIL RIWAYAT 7 HARI TERAKHIR UNTUK LONCENG (Ini yang tadi hilang!)
        $briefingHistory = DailyBriefing::orderBy('tanggal_briefing', 'desc')->take(7)->get();

        // GABUNGKAN SEMUA VARIABEL DALAM SATU RETURN
        return view('api-telemetry', compact(
            'labels', 
            'totalChatMasuk', 
            'totalChatDisummary', 
            'recentSummaries', 
            'briefing', 
            'briefingPagi',
            'briefingHistory'
        ));
    }
}