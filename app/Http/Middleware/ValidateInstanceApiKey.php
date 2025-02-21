<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInstanceApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Instance-Key');
        $instance = Instance::where('api_key', $apiKey)->first();

        if (!$instance || !$instance->isActive()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->merge(['instance' => $instance]);
        return $next($request);
    }
}
