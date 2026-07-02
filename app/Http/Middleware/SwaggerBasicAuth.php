<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SwaggerBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('swagger-auth.credentials');

        if (
            $request->getUser() !== $expected['user']
            || $request->getPassword() !== $expected['password']
        ) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Swagger Docs"',
            ]);
        }

        return $next($request);
    }
}
