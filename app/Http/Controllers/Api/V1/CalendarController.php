<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\LunarCalendarProviderInterface;
use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Calendar\BaliCalendarService;
use App\Services\Calendar\CalendarObservanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CalendarController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly BaliCalendarService $calendar,
        private readonly LunarCalendarProviderInterface $lunar,
        private readonly CalendarObservanceService $observances,
    ) {}

    public function today(): JsonResponse
    {
        return $this->show(now(config('wariga.timezone'))->toDateString());
    }

    public function upcoming(Request $request): JsonResponse
    {
        $days = min(120, max(1, (int) $request->integer('days', 90)));
        $start = now(config('wariga.timezone'))->startOfDay();
        $cacheKey = 'wariga:upcoming-observances:'.hash('sha256', json_encode([
            $start->toDateString(), $days, config('wariga.engine_version'),
            config('wariga.dataset_version'),
        ]));
        $events = Cache::remember($cacheKey, now()->addHours(12), function () use ($days, $start): array {
        $events = [];

        for ($offset = 1; $offset <= $days; $offset++) {
            $date = $start->copy()->addDays($offset);
            $calendar = $this->calendar->calculate($date)->toArray();
            $lunar = $this->lunar->getForDate($date)->toArray();
            foreach ($this->observances->forDate($date, $calendar, $lunar)['observances'] as $event) {
                if (! in_array($event['type'], ['balinese_observance', 'hindu_holiday', 'moon_phase'], true)) {
                    continue;
                }
                $events[] = $event + [
                    'date' => $date->toDateString(),
                    'days_until' => $offset,
                ];
            }
        }

            return $events;
        });

        return $this->success(array_merge(['items' => $events], $this->metadata()));
    }

    public function show(string $date): JsonResponse
    {
        try {
            $day = Carbon::createFromFormat('!Y-m-d', $date, config('wariga.timezone'));
            $errors = Carbon::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) {
                throw new \InvalidArgumentException;
            }
            $lunar = $this->lunar->getForDate($day)->toArray();
        } catch (Throwable) {
            return $this->failure('Tanggal harus menggunakan format YYYY-MM-DD.', ['date' => ['Tanggal tidak valid.']], 'VALIDATION_ERROR', 422);
        }

        $calendar = $this->calendar->calculate($day)->toArray();

        return $this->success(array_merge([
            'calendar' => $calendar,
            'lunar' => $lunar,
            ...$this->observances->forDate($day, $calendar, $lunar),
        ], $this->metadata($lunar['verification_status'] ?? 'unavailable')));
    }

    public function month(Request $request): JsonResponse
    {
        $validated = validator($request->query(), [
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,31'],
        ]);
        if ($validated->fails()) {
            return $this->failure('Parameter kalender tidak valid.', $validated->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }
        $now = now(config('wariga.timezone'));
        $start = Carbon::create((int) $request->input('year', $now->year), (int) $request->input('month', $now->month), 1, 0, 0, 0, config('wariga.timezone'));
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 31);
        $dates = [];
        foreach (range(1, $start->daysInMonth) as $day) {
            $dates[] = $start->copy()->day($day)->toDateString();
        }
        $slice = array_slice($dates, ($page - 1) * $perPage, $perPage);
        $items = array_map(function (string $date): array {
            $lunar = $this->lunar->getForDate(Carbon::parse($date, config('wariga.timezone')))->toArray();
            $calendar = $this->calendar->calculate($date)->toArray();

            return [
                'calendar' => $calendar,
                'lunar' => $lunar,
                ...$this->observances->forDate($date, $calendar, $lunar),
                'verification_status' => $lunar['verification_status'] ?? 'unavailable',
            ];
        }, $slice);

        return $this->success(array_merge([
            'items' => $items,
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total' => count($dates), 'last_page' => (int) ceil(count($dates) / $perPage)],
        ], $this->metadata()));
    }
}
