<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagementReportQuestion extends Model
{
    protected $fillable = [
        'period_key',
        'period_start',
        'period_end',
        'question',
        'answer',
        'cache_name',
        'model',
        'provider',
        'prompt_tokens',
        'cached_tokens',
        'total_tokens',
        'raw_response',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'prompt_tokens' => 'integer',
        'cached_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];
}
