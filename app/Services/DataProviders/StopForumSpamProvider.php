<?php

namespace App\Services\DataProviders;

use RuntimeException;

class StopForumSpamProvider extends BaseDataProvider
{
    protected string $name = 'spam';

    private string $apiUrl = 'https://api.stopforumspam.com/api';

    public function checkIp(string $ip): array
    {
        return $this->performCheck('ip', $ip);
    }

    public function checkEmail(string $email): array
    {
        return $this->performCheck('email', $email);
    }

    public function checkUrl(string $url): array
    {
        return [
            'result' => false,
            'confidence' => 0,
            'category' => 'not_applicable',
            'metadata' => [],
        ];
    }

    private function performCheck(string $type, string $value): array
    {
        return $this->getCached($type, $value, function () use ($type, $value) {
            $response = $this->http->get($this->apiUrl, [
                $type => $value,
                'json' => 1,
                'confidence' => 1,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('StopForumSpam API request failed');
            }

            $data = $response->json();
            $typeData = $data[$type] ?? [];

            $rawConfidence = $typeData['confidence'] ?? 0;
            $confidence = (int) (((float) $rawConfidence) * 100);

            return [
                'result' => (bool) ($typeData['appears'] ?? false),
                'confidence' => $confidence,
                'category' => 'reputation',
                'metadata' => [
                    'frequency' => (int) ($typeData['frequency'] ?? 0),
                    'last_seen' => $typeData['lastseen'] ?? null,
                ],
            ];
        });
    }

    protected function getCacheTtl(): int
    {
        return 1800;
    }
}
