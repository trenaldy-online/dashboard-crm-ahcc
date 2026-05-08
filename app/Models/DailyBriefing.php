<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyBriefing extends Model
{
    protected $fillable = ['tanggal_briefing', 'narasi_teks', 'xray_log', 'data_mentah', 'is_wa_sent'];
    protected $casts = ['data_mentah' => 'array'];
}
