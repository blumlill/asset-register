<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Core\Logging\ApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_is_logged_with_a_sane_duration(): void
    {
        $this->getJson('/api/v1/assets')->assertOk();

        $log = ApiLog::latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('GET', $log->method);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
        $this->assertLessThan(5000, $log->duration_ms);
    }
}
