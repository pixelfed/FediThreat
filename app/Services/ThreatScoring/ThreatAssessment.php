<?php

namespace App\Services\ThreatScoring;

class ThreatAssessment
{
    public readonly float $score;

    public readonly array $provider_results;

    public readonly array $instance_reports;

    public readonly string $risk_level;

    public readonly array $recommendations;

    public function __construct(array $data)
    {
        $this->score = $data['score'];
        $this->provider_results = $data['provider_results'];
        $this->instance_reports = $data['instance_reports'];
        $this->risk_level = $data['risk_level'];
        $this->recommendations = $data['recommendations'];
    }

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'risk_level' => $this->risk_level,
            'provider_results' => $this->provider_results,
            'instance_reports' => $this->instance_reports,
            'recommendations' => $this->recommendations,
        ];
    }
}
