<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaChat;

class DashboardController extends Controller
{
    public function index()
    {
        /// Urutkan berdasarkan chat_time dari terlama ke terbaru (asc)
        $groupedChats = WaChat::orderBy('chat_time', 'asc')->get()->groupBy('client_number');

        return view('dashboard', compact('groupedChats'));
    }
}