<?php

namespace App\Services\DataProviders;

interface DataProviderInterface
{
    /**
     * Check an IP address against the provider
     *
     * @return array{
     *   result: bool,
     *   confidence: int,
     *   category: string,
     *   metadata?: array<string, mixed>
     * }
     */
    public function checkIp(string $ip): array;

    /**
     * Check an email address against the provider
     */
    public function checkEmail(string $email): array;

    /**
     * Check a URL against the provider
     */
    public function checkUrl(string $url): array;

    /**
     * Get the provider's name
     */
    public function getName(): string;

    /**
     * Check if the provider is available/healthy
     */
    public function isAvailable(): bool;
}
