<?php

declare(strict_types=1);

return [
    'credentials' => [
        'user' => env('SWAGGER_USER', 'swagger'),
        'password' => env('SWAGGER_PASSWORD', 'secret'),
    ],
];
