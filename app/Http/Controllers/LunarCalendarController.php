<?php

namespace App\Http\Controllers;

use App\Contracts\LunarCalendarProviderInterface;
use App\Services\Wariga\VersionManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Throwable;

final class LunarCalendarController extends Controller
{
    public function __invoke(
        string $date,
        LunarCalendarProviderInterface $provider,
    ): JsonResponse {
        try {
            $calendarDate = Carbon::createFromFormat('!Y-m-d', $date, config('wariga.timezone'));
            $errors = Carbon::getLastErrors();

            if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                throw new \InvalidArgumentException;
            }
        } catch (Throwable) {
            return response()->json([
                'message' => 'Tanggal Masehi harus menggunakan format YYYY-MM-DD.',
            ], 422);
        }

        return response()->json(array_merge(
            $provider->getForDate($calendarDate)->toArray(),
            app(VersionManager::class)->current(),
        ));
    }
}
