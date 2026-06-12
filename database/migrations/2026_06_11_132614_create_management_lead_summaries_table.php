<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_lead_summaries', function (Blueprint $table) {
            $table->id();

            $table->string('client_number', 80);
            $table->string('period_key', 20);
            $table->date('period_start');
            $table->date('period_end');

            $table->string('source_channel', 50)->nullable();

            $table->text('representative_question')->nullable();
            $table->text('patient_intent')->nullable();
            $table->string('question_theme', 80)->nullable();
            $table->text('management_summary')->nullable();

            $table->string('kategori_kanker_norm', 120)->nullable();
            $table->string('minat_treatment_norm', 120)->nullable();
            $table->string('metode_bayar_norm', 120)->nullable();
            $table->string('profil_pengirim_norm', 120)->nullable();
            $table->string('status_medis_norm', 120)->nullable();
            $table->string('kendala_utama_norm', 120)->nullable();

            $table->string('lead_quality_segment', 50)->nullable();
            $table->unsignedTinyInteger('management_score')->default(0);
            $table->text('score_reason')->nullable();

            $table->text('recommended_action')->nullable();
            $table->text('content_angle')->nullable();
            $table->text('data_quality_note')->nullable();

            $table->unsignedInteger('patient_message_count')->default(0);
            $table->unsignedInteger('cs_message_count')->default(0);
            $table->timestamp('first_chat_at')->nullable();
            $table->timestamp('last_chat_at')->nullable();

            $table->longText('ai_raw_response')->nullable();

            $table->timestamps();

            $table->unique(['client_number', 'period_key'], 'mgmt_lead_client_period_unique');
            $table->index('period_key', 'mgmt_lead_period_idx');
            $table->index('source_channel', 'mgmt_lead_source_idx');
            $table->index('lead_quality_segment', 'mgmt_lead_quality_idx');
            $table->index('kategori_kanker_norm', 'mgmt_lead_kanker_idx');
            $table->index('minat_treatment_norm', 'mgmt_lead_treatment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_lead_summaries');
    }
};
