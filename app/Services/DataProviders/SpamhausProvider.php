<?php

namespace App\Services\DataProviders;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

class SpamhausProvider extends BaseDataProvider
{
    protected string $name = 'spamhaus';

    private string $loginUrl = 'https://api.spamhaus.org/api/v1/login';

    private string $apiUrl = 'https://api.spamhaus.org/api/intel/v1';

    private string $username;

    private string $password;

    private string $realm = 'intel';

    public function __construct()
    {
        parent::__construct();

        $this->username = config('services.spamhaus.username');
        $this->password = config('services.spamhaus.password');

        if (empty($this->username) || empty($this->password)) {
            throw new RuntimeException('Spamhaus credentials not configured');
        }
    }

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
        return $this->performCheck('url', $url);
    }

    private function performCheck(string $type, string $value): array
    {
        return $this->getCached($type, $value, function () use ($type, $value) {
            $token = $this->getAuthToken();

            switch ($type) {
                case 'ip':
                    return $this->checkIpAddress($value, $token);
                case 'email':
                    return $this->checkEmailAddress($value, $token);
                case 'url':
                    return $this->checkUrlAddress($value, $token);
                default:
                    throw new RuntimeException("Unsupported check type: {$type}");
            }
        });
    }

    private function getAuthToken(): string
    {
        $cacheKey = 'spamhaus_auth_token';

        return Cache::remember($cacheKey, 3600, function () {
            $response = $this->http->post($this->loginUrl, [
                'username' => $this->username,
                'password' => $this->password,
                'realm' => $this->realm,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Spamhaus authentication failed');
            }

            $data = $response->json();

            if (empty($data['token'])) {
                throw new RuntimeException('No auth token received from Spamhaus');
            }

            return $data['token'];
        });
    }

    private function checkIpAddress(string $ip, string $token): array
    {
        // Check multiple Spamhaus blocklists for IP
        // $blocklists = ['SBL', 'XBL', 'CSS', 'PBL'];
        $blocklists = ['SBL', 'CSS'];
        $results = [];
        $isListed = false;
        $maxConfidence = 0;
        $metadata = [];

        foreach ($blocklists as $blocklist) {
            $url = "{$this->apiUrl}/byobject/cidr/{$blocklist}/listed/{$ip}";

            $response = $this->http->withToken($token)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (! empty($data) && isset($data[0]['listed']) && $data[0]['listed']) {
                    $isListed = true;
                    $confidence = $this->calculateConfidenceFromBlocklist($blocklist);
                    $maxConfidence = max($maxConfidence, $confidence);

                    $metadata[$blocklist] = [
                        'listed' => true,
                        'first_seen' => $data[0]['first_seen'] ?? null,
                        'last_seen' => $data[0]['last_seen'] ?? null,
                    ];
                }
            }
        }

        return [
            'result' => $isListed,
            'confidence' => $maxConfidence,
            'category' => $isListed ? 'malicious' : 'clean',
            'metadata' => $metadata,
        ];
    }

    private function checkEmailAddress(string $email, string $token): array
    {
        $domain = substr(strrchr($email, '@'), 1);

        if (empty($domain)) {
            return [
                'result' => false,
                'confidence' => 0,
                'category' => 'invalid',
                'metadata' => ['error' => 'Invalid email format'],
            ];
        }

        $url = "{$this->apiUrl}/byobject/domain/DBL/listed/{$domain}";

        $response = $this->http->withToken($token)->get($url);

        if (! $response->successful()) {
            return [
                'result' => false,
                'confidence' => 0,
                'category' => 'unknown',
                'metadata' => ['error' => 'API request failed'],
            ];
        }

        $data = $response->json();
        $isListed = ! empty($data) && isset($data[0]['listed']) && $data[0]['listed'];

        return [
            'result' => $isListed,
            'confidence' => $isListed ? 85 : 0,
            'category' => $isListed ? 'malicious' : 'clean',
            'metadata' => [
                'domain' => $domain,
                'checked_list' => 'DBL',
                'first_seen' => $data[0]['first_seen'] ?? null,
                'last_seen' => $data[0]['last_seen'] ?? null,
            ],
        ];
    }

    private function checkUrlAddress(string $url, string $token): array
    {
        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? null;

        if (empty($domain)) {
            return [
                'result' => false,
                'confidence' => 0,
                'category' => 'invalid',
                'metadata' => ['error' => 'Invalid URL format'],
            ];
        }

        $apiUrl = "{$this->apiUrl}/byobject/domain/DBL/listed/{$domain}";

        $response = $this->http->withToken($token)->get($apiUrl);

        if (! $response->successful()) {
            return [
                'result' => false,
                'confidence' => 0,
                'category' => 'unknown',
                'metadata' => ['error' => 'API request failed'],
            ];
        }

        $data = $response->json();
        $isListed = ! empty($data) && isset($data[0]['listed']) && $data[0]['listed'];

        return [
            'result' => $isListed,
            'confidence' => $isListed ? 80 : 0,
            'category' => $isListed ? 'malicious' : 'clean',
            'metadata' => [
                'domain' => $domain,
                'full_url' => $url,
                'checked_list' => 'DBL',
                'first_seen' => $data[0]['first_seen'] ?? null,
                'last_seen' => $data[0]['last_seen'] ?? null,
            ],
        ];
    }

    private function calculateConfidenceFromBlocklist(string $blocklist): int
    {
        return match ($blocklist) {
            'SBL' => 95, // Spamhaus Block List - highest confidence
            'XBL' => 90, // Exploits Block List - very high confidence
            'CSS' => 85, // CSS (Composite Blocking List) - high confidence
            'PBL' => 70, // Policy Block List - moderate confidence
            default => 50,
        };
    }

    protected function getCacheTtl(): int
    {
        return 3600;
    }
}
