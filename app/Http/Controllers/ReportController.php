<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadSummary;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        // 1. Hitung total prospek yang sudah divalidasi
        $totalPasien = LeadSummary::where('status_review', 'disetujui')->count();

        // 2. Statistik Kategori Kanker (Untuk Grafik Pie)
        $kankerStats = LeadSummary::where('status_review', 'disetujui')
            ->select('kategori_kanker', DB::raw('count(*) as total'))
            ->groupBy('kategori_kanker')
            ->pluck('total', 'kategori_kanker');

        // 3. Statistik Minat Layanan (Untuk Grafik Batang)
        $layananStats = LeadSummary::where('status_review', 'disetujui')
            ->select('minat_layanan', DB::raw('count(*) as total'))
            ->groupBy('minat_layanan')
            ->pluck('total', 'minat_layanan');

        return view('management_report', compact('totalPasien', 'kankerStats', 'layananStats'));
    }
}