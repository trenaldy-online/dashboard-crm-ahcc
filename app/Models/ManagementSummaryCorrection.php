<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagementSummaryCorrection extends Model
{
    protected $fillable = [
        'management_lead_summary_id',
        'client_number',
        'period_key',
        'field_name',
        'old_value',
        'new_value',
        'correction_reason',
        'learning_keywords',
        'source',
        'created_by',
    ];
}
