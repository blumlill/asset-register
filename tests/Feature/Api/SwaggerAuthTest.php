<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwaggerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_docs_endpoint_rejects_without_credentials(): void
    {
        $response = $this->get('/api/docs');

        $response->assertStatus(401);
    }

    public function test_docs_endpoint_rejects_with_wrong_password(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('swagger:wrong-password'),
        ])->get('/api/docs');

        $response->assertStatus(401);
    }

    public function test_docs_endpoint_accepts_correct_credentials(): void
    {
        config(['swagger-auth.credentials' => ['user' => 'swagger', 'password' => 'secret']]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('swagger:secret'),
        ])->get('/api/docs');

        $response->assertSuccessful();
    }
}
