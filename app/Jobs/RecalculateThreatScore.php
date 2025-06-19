<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\ThreatScore;
use App\Services\ThreatScoring\ThreatScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecalculateThreatScore implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 60, 120];

    /**
     * Delete the job if its models no longer exist.
     */
    public $deleteWhenMissingModels = true;

    /**
     * The report that triggered the recalculation.
     */
    private Report $report;

    /**
     * Create a new job instance.
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "{$this->report->target_type}:{$this->report->target_value}";
    }

    /**
     * The unique key for the job lock.
     */
    public function uniqueFor(): int
    {
        return 300;
    }

    /**
     * Execute the job.
     */
    public function handle(ThreatScoringService $scoringService): void
    {
        try {
            $assessment = $scoringService->calculate(
                $this->report->target_type,
                $this->report->target_value
            );

            $threatScore = ThreatScore::firstOrNew([
                'target_type' => $this->report->target_type,
                'target_value' => $this->report->target_value,
            ]);

            if (! $threatScore->exists) {
                $threatScore->first_seen_at = now();
            }

            $oldScore = $threatScore->exists ? $threatScore->score : null;
            $oldRiskLevel = $threatScore->exists ? $threatScore->risk_level : null;

            $threatScore->updateScore($assessment->score, [
                'risk_level' => $assessment->risk_level,
                'provider_results' => $assessment->provider_results,
                'instance_reports' => $assessment->instance_reports,
                'recommendations' => $assessment->recommendations,
                'total_reports' => $assessment->instance_reports['total_reports'],
                'unique_instances' => $assessment->instance_reports['unique_instances'],
                'severity_breakdown' => $assessment->instance_reports['severity_breakdown'] ?? [],
                'reason_breakdown' => $assessment->instance_reports['reason_breakdown'] ?? [],
            ]);

            if ($this->isSignificantChange($oldScore, $assessment->score)) {
                $this->notifySignificantChange($threatScore, $oldScore, $oldRiskLevel);
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Threat score recalculation job failed', [
            'report_id' => $this->report->id,
            'target_type' => $this->report->target_type,
            'target_value' => $this->report->target_value,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Determine if the score change is significant enough to warrant attention
     */
    private function isSignificantChange(?float $oldScore, float $newScore): bool
    {
        if ($oldScore === null) {
            return $newScore >= 70;
        }

        // Consider change significant if:
        // 1. Score changed by 20 or more points
        // 2. Score crossed 50 or 80 threshold in either direction
        return abs($newScore - $oldScore) >= 20
            || ($oldScore < 50 && $newScore >= 50)
            || ($oldScore < 80 && $newScore >= 80)
            || ($oldScore >= 50 && $newScore < 50)
            || ($oldScore >= 80 && $newScore < 80);
    }

    /**
     * Handle notifications for significant changes
     */
    private function notifySignificantChange(
        ThreatScore $threatScore,
        ?float $oldScore,
        ?string $oldRiskLevel
    ): void {
        // If this is a new high-risk target
        if ($oldScore === null && $threatScore->risk_level === 'high') {
            // todo
            // NotifyAdminsOfNewThreat::dispatch($threatScore);
        }

        // If risk level increased to high
        if ($oldRiskLevel !== 'high' && $threatScore->risk_level === 'high') {
            // todo
            // NotifyAdminsOfRiskEscalation::dispatch($threatScore, $oldRiskLevel);
        }
    }
}
