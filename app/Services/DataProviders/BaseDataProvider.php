<?php

namespace App\Services\DataProviders;

abstract class BaseDataProvider implements DataProviderInterface
{
    protected string $name;

    protected array $config;

    protected \Illuminate\Cache\Repository $cache;

    protected \Illuminate\Http\Client\Factory $http;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->cache = app('cache.store');
        $this->http = app(\Illuminate\Http\Client\Factory::class);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    protected function cacheKey(string $type, string $value): string
    {
        return "threat_check:{$this->getName()}:{$type}:{$value}";
    }

    protected function getCached(string $type, string $value, \Closure $callback): array
    {
        $key = $this->cacheKey($type, $value);

        return $this->cache->remember($key, $this->getCacheTtl(), $callback);
    }

    protected function getCacheTtl(): int
    {
        return 3600; // 1 hour default
    }
}
