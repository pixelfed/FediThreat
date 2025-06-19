<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThreatScore extends Model
{
    protected $fillable = [
        'target_type',
        'target_value',
        'score',
        'risk_level',
        'provider_results',
        'instance_reports',
        'recommendations',
        'total_reports',
        'unique_instances',
        'severity_breakdown',
        'reason_breakdown',
        'first_seen_at',
        'last_reported_at',
    ];

    protected $casts = [
        'provider_results' => 'array',
        'instance_reports' => 'array',
        'recommendations' => 'array',
        'severity_breakdown' => 'array',
        'reason_breakdown' => 'array',
        'score' => 'float',
        'first_seen_at' => 'datetime',
        'last_reported_at' => 'datetime',
    ];

    public function history(): HasMany
    {
        return $this->hasMany(ThreatScoreHistory::class);
    }

    public function recordHistory(string $triggerType, ?array $changeDetails = null): void
    {
        $this->history()->create([
            'score' => $this->score ?? 0.0, // Ensure non-null score
            'risk_level' => $this->risk_level ?? 'low',
            'provider_results' => $this->provider_results ?? [],
            'total_reports' => $this->total_reports ?? 0,
            'unique_instances' => $this->unique_instances ?? 0,
            'trigger_type' => $triggerType,
            'change_details' => $changeDetails,
        ]);
    }

    public function updateScore(float $newScore, array $data): void
    {
        $oldScore = $this->score ?? 0.0; // Ensure we have a numeric value
        $changes = [
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'change' => $newScore - $oldScore,
        ];

        // Update the current record
        $this->update(array_merge($data, [
            'score' => $newScore,
            'last_reported_at' => now(),
        ]));

        // Refresh the model to get updated values
        $this->refresh();

        // Record history with current values
        $this->recordHistory('score_update', $changes);
    }

    public function getScoreHistory(int $days = 30): array
    {
        return $this->history()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get()
            ->map(fn ($record) => [
                'date' => $record->created_at->toDateString(),
                'score' => $record->score,
                'trigger' => $record->trigger_type,
            ])
            ->toArray();
    }
}
