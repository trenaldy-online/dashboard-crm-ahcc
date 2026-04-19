<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaChat;
use App\Models\LeadSummary;

class CrmController extends Controller
{
    public function index()
    {
        // 1. Ambil semua nomor klien yang pernah chat (tanpa duplikat)
        $clients = WaChat::select('client_number')->distinct()->get();
        
        // 2. Ambil semua data summary AI yang sudah ada, jadikan nomor HP sebagai kuncinya
        $summaries = LeadSummary::all()->keyBy('client_number');

        return view('crm_board', compact('clients', 'summaries'));
    }

    // Fungsi untuk menyetujui hasil AI
    public function approve($clientNumber)
    {
        $summary = LeadSummary::where('client_number', $clientNumber)->first();
        if ($summary) {
            $summary->update(['status_review' => 'disetujui']);
            return back()->with('success', 'Keren! Data ' . $clientNumber . ' resmi disetujui & masuk ke laporan.');
        }
        return back()->with('error', 'Data tidak ditemukan.');
    }

    // Fungsi untuk menolak/menghapus hasil AI (jika AI ngelantur)
    public function reject($clientNumber)
    {
        $summary = LeadSummary::where('client_number', $clientNumber)->first();
        if ($summary) {
            $summary->delete(); // Hapus rekap agar bisa digenerate ulang
            return back()->with('success', 'Data ditolak. Silakan generate ulang dengan AI.');
        }
        return back()->with('error', 'Data tidak ditemukan.');
    }
}