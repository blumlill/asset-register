<?php

declare(strict_types=1);

namespace App\Core\Logging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

final class RequestResponseLogger
{
    private const MASKED_FIELDS = ['authorization', 'password', 'token', 'secret'];

    public function log(
        Request $request,
        SymfonyResponse $response,
        int $durationMs,
    ): void {
        try {
            ApiLog::create([
                'method' => $request->method(),
                'path' => $request->path(),
                'request_headers' => $this->maskHeaders($request->headers->all()),
                'request_body' => $this->maskBody($request->all()),
                'response_status' => $response->getStatusCode(),
                'response_body' => $this->decodeBody($response->getContent()),
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            Log::error('API log write failed: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, string[]>  $headers
     * @return array<string, string[]>
     */
    private function maskHeaders(array $headers): array
    {
        $masked = [];
        foreach ($headers as $key => $values) {
            $masked[$key] = $this->isSensitive($key) ? ['***'] : $values;
        }

        return $masked;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function maskBody(array $body): array
    {
        $masked = [];
        foreach ($body as $key => $value) {
            $masked[$key] = $this->isSensitive((string) $key) ? '***' : $value;
        }

        return $masked;
    }

    private function isSensitive(string $key): bool
    {
        return in_array(strtolower($key), self::MASKED_FIELDS, strict: true);
    }

    /** @return array<string, mixed>|null */
    private function decodeBody(string|false $content): ?array
    {
        if ($content === false || $content === '') {
            return null;
        }

        $decoded = json_decode($content, associative: true);

        return is_array($decoded) ? $decoded : null;
    }
}
