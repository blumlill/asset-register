<?php

declare(strict_types=1);

use App\Business\AssetRegistry\Domain\Exceptions\DomainErrorType;
use App\Business\AssetRegistry\Domain\Exceptions\DomainException;
use App\Http\Middleware\LogApiRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', LogApiRequest::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->renderable(function (DomainException $e) {
            $status = match ($e->errorType) {
                DomainErrorType::NOT_FOUND => 404,
                DomainErrorType::CONFLICT => 409,
            };

            return new JsonResponse(
                ['error' => ['code' => $e->errorCode, 'message' => $e->getMessage(), 'details' => (object) []]],
                $status,
            );
        });

        $exceptions->renderable(function (ValidationException $e) {
            return new JsonResponse([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'details' => $e->errors(),
                ],
            ], 422);
        });
    })->create();
