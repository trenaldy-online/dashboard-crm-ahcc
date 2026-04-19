<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaChat;
use App\Models\LeadSummary;
use Carbon\Carbon;

class TelemetryController extends Controller
{
    public function index()
    {
        $labels = [];
        $totalChatMasuk = [];
        $totalChatDisummary = [];

        // Looping untuk mengambil data 7 hari terakhir
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now('Asia/Jakarta')->subDays($i);
            $dateString = $date->format('Y-m-d');
            
            $labels[] = $date->format('d M'); 
            $totalChatMasuk[] = \App\Models\WaChat::whereDate('chat_time', $dateString)->count();
            $totalChatDisummary[] = \App\Models\LeadSummary::whereDate('created_at', $dateString)->count();
        }

        // --- TAMBAHAN BARU: Ambil 5 ringkasan AI terbaru ---
        $recentSummaries = \App\Models\LeadSummary::orderBy('updated_at', 'desc')->take(5)->get();

        return view('api-telemetry', compact('labels', 'totalChatMasuk', 'totalChatDisummary', 'recentSummaries'));
    }
}