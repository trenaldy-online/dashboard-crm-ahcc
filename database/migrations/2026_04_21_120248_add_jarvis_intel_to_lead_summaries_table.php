<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->integer('lead_score')->default(0)->after('pipeline_status');
        $table->string('minat_treatment')->nullable()->after('lead_score');
        $table->string('metode_bayar')->nullable()->after('minat_treatment');
        $table->string('profil_pengirim')->nullable()->after('metode_bayar');
        $table->string('status_medis')->nullable()->after('profil_pengirim');
        $table->string('sentimen_emosi')->nullable()->after('status_medis');
        $table->string('kendala_utama')->nullable()->after('sentimen_emosi');
        $table->string('gclid')->nullable()->after('kendala_utama');
        $table->string('fbclid')->nullable()->after('gclid');
    });
}

public function down(): void
{
    Schema::table('lead_summaries', function (Blueprint $table) {
        $table->dropColumn([
            'lead_score', 'minat_treatment', 'metode_bayar', 'profil_pengirim', 
            'status_medis', 'sentimen_emosi', 'kendala_utama', 'gclid', 'fbclid'
        ]);
    });
}
};
