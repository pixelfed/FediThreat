<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreatScoreHistory extends Model
{
    protected $fillable = [
        'threat_score_id',
        'score',
        'risk_level',
        'provider_results',
        'total_reports',
        'unique_instances',
        'trigger_type',
        'change_details',
    ];

    protected $casts = [
        'provider_results' => 'array',
        'change_details' => 'array',
        'score' => 'float',
    ];

    public function threatScore(): BelongsTo
    {
        return $this->belongsTo(ThreatScore::class);
    }
}
