<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaChat;

class ChatListController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil daftar semua nomor unik yang pernah chat
        $clients = WaChat::select('client_number')->distinct()->get();

        // 2. Tentukan nomor mana yang sedang aktif (diklik). 
        // Jika tidak ada yang diklik, otomatis pilih nomor pertama dari daftar.
        $activeClient = $request->query('client');
        if (!$activeClient && $clients->count() > 0) {
            $activeClient = $clients->first()->client_number;
        }

        // 3. Ambil riwayat chat lengkap HANYA untuk nomor yang sedang aktif
        $activeChats = [];
        if ($activeClient) {
            $activeChats = WaChat::where('client_number', $activeClient)
                                 ->orderBy('chat_time', 'asc')
                                 ->get();
        }

        return view('chat-list', compact('clients', 'activeClient', 'activeChats'));
    }
}