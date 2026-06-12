<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagementLeadSummary extends Model
{
    protected $fillable = [
        'client_number',
        'period_key',
        'period_start',
        'period_end',
        'source_channel',
        'representative_question',
        'patient_intent',
        'question_theme',
        'management_summary',
        'kategori_kanker_norm',
        'minat_treatment_norm',
        'metode_bayar_norm',
        'profil_pengirim_norm',
        'status_medis_norm',
        'kendala_utama_norm',
        'lead_quality_segment',
        'management_score',
        'score_reason',
        'recommended_action',
        'content_angle',
        'data_quality_note',
        'patient_message_count',
        'cs_message_count',
        'first_chat_at',
        'last_chat_at',
        'ai_raw_response',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'first_chat_at' => 'datetime',
        'last_chat_at' => 'datetime',
        'management_score' => 'integer',
        'patient_message_count' => 'integer',
        'cs_message_count' => 'integer',
    ];
}
