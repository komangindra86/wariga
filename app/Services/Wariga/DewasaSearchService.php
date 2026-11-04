<?php

namespace App\Services\Wariga;

use App\Contracts\LunarCalendarProviderInterface;
use App\DTO\DewasaDateRecommendation;
use App\DTO\DewasaSearchResult;
use App\Services\Calendar\BaliCalendarService;
use App\Services\Calendar\CalendarObservanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DewasaSearchService
{
    private const MODES = ['simple', 'complete', 'expert'];

    private const SORTS = ['ranking', 'date_asc', 'date_desc', 'score_desc'];

    public function __construct(
        private readonly BaliCalendarService $calendarService,
        private readonly LunarCalendarProviderInterface $lunarProvider,
        private readonly AlaAyuMasterEvaluationService $alaAyuEvaluation,
        private readonly CalendarObservanceService $observances,
        private readonly DewasaExplanationService $explanations,
    ) {}

    public function search(
        int $activityId,
        Carbon $startDate,
        Carbon $endDate,
        array $options = [],
    ): DewasaSearchResult {
        $timezone = (string) config('wariga.timezone', 'Asia/Makassar');
        $start = $startDate->copy()->setTimezone($timezone)->startOfDay();
        $end = $endDate->copy()->setTimezone($timezone)->startOfDay();
        $this->validateRange($start, $end);

        $mode = (string) ($options['mode'] ?? 'simple');
        $sort = (string) ($options['sort'] ?? 'ranking');
        $limit = (int) ($options['limit'] ?? config('wariga.search.default_limit', 10));
        $statusFilter = $options['status_filter'] ?? null;
        $this->validateOptions($mode, $sort, $limit, $statusFilter);
        $this->ensureActivityExists($activityId);

        $cacheKey = $this->cacheKey($activityId, $start, $end, $mode, $sort, $limit, $statusFilter);

        return Cache::remember(
            $cacheKey,
            now()->addSeconds((int) config('wariga.search.cache_ttl', 3600)),
            fn (): DewasaSearchResult => $this->performSearch(
                $activityId, $start, $end, $mode, $sort, $limit, $statusFilter,
            ),
        );
    }

    private function performSearch(
        int $activityId,
        Carbon $start,
        Carbon $end,
        string $mode,
        string $sort,
        int $limit,
        string|array|null $statusFilter,
    ): DewasaSearchResult {
        $all = [];
        $activityName = (string) DB::table('wariga_activities')->where('id', $activityId)->value('nama');
        $cursor = $start->copy();
        $chunkSize = max(1, (int) config('wariga.search.chunk_size', 31));

        while ($cursor->lte($end)) {
            $chunkEnd = $cursor->copy()->addDays($chunkSize - 1)->min($end);

            for ($date = $cursor->copy(); $date->lte($chunkEnd); $date->addDay()) {
                $all[] = $this->evaluateDate($activityId, $activityName, $date, $mode);
            }

            $cursor = $chunkEnd->copy()->addDay();
        }

        $counts = array_count_values(array_map(
            fn (DewasaDateRecommendation $item): string => $item->status,
            $all,
        ));
        $filtered = $this->filterByStatus($all, $statusFilter);
        $this->sortRecommendations($filtered, $sort, $start);

        return new DewasaSearchResult(
            totalDatesChecked: count($all),
            recommendedCount: $counts['recommended'] ?? 0,
            considerationCount: $counts['consideration'] ?? 0,
            avoidCount: $counts['avoid'] ?? 0,
            recommendations: array_slice($filtered, 0, $limit),
            mode: $mode,
        );
    }

    private function evaluateDate(int $activityId, string $activityName, Carbon $date, string $mode): DewasaDateRecommendation
    {
        $calendar = $this->calendarService->calculate($date);
        $lunar = $this->lunarProvider->getForDate($date);
        $result = $this->alaAyuEvaluation->evaluate(
            $activityId,
            $calendar->toArray(),
            $lunar->toArray(),
            $mode === 'expert',
        );
        $rerainan = array_column(array_filter(
            $this->observances->forDate($date->toDateString(), $calendar->toArray(), $lunar->toArray())['observances'],
            fn (array $event): bool => in_array($event['type'], ['hindu_holiday', 'balinese_observance'], true),
        ), 'name');

        return new DewasaDateRecommendation(
            date: $date->toDateString(),
            score: $result['score'],
            classification: $result['classification'],
            status: $result['status'],
            positiveRuleCount: $result['positive_count'],
            prohibitionCount: $result['negative_count'],
            reasons: $result['reasons'],
            explanation: $this->explanations->explain($activityName, $result, $rerainan),
            calendar: $calendar->toArray(),
            lunar: $lunar->toArray(),
        );
    }

    private function sortRecommendations(array &$items, string $sort, Carbon $start): void
    {
        usort($items, function (DewasaDateRecommendation $a, DewasaDateRecommendation $b) use ($sort, $start): int {
            return match ($sort) {
                'date_asc' => $a->date <=> $b->date,
                'date_desc' => $b->date <=> $a->date,
                'score_desc' => ($b->score <=> $a->score) ?: ($a->date <=> $b->date),
                default => $this->compareRanking($a, $b, $start),
            };
        });
    }

    private function compareRanking(
        DewasaDateRecommendation $a,
        DewasaDateRecommendation $b,
        Carbon $start,
    ): int {
        return (DewasaDateRecommendation::statusPriority($b->status)
                <=> DewasaDateRecommendation::statusPriority($a->status))
            ?: ($b->score <=> $a->score)
            ?: ($b->positiveRuleCount <=> $a->positiveRuleCount)
            ?: ($a->prohibitionCount <=> $b->prohibitionCount)
            ?: ($start->diffInDays(Carbon::parse($a->date))
                <=> $start->diffInDays(Carbon::parse($b->date)))
            ?: ($a->date <=> $b->date);
    }

    private function filterByStatus(array $items, string|array|null $filter): array
    {
        if ($filter === null || $filter === '' || $filter === []) {
            return $items;
        }

        $allowed = is_array($filter) ? $filter : explode(',', $filter);

        return array_values(array_filter(
            $items,
            fn (DewasaDateRecommendation $item): bool => in_array($item->status, $allowed, true),
        ));
    }

    private function validateRange(Carbon $start, Carbon $end): void
    {
        if ($end->lt($start)) {
            throw new InvalidArgumentException('Tanggal akhir tidak boleh sebelum tanggal awal.');
        }

        if ($start->diffInDays($end) + 1 > (int) config('wariga.search.max_range_days', 366)) {
            throw new InvalidArgumentException('Rentang pencarian publik maksimal 366 hari.');
        }
    }

    private function validateOptions(string $mode, string $sort, int $limit, mixed $statusFilter): void
    {
        if (! in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException('Mode pencarian harus simple, complete, atau expert.');
        }
        if (! in_array($sort, self::SORTS, true)) {
            throw new InvalidArgumentException('Urutan pencarian tidak didukung.');
        }
        if ($limit < 1 || $limit > (int) config('wariga.search.max_limit', 100)) {
            throw new InvalidArgumentException('Limit pencarian harus antara 1 dan 100.');
        }

        $statuses = is_array($statusFilter) ? $statusFilter : explode(',', (string) $statusFilter);
        foreach (array_filter($statuses) as $status) {
            if (! in_array($status, ['recommended', 'consideration', 'avoid'], true)) {
                throw new InvalidArgumentException('Filter status tidak didukung.');
            }
        }
    }

    private function ensureActivityExists(int $activityId): void
    {
        $exists = DB::table('wariga_activities')
            ->where('id', $activityId)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('Kegiatan tidak ditemukan atau tidak aktif.');
        }
        if (! $this->alaAyuEvaluation->hasDefinitions($activityId)) {
            throw new InvalidArgumentException('Basis pengetahuan kegiatan belum tersedia.');
        }
    }

    private function cacheKey(
        int $activityId,
        Carbon $start,
        Carbon $end,
        string $mode,
        string $sort,
        int $limit,
        mixed $statusFilter,
    ): string {
        $alaAyuVersion = DB::table('ala_ayu_activity_rules as relation')
            ->join('ala_ayu_masters as master', 'master.id', '=', 'relation.ala_ayu_master_id')
            ->where('relation.activity_id', $activityId)
            ->orderBy('relation.id')
            ->get([
                'relation.id', 'relation.effect', 'relation.updated_at',
                'master.nama', 'master.komposisi', 'master.updated_at as master_updated_at',
            ])
            ->all();
        $validationVersion = DB::table('kalender_bali_validation_months')
            ->where('activity_id', $activityId)
            ->orderBy('year')
            ->orderBy('month')
            ->get(['year', 'month', 'recommended_dates', 'validated_at', 'updated_at'])
            ->all();
        $parts = [
            'activity' => $activityId,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'engine' => config('wariga.engine_version', '0.4.0'),
            'ala_ayu_rules' => hash('sha256', json_encode($alaAyuVersion)),
            'validated_months' => hash('sha256', json_encode($validationVersion)),
            'mode' => $mode,
            'sort' => $sort,
            'limit' => $limit,
            'status' => $statusFilter,
        ];

        return 'wariga:dewasa-search:'.hash('sha256', json_encode($parts));
    }
}
