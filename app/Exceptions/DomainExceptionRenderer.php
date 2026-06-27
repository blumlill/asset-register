<?php declare(strict_types=1);

namespace App\Exceptions;

use App\Business\AssetRegistry\Domain\Exceptions\AssetAlreadyAssignedException;
use App\Business\AssetRegistry\Domain\Exceptions\AssetHasActiveAssignmentsException;
use App\Business\AssetRegistry\Domain\Exceptions\AssetNotFoundException;
use App\Business\AssetRegistry\Domain\Exceptions\ContractNotFoundException;
use App\Business\AssetRegistry\Domain\Exceptions\SerialNumberTakenException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class DomainExceptionRenderer
{
    public function register(Exceptions $exceptions): void
    {
        $exceptions->renderable(function (AssetNotFoundException $e) {
            return $this->notFound($e->errorCode, $e->getMessage());
        });

        $exceptions->renderable(function (ContractNotFoundException $e) {
            return $this->notFound($e->errorCode, $e->getMessage());
        });

        $exceptions->renderable(function (AssetAlreadyAssignedException $e) {
            return $this->conflict($e->errorCode, $e->getMessage());
        });

        $exceptions->renderable(function (SerialNumberTakenException $e) {
            return $this->conflict($e->errorCode, $e->getMessage());
        });

        $exceptions->renderable(function (AssetHasActiveAssignmentsException $e) {
            return $this->conflict($e->errorCode, $e->getMessage());
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
    }

    private function notFound(string $code, string $message): JsonResponse
    {
        return new JsonResponse(
            ['error' => ['code' => $code, 'message' => $message, 'details' => (object) []]],
            404,
        );
    }

    private function conflict(string $code, string $message): JsonResponse
    {
        return new JsonResponse(
            ['error' => ['code' => $code, 'message' => $message, 'details' => (object) []]],
            409,
        );
    }
}
