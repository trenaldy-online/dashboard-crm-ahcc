<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeadSummary; // Wajib dipanggil

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Menyuntikkan 15 data pasien secara acak ke dalam database
        // LeadSummary::factory(15)->create();
    }
}