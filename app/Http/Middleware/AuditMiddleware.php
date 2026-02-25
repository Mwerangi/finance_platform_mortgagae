<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    protected AuditService $auditService;

    /**
     * Paths to exclude from audit logging.
     */
    protected array $excludedPaths = [
        'api/v1/audit-logs',
        'sanctum/csrf-cookie',
        'health',
        '_debugbar',
    ];

    /**
     * HTTP methods to exclude.
     */
    protected array $excludedMethods = [
        // Optionally exclude GET requests to reduce log volume
        // 'GET',
    ];

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if path is excluded
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Process the request
        $response = $next($request);

        // Log the request after response (in background to not slow down response)
        if ($this->shouldLog($request, $response)) {
            try {
                $this->auditService->logRequest($request, $response->getStatusCode());
            } catch (\Exception $e) {
                // Fail silently - don't break the request if audit logging fails
                \Log::error('Audit logging failed: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Determine if request should be skipped entirely.
     */
    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->excludedPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return true;
            }
        }

        if (in_array($request->method(), $this->excludedMethods)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if request should be logged.
     */
    protected function shouldLog(Request $request, Response $response): bool
    {
        // Always log write operations (POST, PUT, PATCH, DELETE)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        // Log failed requests (4xx, 5xx)
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        // Log authenticated GET requests to sensitive endpoints
        if ($request->method() === 'GET' && auth()->check()) {
            $sensitivePaths = [
                'kyc-documents',
                'bank-statements',
                'customers',
                'applications',
                'loans',
            ];

            foreach ($sensitivePaths as $path) {
                if (str_contains($request->path(), $path)) {
                    return true;
                }
            }
        }

        return false;
    }
}
