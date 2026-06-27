<?php declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwaggerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function testDocsEndpointRejectsWithoutCredentials(): void
    {
        $response = $this->get('/api/docs');

        $response->assertStatus(401);
    }

    public function testDocsEndpointRejectsWithWrongPassword(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('swagger:wrong-password'),
        ])->get('/api/docs');

        $response->assertStatus(401);
    }

    public function testDocsEndpointAcceptsCorrectCredentials(): void
    {
        config(['swagger-auth.credentials' => ['user' => 'swagger', 'password' => 'secret']]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('swagger:secret'),
        ])->get('/api/docs');

        $response->assertSuccessful();
    }
}
