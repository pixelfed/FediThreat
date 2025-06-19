<?php

namespace App\Services\ThreatScoring;

use App\Models\Report;
use App\Models\ThreatScore;
use App\Services\DataProviders\DataProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ThreatScoringService
{
    private const SCORE_THRESHOLD_HIGH = 80;

    private const SCORE_THRESHOLD_MEDIUM = 50;

    private const CACHE_TTL = 3600;

    /**
     * @var Collection<DataProviderInterface>
     */
    private Collection $providers;

    /**
     * Provider weights for scoring calculation
     */
    private array $providerWeights = [
        'spam' => 0.80,
        'instance_reports' => 0.25,
    ];

    /**
     * Severity weights for instance reports
     */
    private array $severityWeights = [
        1 => 0.2,  // Low
        2 => 0.4,  // Medium-Low
        3 => 0.6,  // Medium
        4 => 0.8,  // Medium-High
        5 => 1.0,   // High
    ];

    public function __construct(array $providers)
    {
        $this->providers = collect($providers);
    }

    /**
     * Calculate threat score for a given target
     */
    public function calculate(string $type, string $value): ThreatAssessment
    {
        $cacheKey = "threat_score:{$type}:{$value}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type, $value) {
            $providerResults = $this->collectProviderResults($type, $value);

            $instanceReports = $this->getInstanceReports($type, $value);

            $score = $this->calculateFinalScore($providerResults, $instanceReports);

            return new ThreatAssessment([
                'score' => $score,
                'provider_results' => $providerResults,
                'instance_reports' => $instanceReports,
                'risk_level' => $this->determineRiskLevel($score),
                'recommendations' => $this->generateRecommendations($score, $providerResults, $instanceReports),
            ]);
        });
    }

    /**
     * Collect results from all providers
     */
    private function collectProviderResults(string $type, string $value): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            try {
                $results[$provider->getName()] = match ($type) {
                    'ip' => $provider->checkIp($value),
                    'email' => $provider->checkEmail($value),
                    'url' => $provider->checkUrl($value),
                    default => throw new \InvalidArgumentException("Unsupported type: {$type}")
                };
            } catch (\Exception $e) {
                $results[$provider->getName()] = [
                    'result' => false,
                    'confidence' => 0,
                    'category' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get and process instance reports
     */
    private function getInstanceReports(string $type, string $value): array
    {
        $reports = Report::where('target_type', $type)
            ->where('target_value', $value)
            ->where('status', 'confirmed')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        return [
            'total_reports' => $reports->count(),
            'unique_instances' => $reports->unique('instance_id')->count(),
            'severity_breakdown' => $reports->groupBy('severity')->map->count(),
            'reason_breakdown' => $reports->groupBy('reason')->map->count(),
            'recent_reports' => $reports->take(5)->map(function ($report) {
                return [
                    'reason' => $report->reason,
                    'severity' => $report->severity,
                    'created_at' => $report->created_at->toIso8601String(),
                ];
            }),
        ];
    }

    /**
     * Calculate final threat score
     */
    private function calculateFinalScore(array $providerResults, array $instanceReports): float
    {
        $scores = [];

        foreach ($providerResults as $provider => $result) {
            if (isset($this->providerWeights[$provider]) && $result['result']) {
                $scores[] = $result['confidence'] * $this->providerWeights[$provider];
            }
        }

        if ($instanceReports['total_reports'] > 0) {
            $reportScore = $this->calculateInstanceReportScore($instanceReports);
            $scores[] = $reportScore * $this->providerWeights['instance_reports'];
        }

        return min(100, array_sum($scores));
    }

    /**
     * Calculate score based on instance reports
     */
    private function calculateInstanceReportScore(array $instanceReports): float
    {
        $severityScore = 0;
        $totalReports = $instanceReports['total_reports'];

        foreach ($instanceReports['severity_breakdown'] as $severity => $count) {
            $severityScore += ($count / $totalReports) * $this->severityWeights[$severity] * 100;
        }

        $instanceMultiplier = min(1.5, sqrt($instanceReports['unique_instances'] / 3));

        return min(100, $severityScore * $instanceMultiplier);
    }

    /**
     * Determine risk level based on score
     */
    private function determineRiskLevel(float $score): string
    {
        return match (true) {
            $score >= self::SCORE_THRESHOLD_HIGH => 'high',
            $score >= self::SCORE_THRESHOLD_MEDIUM => 'medium',
            default => 'low'
        };
    }

    /**
     * Generate recommendations based on assessment
     */
    private function generateRecommendations(
        float $score,
        array $providerResults,
        array $instanceReports
    ): array {
        $recommendations = [];

        if ($score >= self::SCORE_THRESHOLD_HIGH) {
            $recommendations[] = 'Block registration and require manual review';
        } elseif ($score >= self::SCORE_THRESHOLD_MEDIUM) {
            $recommendations[] = 'Enable additional verification steps';
        }

        foreach ($providerResults as $provider => $result) {
            if ($result['result']) {
                match ($provider) {
                    'spam' => $recommendations[] = 'Review recent activity for spam patterns',
                    default => null
                };
            }
        }

        if ($instanceReports['total_reports'] > 0) {
            $recommendations[] = sprintf(
                'Review previous violations reported by %d instances',
                $instanceReports['unique_instances']
            );
        }

        return array_unique($recommendations);
    }

    public function updateThreatScore(string $type, string $value, array $assessment): void
    {
        $threatScore = ThreatScore::firstOrNew([
            'target_type' => $type,
            'target_value' => $value,
        ]);

        if (! $threatScore->exists) {
            $threatScore->first_seen_at = now();
        }

        $threatScore->updateScore($assessment['score'], [
            'risk_level' => $assessment['risk_level'],
            'provider_results' => $assessment['provider_results'],
            'instance_reports' => $assessment['instance_reports'],
            'recommendations' => $assessment['recommendations'],
            'total_reports' => $assessment['instance_reports']['total_reports'],
            'unique_instances' => $assessment['instance_reports']['unique_instances'],
            'severity_breakdown' => $assessment['instance_reports']['severity_breakdown'],
            'reason_breakdown' => $assessment['instance_reports']['reason_breakdown'],
        ]);
    }
}
