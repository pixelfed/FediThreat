<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instance;
use App\Models\ThreatScore;
use App\Services\ThreatScoring\ThreatScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ThreatCheckController extends Controller
{
    private ThreatScoringService $threatScoringService;

    public function __construct(ThreatScoringService $threatScoringService)
    {
        $this->threatScoringService = $threatScoringService;
    }

    /**
     * Check threat score for a given target
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:ip,email',
            'value' => 'required|string|max:512',
            'force_refresh' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $instance = $request->instance;
        $type = $request->input('type');
        $value = $request->input('value');
        // $forceRefresh = $request->boolean('force_refresh', false);
        $forceRefresh = false;

        try {
            $instance->updateLastSeen();

            if ($forceRefresh) {
                $this->clearThreatCache($type, $value);
            }

            $assessment = $this->threatScoringService->calculate($type, $value);

            $this->updateThreatScore($type, $value, $assessment);

            $response = [
                'type' => $type,
                //'score' => $assessment->score,
                //'risk_level' => $assessment->risk_level,
                //'recommendations' => $assessment->recommendations,
                'provider_results' => $this->filterProviderResults($assessment->provider_results),
                //'instance_reports' => $this->formatInstanceReports($assessment->instance_reports),
                'checked_at' => now()->toIso8601String(),
            ];

            // if ($request->boolean('include_details', false)) {
            //     $response['details'] = [
            //         'provider_breakdown' => $assessment->provider_results,
            //         'calculation_method' => 'weighted_average',
            //         'cache_status' => $forceRefresh ? 'refreshed' : 'cached'
            //     ];
            // }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to process threat check',
                'message' => 'An internal error occurred while processing your request',
            ], 500);
        }
    }

    /**
     * Clear threat cache for a target
     */
    private function clearThreatCache(string $type, string $value): void
    {
        $cacheKey = "threat_score:{$type}:{$value}";
        Cache::forget($cacheKey);

        // todo - make providers easier to query
        $providers = ['spam'];
        foreach ($providers as $provider) {
            Cache::forget("threat_check:{$provider}:{$type}:{$value}");
        }
    }

    /**
     * Update threat score in database
     */
    private function updateThreatScore(string $type, string $value, $assessment): void
    {
        $threatScore = ThreatScore::firstOrNew([
            'target_type' => $type,
            'target_value' => $value,
        ]);

        $isNew = ! $threatScore->exists;

        if ($isNew) {
            $threatScore->first_seen_at = now();
            $threatScore->score = 0.0;
            $threatScore->risk_level = 'low';
            $threatScore->provider_results = [];
            $threatScore->instance_reports = [];
            $threatScore->recommendations = [];
            $threatScore->total_reports = 0;
            $threatScore->unique_instances = 0;
            $threatScore->severity_breakdown = [];
            $threatScore->reason_breakdown = [];
            $threatScore->last_reported_at = now();

            $threatScore->save();
        }

        $threatScore->updateScore($assessment->score, [
            'risk_level' => $assessment->risk_level,
            'provider_results' => $assessment->provider_results,
            'instance_reports' => $assessment->instance_reports,
            'recommendations' => $assessment->recommendations,
            'total_reports' => $assessment->instance_reports['total_reports'] ?? 0,
            'unique_instances' => $assessment->instance_reports['unique_instances'] ?? 0,
            'severity_breakdown' => $assessment->instance_reports['severity_breakdown'] ?? [],
            'reason_breakdown' => $assessment->instance_reports['reason_breakdown'] ?? [],
        ]);
    }

    /**
     * Filter provider results for response
     */
    private function filterProviderResults(array $providerResults): array
    {
        $filtered = [];
        foreach ($providerResults as $provider => $result) {
            $filtered[$provider] = [
                'detected' => $result['result'] ?? false,
                'confidence' => $result['confidence'] ?? 0,
                'category' => $result['category'] ?? 'unknown',
            ];
        }

        return $filtered;
    }

    /**
     * Format instance reports for response
     */
    private function formatInstanceReports(array $instanceReports): array
    {
        return [
            'total_reports' => $instanceReports['total_reports'] ?? 0,
            'unique_instances' => $instanceReports['unique_instances'] ?? 0,
            'most_common_reason' => $this->getMostCommonReason($instanceReports['reason_breakdown'] ?? []),
            'average_severity' => $this->getAverageSeverity($instanceReports['severity_breakdown']->toArray() ?? []),
        ];
    }

    /**
     * Get most common report reason
     */
    private function getMostCommonReason(\Illuminate\Database\Eloquent\Collection|array $reasonBreakdown): ?string
    {
        if (empty($reasonBreakdown)) {
            return null;
        }

        return array_key_first(
            array_slice(
                array_flip(
                    array_flip($reasonBreakdown->toArray())
                ),
                0,
                1,
                true
            )
        );
    }

    /**
     * Calculate average severity
     */
    private function getAverageSeverity(array $severityBreakdown): ?float
    {
        if (empty($severityBreakdown)) {
            return null;
        }

        $total = array_sum($severityBreakdown);
        $weightedSum = array_sum(array_map(
            fn ($severity, $count) => $severity * $count,
            array_keys($severityBreakdown),
            $severityBreakdown
        ));

        return round($weightedSum / $total, 2);
    }

    /**
     * Get recent checks count for instance
     */
    private function getRecentChecks(Instance $instance, int $days): int
    {
        return 0;
    }

    /**
     * Get high risk detections for instance
     */
    private function getHighRiskDetections(Instance $instance, int $days): int
    {
        return ThreatScore::where('risk_level', 'high')
            ->where('updated_at', '>=', now()->subDays($days))
            ->count();
    }
}
