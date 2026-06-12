<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagementReportCache extends Model
{
    protected $fillable = [
        'period_key',
        'period_start',
        'period_end',
        'model',
        'cache_name',
        'ttl_seconds',
        'expires_at',
        'status',
        'cached_token_count',
        'source_payload_hash',
        'source_payload',
        'last_error',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'expires_at' => 'datetime',
        'ttl_seconds' => 'integer',
        'cached_token_count' => 'integer',
    ];
}
