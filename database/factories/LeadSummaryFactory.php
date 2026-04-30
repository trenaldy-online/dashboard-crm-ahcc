<?php

namespace Database\Factories;

use App\Models\LeadSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadSummaryFactory extends Factory
{
    protected $model = LeadSummary::class;

    public function definition(): array
    {
        // Daftar kategori kanker acak
        $kategori = ['Kanker Payudara', 'Kanker Paru', 'Kanker Serviks', 'Leukemia', 'Tumor Otak', 'Belum Terdeteksi'];
        
        // Daftar status acak
        $status = ['leads_baru', 'edukasi', 'konsultasi', 'deal', 'batal'];

        return [
            // Membuat nomor HP acak khas Indonesia (+628...)
            'client_number'      => '+628' . $this->faker->unique()->randomNumber(9, true),
            
            // Mengambil satu kanker acak
            'kategori_kanker'    => $this->faker->randomElement($kategori),
            
            // Membuat kalimat ringkasan medis acak
            'ringkasan'          => "Pasien menanyakan informasi terkait " . $this->faker->randomElement(['kemoterapi', 'radioterapi', 'biaya operasi', 'jadwal dokter', 'penggunaan BPJS']) . " untuk keluhannya.",
            
            // Menempatkan secara acak di papan Kanban
            'pipeline_status'    => $this->faker->randomElement($status),
            
            // Secara acak menentukan apakah ini hasil draft AI (0) atau sudah divalidasi (1)
            'is_human_validated' => $this->faker->boolean(40), // 40% kemungkinan sudah divalidasi
        ];
    }
}