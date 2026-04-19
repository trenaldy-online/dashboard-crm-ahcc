<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadSummary extends Model
{
    protected $fillable = [
        'client_number', 
        'kategori_kanker', 
        'minat_layanan', 
        'ringkasan', 
        'status_review'
    ];
}