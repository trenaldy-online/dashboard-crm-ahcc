<?php

namespace App\Console\Commands;

use App\Jobs\ProcessManagementLeadSummaryJob;
use App\Models\ManagementLeadSummary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class HanaManagementSummary extends Command
{
    protected $signature = 'hana:management-summary
                            {--month= : Bulan laporan format YYYY-MM, contoh 2026-06}
                            {--start= : Tanggal mulai format YYYY-MM-DD}
                            {--end= : Tanggal akhir format YYYY-MM-DD}
                            {--client= : Proses satu nomor client tertentu, contoh "+62 812-xxxx"}
                            {--limit=0 : Batasi jumlah pasien, 0 berarti semua}
                            {--min-patient-messages=1 : Minimal jumlah pesan dari pasien agar diproses}
                            {--sync : Proses langsung tanpa queue}
                            {--force : Proses ulang walaupun data management summary sudah ada}';

    protected $description = 'Membuat management lead summary per pasien per periode untuk laporan management bulanan.';

    public function handle(): int
    {
        [$start, $end, $periodKey] = $this->resolvePeriod();

        $force = (bool) $this->option('force');
        $limit = max((int) $this->option('limit'), 0);
        $specificClient = trim((string) $this->option('client'));
        $minPatientMessages = max((int) $this->option('min-patient-messages'), 0);

        if ($specificClient !== '') {
            $clients = collect([$specificClient]);
        } else {
            $clients = DB::table('wa_chats')
                ->whereNotNull('client_number')
                ->where('client_number', '!=', '')
                ->where('is_me', false)
                ->whereBetween('chat_time', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->select(
                    'client_number',
                    DB::raw('COUNT(*) as patient_messages')
                )
                ->groupBy('client_number')
                ->havingRaw('COUNT(*) >= ?', [$minPatientMessages])
                ->orderByDesc('patient_messages')
                ->pluck('client_number')
                ->filter()
                ->values();
        }

        if (!$force) {
            $existingClients = ManagementLeadSummary::where('period_key', $periodKey)
                ->whereIn('client_number', $clients)
                ->pluck('client_number');

            $clients = $clients->diff($existingClients)->values();
        }

        if ($limit > 0) {
            $clients = $clients->take($limit)->values();
        }

        $this->info("Periode: {$periodKey} ({$start->toDateString()} s/d {$end->toDateString()})");
        $this->info("Mode: " . ($this->option('sync') ? 'sync' : 'queue batch'));
        $this->info("Force: " . ($force ? 'yes' : 'no'));
        $this->info("Minimal pesan pasien: {$minPatientMessages}");

        if ($specificClient !== '') {
            $this->info("Client spesifik: {$specificClient}");
        }

        $this->info("Total pasien diproses: {$clients->count()}");

        if ($clients->isEmpty()) {
            $this->warn('Tidak ada pasien yang perlu diproses.');
            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $bar = $this->output->createProgressBar($clients->count());
            $bar->start();

            foreach ($clients as $clientNumber) {
                Bus::dispatchSync(new ProcessManagementLeadSummaryJob(
                    clientNumber: $clientNumber,
                    periodKey: $periodKey,
                    periodStart: $start->toDateString(),
                    periodEnd: $end->toDateString(),
                    force: $force
                ));

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('Selesai memproses management summary secara sync.');

            return self::SUCCESS;
        }

        $jobs = $clients
            ->map(fn ($clientNumber) => new ProcessManagementLeadSummaryJob(
                clientNumber: $clientNumber,
                periodKey: $periodKey,
                periodStart: $start->toDateString(),
                periodEnd: $end->toDateString(),
                force: $force
            ))
            ->all();

        $batch = Bus::batch($jobs)
            ->name("Management Summary {$periodKey}")
            ->dispatch();

        $this->info('Batch management summary berhasil dibuat.');
        $this->line('Batch ID: ' . $batch->id);
        $this->line('Total jobs: ' . count($jobs));

        return self::SUCCESS;
    }

    private function resolvePeriod(): array
    {
        if ($this->option('month')) {
            $month = trim((string) $this->option('month'));

            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new \InvalidArgumentException('Format --month harus YYYY-MM, contoh 2026-06.');
            }

            $start = Carbon::createFromFormat('Y-m-d', $month . '-01', 'Asia/Jakarta')->startOfDay();
            $end = $start->copy()->endOfMonth()->endOfDay();

            return [$start, $end, $month];
        }

        if ($this->option('start') && $this->option('end')) {
            $start = Carbon::parse($this->option('start'), 'Asia/Jakarta')->startOfDay();
            $end = Carbon::parse($this->option('end'), 'Asia/Jakarta')->endOfDay();

            if ($end->lt($start)) {
                throw new \InvalidArgumentException('Tanggal --end tidak boleh lebih awal dari --start.');
            }

            $periodKey = $start->format('Ymd') . '-' . $end->format('Ymd');

            return [$start, $end, $periodKey];
        }

        $start = Carbon::now('Asia/Jakarta')->startOfMonth();
        $end = Carbon::now('Asia/Jakarta')->endOfDay();

        return [$start, $end, $start->format('Y-m')];
    }
}
