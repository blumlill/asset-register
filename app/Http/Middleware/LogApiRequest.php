<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Logging\RequestResponseLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LogApiRequest
{
    private int $startTime = 0;

    public function __construct(private readonly RequestResponseLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->startTime = (int) (hrtime(true) / 1_000_000);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $durationMs = (int) (hrtime(true) / 1_000_000) - $this->startTime;
        $this->logger->log($request, $response, $durationMs);
    }
}
