<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadSummary;
use App\Models\WaChat;
use Carbon\Carbon;

class PipelineController extends Controller
{
    // 1. Menampilkan Halaman Kanban Board
    public function index(Request $request)
    {
        // ==========================================
        // A. Kueri Khusus untuk Kanban Board (Bisa Difilter)
        // ==========================================
        $query = LeadSummary::query();

        // FILTER 1: Pencarian Bebas (Nomor WA, Kategori, atau Kendala)
        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('client_number', 'like', '%' . $request->search . '%')
                    ->orWhere('kategori_kanker', 'like', '%' . $request->search . '%')
                    ->orWhere('kendala_utama', 'like', '%' . $request->search . '%')
                    ->orWhere('ringkasan_medis', 'like', '%' . $request->search . '%');
            });
        });

        // FILTER 2: Filter Wajib Follow Up
        $query->when($request->has('perlu_fu') && $request->perlu_fu != '', function ($q) use ($request) {
            $q->where('perlu_follow_up', $request->perlu_fu);
        });

        // Tarik data yang SUDAH DIFILTER dari database
        $filteredLeads = $query->get();

        // Pecah data ke masing-masing kolom Kanban
        $leads = [
            'leads_baru' => $filteredLeads->where('pipeline_status', 'leads_baru'),
            'edukasi'    => $filteredLeads->where('pipeline_status', 'edukasi'),
            'konsultasi' => $filteredLeads->where('pipeline_status', 'konsultasi'),
            'deal'       => $filteredLeads->where('pipeline_status', 'deal'),
            'batal'      => $filteredLeads->where('pipeline_status', 'batal'),
        ];

        // ==========================================
        // B. Kueri Khusus H.A.N.A Morning Briefing (Kebal Filter)
        // ==========================================
        // Briefing H.A.N.A tidak boleh hilang/terpengaruh meskipun PA sedang mencari nama pasien tertentu.
        // Kueri di bawah ini langsung dieksekusi di level Database (jauh lebih ringan).
        
        $today = Carbon::today('Asia/Jakarta');

        $briefing = [
            // 1. Pasien yang menggantung/harus difollow-up
            'follow_up' => LeadSummary::where('perlu_follow_up', true)
                                    ->whereNotIn('pipeline_status', ['deal', 'batal'])
                                    ->orderBy('lead_score', 'desc')
                                    ->take(4)
                                    ->get(), 
            
            // 2. Hot Leads (Pasien dengan sinyal beli kuat > 60)
            'hot_leads' => LeadSummary::where('lead_score', '>=', 60)
                                    ->where('perlu_follow_up', false) 
                                    ->whereNotIn('pipeline_status', ['deal', 'batal'])
                                    ->orderBy('lead_score', 'desc')
                                    ->take(4)
                                    ->get(),

            // 3. Pasien baru masuk hari ini
            'baru_hari_ini' => LeadSummary::where('created_at', '>=', $today)
                                        ->take(4)
                                        ->get()
        ];

        return view('pipeline', compact('leads', 'briefing'));
    }

    // 2. Menerima Sinyal dari Drag & Drop (AJAX)
    public function updateStatus(Request $request)
    {
        $request->validate([
            'client_number' => 'required|string',
            'new_status'    => 'required|in:leads_baru,edukasi,konsultasi,deal,batal'
        ]);

        $lead = LeadSummary::where('client_number', $request->client_number)->first();
        
        if ($lead) {
            $lead->update([
                'pipeline_status' => $request->new_status,
                'is_human_validated' => true // Tandai bahwa manusia sudah ikut campur
            ]);
            return response()->json(['success' => true, 'message' => 'Status berhasil diperbarui']);
        }

        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }

    // 3. Mengambil Detail Lengkap untuk Modal (AJAX)
    public function getDetail(Request $request)
    {
        $request->validate(['client_number' => 'required|string']);

        $clientNumber = $request->client_number;
        
        $lead = LeadSummary::where('client_number', $clientNumber)->first();
        $chats = WaChat::where('client_number', $clientNumber)
                       ->orderBy('chat_time', 'asc')
                       ->get();

        if (!$lead) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Memformat waktu chat agar mudah dibaca oleh Javascript
        $formattedChats = $chats->map(function($chat) {
            return [
                'is_me' => $chat->is_me,
                'message' => $chat->message,
                'time' => Carbon::parse($chat->chat_time)->timezone('Asia/Jakarta')->format('d M Y, H:i')
            ];
        });

        return response()->json([
            'success' => true,
            'lead' => $lead,
            'chats' => $formattedChats
        ]);
    }

    // 4. Update Manual Data Pasien (AJAX)
    public function updateManual(Request $request)
    {
        $lead = LeadSummary::where('client_number', $request->client_number)->first();
        
        if ($lead) {
            // Jika tombol yang ditekan adalah "Selesai Follow Up"
            if ($request->has('perlu_follow_up')) {
                $lead->perlu_follow_up = filter_var($request->perlu_follow_up, FILTER_VALIDATE_BOOLEAN);
                if (!$lead->perlu_follow_up) {
                    $lead->alasan_follow_up = null; // Bersihkan alasan
                }
            }
            
            // Jika tombol yang ditekan adalah pindah kolom Kanban
            if ($request->has('pipeline_status')) {
                $lead->pipeline_status = $request->pipeline_status;
            }
            
            $lead->save();
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false], 404);
    }
}