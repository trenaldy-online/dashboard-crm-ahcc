<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagementClassificationRule extends Model
{
    protected $fillable = [
        'field_name',
        'match_keywords',
        'target_value',
        'reasoning',
        'example_summary_id',
        'example_client_number',
        'source',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
