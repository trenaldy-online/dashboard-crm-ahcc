<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaChat;
use Illuminate\Support\Facades\DB;

class ChatListController extends Controller
{
    public function index(Request $request)
    {
        // 1. Kueri Dasar: Gabungkan wa_chats dengan lead_summaries menggunakan LEFT JOIN
        $query = WaChat::select(
                'wa_chats.client_number', 
                DB::raw('MAX(wa_chats.chat_time) as last_activity'),
                // Kita ambil juga data dari lead_summaries agar bisa ditampilkan jika diperlukan
                'lead_summaries.pipeline_status',
                'lead_summaries.perlu_follow_up'
            )
            ->leftJoin('lead_summaries', 'wa_chats.client_number', '=', 'lead_summaries.client_number')
            // Wajib di-group berdasarkan semua kolom yang di-select (aturan ketat MySQL)
            ->groupBy(
                'wa_chats.client_number', 
                'lead_summaries.pipeline_status', 
                'lead_summaries.perlu_follow_up'
            );

        // ==========================================
        // 2. GERBANG FILTER & SEARCH (Bekerja Otomatis)
        // ==========================================

        // A. Filter Pencarian Bebas (Cari Nomor, Kendala, atau Kategori Kanker)
        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('wa_chats.client_number', 'like', '%' . $request->search . '%')
                    ->orWhere('lead_summaries.kategori_kanker', 'like', '%' . $request->search . '%')
                    ->orWhere('lead_summaries.kendala_utama', 'like', '%' . $request->search . '%')
                    ->orWhere('lead_summaries.ringkasan_medis', 'like', '%' . $request->search . '%');
            });
        });

        // B. Filter Status Pipeline (Dropdown)
        $query->when($request->pipeline_status, function ($q) use ($request) {
            $q->where('lead_summaries.pipeline_status', $request->pipeline_status);
        });

        // C. Filter Wajib Follow Up (Dropdown)
        // Pakai has() dan cek != '' karena value '0' (Aman) dianggap false/kosong oleh PHP
        $query->when($request->has('perlu_fu') && $request->perlu_fu != '', function ($q) use ($request) {
            $q->where('lead_summaries.perlu_follow_up', $request->perlu_fu);
        });

        // 3. Eksekusi Data Klien (Tarik dari Database)
        $clients = $query->orderBy('last_activity', 'desc')->get();

        // ==========================================
        // 4. LOGIKA CHAT AKTIF (Tetap Dipertahankan)
        // ==========================================

        $activeClient = $request->query('client');
        
        // Jika tidak ada parameter client di URL, pilih data urutan pertama dari hasil filter
        if (!$activeClient && $clients->count() > 0) {
            $activeClient = $clients->first()->client_number;
        }

        $activeChats = [];
        if ($activeClient) {
            $activeChats = WaChat::where('client_number', $activeClient)
                                 ->orderBy('chat_time', 'asc') // Ascending: Lama di atas, Baru di bawah
                                 ->get();
        }

        // 5. Lempar ke Blade (Jangan lupa memastikan variabel pencarian tetap menempel)
        return view('chat-list', compact('clients', 'activeClient', 'activeChats'));
    }
}