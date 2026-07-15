<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Logging\RequestResponseLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LogApiRequest
{
    private const START_TIME_ATTRIBUTE = '_log_api_request_start_time';

    public function __construct(private readonly RequestResponseLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::START_TIME_ATTRIBUTE, (int) (hrtime(true) / 1_000_000));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $startTime = (int) $request->attributes->get(self::START_TIME_ATTRIBUTE, 0);
        $durationMs = (int) (hrtime(true) / 1_000_000) - $startTime;
        $this->logger->log($request, $response, $durationMs);
    }
}
