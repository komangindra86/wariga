<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Services\Wariga\VersionManager;
use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    protected function success(mixed $data = null, string $message = 'Data berhasil diproses.', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function failure(string $message, array $errors = [], string $code = 'REQUEST_ERROR', int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'error_code' => $code,
        ], $status);
    }

    protected function metadata(string $verificationStatus = 'verified'): array
    {
        return app(VersionManager::class)->current() + [
            'verification_status' => $verificationStatus,
        ];
    }
}
