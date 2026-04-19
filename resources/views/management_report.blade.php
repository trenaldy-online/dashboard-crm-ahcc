<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Manajemen AHCC</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; padding: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; }
        .scorecard { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 20px; border-radius: 12px; text-align: center; width: 300px; margin: 0 auto 30px auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .scorecard h2 { margin: 0; font-size: 36px; }
        .scorecard p { margin: 5px 0 0 0; font-size: 16px; opacity: 0.9; }
        
        .charts-container { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        .chart-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 45%; min-width: 400px; }
        .chart-box h3 { text-align: center; margin-top: 0; color: #475569; }
    </style>
</head>
<body>

    <div class="header">
        <h1>📊 Dashboard Laporan Manajemen AHCC</h1>
        <p>Analisis Tren Penyakit dan Minat Layanan Berdasarkan Percakapan WhatsApp (Validated by AI)</p>
    </div>

    <div class="scorecard">
        <h2>{{ $totalPasien }}</h2>
        <p>Total Pasien Baru (Tervalidasi)</p>
    </div>

    <div class="charts-container">
        <div class="chart-box">
            <h3>Tren Kategori Kanker</h3>
            <canvas id="kankerChart"></canvas>
        </div>

        <div class="chart-box">
            <h3>Minat Layanan Rumah Sakit</h3>
            <canvas id="layananChart"></canvas>
        </div>
    </div>

    <script>
        // Mengambil data dari Laravel dan mengubahnya ke format JavaScript
        const kankerLabels = {!! json_encode($kankerStats->keys()) !!};
        const kankerData = {!! json_encode($kankerStats->values()) !!};
        
        const layananLabels = {!! json_encode($layananStats->keys()) !!};
        const layananData = {!! json_encode($layananStats->values()) !!};

        // 1. Konfigurasi Grafik Pie (Kanker)
        new Chart(document.getElementById('kankerChart'), {
            type: 'doughnut',
            data: {
                labels: kankerLabels,
                datasets: [{
                    data: kankerData,
                    backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'],
                    borderWidth: 1
                }]
            }
        });

        // 2. Konfigurasi Grafik Batang (Layanan)
        new Chart(document.getElementById('layananChart'), {
            type: 'bar',
            data: {
                labels: layananLabels,
                datasets: [{
                    label: 'Jumlah Permintaan',
                    data: layananData,
                    backgroundColor: '#6366f1',
                    borderRadius: 6
                }]
            },
            options: {
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    </script>
</body>
</html>