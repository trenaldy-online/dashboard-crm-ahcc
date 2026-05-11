# 👩‍⚕️ H.A.N.A - AI-Powered CRM & WhatsApp Telemetry
**Enterprise-Grade WhatsApp CRM for AHCC (Adi Husada Cancer Center)**

H.A.N.A (Head of Patient Advisor / AI CRM) adalah sistem *Customer Relationship Management* cerdas berbasis kecerdasan buatan (Google Gemini API) dan integrasi WhatsApp (Fonnte). Sistem ini dirancang secara khusus untuk memonitor, menganalisis, dan menjadwalkan tindakan *follow-up* pasien kanker secara otomatis, guna mengurangi *Junk Leads* dan meningkatkan efisiensi operasional tim Patient Advisor.

---

## ✨ Fitur Utama
- **AI Conversation Classification:** Pemilahan chat otomatis (*Heuristic + Regex + AI*) untuk memisahkan *Engaged Leads*, *Junk Leads*, dan percakapan yang sudah selesai (*Resolved*).
- **Smart Follow-up Radar:** Mendeteksi pasien yang melakukan *ghosting* dan menjadwalkan *follow-up* bertingkat (H+2, H+5) atau pembatalan otomatis (H+14).
- **Daily AI Morning Briefing:** Merangkum interaksi harian dan mengirimkan instruksi tugas via *broadcast* WhatsApp kepada tim setiap pagi.
- **Business Intelligence Dashboard:** Visualisasi data analitik untuk memonitor kualitas *lead*, sumber lalu lintas, dan *pain points* (kendala) pasien secara *real-time*.

---

## 🗺️ Struktur Halaman & Fungsinya

Sistem ini memiliki beberapa antarmuka utama yang dirancang dengan estetika modern (*Dark Mode / Cyberpunk*):

### 1. Dashboard Telemetry & Morning Briefing (`/dashboard`)
Pusat komando harian untuk tim Patient Advisor (PA).
* **Surat Instruksi H.A.N.A:** *Modal popup* berisi ringkasan harian dan motivasi dari AI.
* **Daftar Eksekusi Follow-Up:** Menampilkan pasien mana saja yang wajib disapa hari ini lengkap dengan saran *copywriting* dari AI.
* **Live Feed & Traffic Chart:** Memantau grafik pesan masuk dan ringkasan percakapan terakhir yang ditarik dari ekstensi WhatsApp.

### 2. Kanban Pipeline (`/pipeline`)
Area kerja operasional (*Lead Management*).
* **Kolom Status:** Menggeser pasien antar tahap (Leads Baru ➔ Edukasi ➔ Konsultasi ➔ Deal/Batal).
* **Tombol "Selesai Follow Up":** Fitur krusial untuk mengeksekusi tugas. Tombol ini memperbarui waktu `last_cs_reply_at` dan `last_follow_up_sent_at` secara akurat untuk mereset radar peringatan H.A.N.A.

### 3. Business Intelligence (B.I) Analitik (`/laporan`)
Dasbor khusus untuk Manajemen dan Supervisor (SPV).
* **Kualitas Lead (Pie/Doughnut Chart):** Melihat rasio pasien potensial (*Engaged*) berbanding pasien *Junk* atau nyasar (*Redirected*).
* **Top Kendala Utama (Horizontal Bar Chart):** Memetakan alasan keraguan pasien (Biaya, Jarak, Takut Efek Samping, dll) untuk keperluan strategi *Marketing*.

---

## 💻 Panduan Instalasi & Setup

Ikuti langkah-langkah di terminal Anda untuk menjalankan proyek ini di *server* lokal atau *production*:

### 1. Kloning Repositori & Instalasi Dependensi
```bash
git clone [https://github.com/username-anda/nama-repo-hana.git](https://github.com/username-anda/nama-repo-hana.git)
cd nama-repo-hana
composer install
npm install
npm run build

Konfigurasi Environment (.env)
Salin file konfigurasi bawaan dan sesuaikan nilainya:

Bash
cp .env.example .env
php artisan key:generate
Wajib tambahkan API Keys berikut di dalam file .env Anda:

Cuplikan kode
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_anda
DB_USERNAME=root
DB_PASSWORD=

# Gemini API (AI Otak H.A.N.A)
GEMINI_API_KEY="isi_dengan_api_key_google_studio_anda"

# Fonnte API (WhatsApp Broadcast)
FONNTE_TOKEN="isi_dengan_token_fonnte_anda"
FONNTE_TARGET_WA="081234567890" # Nomor grup atau SPV tujuan

Perintah Terminal Operasional (Wajib Tahu)
Agar H.A.N.A dapat bekerja dengan maksimal (terutama untuk pemrosesan AI massal dan scan pagi), Anda perlu menjalankan perintah berikut:

1. Menjalankan Antrean Proses AI (Job Worker)
Sistem menggunakan Bus::batch untuk memanggil API Gemini. Anda wajib menjalankan antrean (queue) di latar belakang agar fitur "Rekap Chat Hari Ini" bisa memproses ringkasan tanpa membuat browser loading lama:

Bash
php artisan queue:work
(Catatan: Di server production, gunakan Supervisor agar perintah ini berjalan terus-menerus 24/7).

2. Membangunkan H.A.N.A (Morning Scan)
Perintah ini bertugas menyeleksi database, mencari pasien ghosting, menghitung Actionable Metrics, memanggil AI pembuat briefing, dan mengirim WhatsApp ke tim PA.

Bash
php artisan hana:morning-scan
(Catatan: Daftarkan perintah ini ke dalam Cron Job atau Laravel Scheduler (routes/console.php) agar tereksekusi otomatis setiap jam 07:00 pagi).

3. Sinkronisasi Data Historis (Tinker)
Hanya dilakukan SATU KALI SAJA jika Anda memiliki data obrolan lama yang ingin diikutsertakan ke dalam sistem filter kelas H.A.N.A (Junk vs Engaged):

Bash
php artisan tinker
Lalu paste script One-Liner yang mengklasifikasikan conversation_outcome ke terminal Tinker.

Developed with ❤️ for Better Patient Care at AHCC.