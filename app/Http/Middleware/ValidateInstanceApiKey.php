<?php

namespace App\Http\Middleware;

use App\Models\Instance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInstanceApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->extractApiKey($request);

        if (! $apiKey) {
            return $this->unauthorizedResponse('API key is required');
        }

        $instance = Instance::where('api_key', $apiKey)->first();

        if (! $instance) {
            return $this->unauthorizedResponse('Invalid API key');
        }

        if (! $instance->isActive()) {
            return $this->forbiddenResponse(
                'Instance is not active',
                ['status' => $instance->status]
            );
        }

        if (! $instance->isVerified() && $this->requiresVerification($request)) {
            return $this->forbiddenResponse(
                'Instance verification required',
                ['verified' => false]
            );
        }

        if ($this->isRateLimited($instance, $request)) {
            return $this->rateLimitResponse();
        }

        $request->merge(['instance' => $instance]);

        return $next($request);
    }

    /**
     * Extract API key from request headers
     */
    private function extractApiKey(Request $request): ?string
    {
        $apiKey = $request->header('Authorization');

        if ($apiKey && str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }

        return $apiKey;
    }

    /**
     * Check if the endpoint requires instance verification
     */
    private function requiresVerification(Request $request): bool
    {
        $verificationRequired = [
            'api/v1/check',
            'api/v1/report',
        ];

        $path = $request->path();

        foreach ($verificationRequired as $requiredPath) {
            if (str_contains($path, $requiredPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the instance is rate limited
     */
    private function isRateLimited(Instance $instance, Request $request): bool
    {
        $key = "rate_limit:instance:{$instance->id}";
        $requests = cache()->get($key, 0);

        $limit = 100;
        $window = 60;

        if ($requests >= $limit) {
            return true;
        }

        cache()->put($key, $requests + 1, $window);

        return false;
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401);
    }

    /**
     * Return forbidden response
     */
    private function forbiddenResponse(string $message, array $details = []): Response
    {
        return response()->json(array_merge([
            'error' => 'Forbidden',
            'message' => $message,
        ], $details), 403);
    }

    /**
     * Return rate limit response
     */
    private function rateLimitResponse(): Response
    {
        return response()->json([
            'error' => 'Rate Limit Exceeded',
            'message' => 'Too many requests. Please try again later.',
        ], 429);
    }
}
