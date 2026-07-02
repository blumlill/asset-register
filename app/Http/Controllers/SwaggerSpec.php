<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Asset Register API',
    description: 'Laravel 13 / PHP 8.5 hexagonal architecture REST API for managing assets and contracts.',
    contact: new OA\Contact(name: 'Asset Register Team'),
)]
#[OA\Server(url: '/')]
#[OA\SecurityScheme(
    securityScheme: 'basicAuth',
    type: 'http',
    scheme: 'basic',
)]
class SwaggerSpec {}
