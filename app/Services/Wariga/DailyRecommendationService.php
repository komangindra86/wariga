<?php

namespace App\Services\Wariga;

use App\Contracts\LunarCalendarProviderInterface;
use App\Models\WarigaActivity;
use App\Services\Calendar\BaliCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class DailyRecommendationService
{
    public function __construct(
        private readonly BaliCalendarService $calendarService,
        private readonly LunarCalendarProviderInterface $lunarProvider,
        private readonly AlaAyuMasterEvaluationService $evaluation,
    ) {}

    public function forDate(Carbon $date): array
    {
        $date = $date->copy()->setTimezone((string) config('wariga.timezone', 'Asia/Makassar'))->startOfDay();

        return Cache::remember(
            $this->cacheKey($date),
            $date->copy()->addDay()->addMinutes(5),
            fn (): array => $this->calculate($date),
        );
    }

    private function calculate(Carbon $date): array
    {
        $calendar = $this->calendarService->calculate($date)->toArray();
        $lunar = $this->lunarProvider->getForDate($date)->toArray();
        $items = [];

        $activities = WarigaActivity::query()
            ->where('is_active', true)
            ->whereExists(fn ($query) => $query->selectRaw('1')
                ->from('ala_ayu_activity_rules')
                ->whereColumn('ala_ayu_activity_rules.activity_id', 'wariga_activities.id'))
            ->orderBy('urutan')
            ->get();

        foreach ($activities as $activity) {
            $result = $this->evaluation->evaluate($activity->id, $calendar, $lunar, false);
            if ($result['status'] === 'consideration') {
                continue;
            }

            $reasons = array_values(array_filter(
                $result['reasons'],
                fn (array $reason): bool => $result['status'] === 'recommended'
                    ? $reason['rule_type'] === 'positive'
                    : $reason['rule_type'] === 'prohibition',
            ));

            $items[] = [
                'id' => $activity->id,
                'code' => $activity->kode,
                'slug' => $activity->slug,
                'name' => $activity->nama,
                'category' => $activity->kategori,
                'icon' => $activity->icon,
                'score' => $result['score'],
                'status' => $result['status'],
                'reason' => $reasons[0]['reason'] ?? null,
                'matched_rule' => $reasons[0]['rule_name'] ?? null,
                'positive_count' => $result['positive_count'],
                'negative_count' => $result['negative_count'],
            ];
        }

        $recommended = array_values(array_filter($items, fn (array $item): bool => $item['status'] === 'recommended'));
        $avoid = array_values(array_filter($items, fn (array $item): bool => $item['status'] === 'avoid'));
        usort($recommended, fn (array $a, array $b): int => ($b['score'] <=> $a['score']) ?: ($b['positive_count'] <=> $a['positive_count']) ?: ($a['name'] <=> $b['name']));
        usort($avoid, fn (array $a, array $b): int => ($a['score'] <=> $b['score']) ?: ($b['negative_count'] <=> $a['negative_count']) ?: ($a['name'] <=> $b['name']));

        return [
            'date' => $date->toDateString(),
            'recommended' => array_slice($recommended, 0, 6),
            'avoid' => array_slice($avoid, 0, 6),
            'calendar_basis' => [
                'saptawara' => data_get($calendar, 'wewaran.sapta_wara.nama'),
                'pancawara' => data_get($calendar, 'wewaran.panca_wara.nama'),
                'wuku' => data_get($calendar, 'wuku.nama'),
                'sasih' => data_get($lunar, 'sasih.nama'),
            ],
            'verification_status' => $lunar['verification_status'] ?? 'calculated',
        ];
    }

    private function cacheKey(Carbon $date): string
    {
        $versions = [
            'date' => $date->toDateString(),
            'engine' => config('wariga.engine_version'),
            'rules' => config('wariga.rule_version'),
            'dataset' => config('wariga.dataset_version'),
            'activities' => DB::table('wariga_activities')->max('updated_at'),
            'relations' => DB::table('ala_ayu_activity_rules')->max('updated_at'),
            'masters' => DB::table('ala_ayu_masters')->max('updated_at'),
            'validation' => DB::table('kalender_bali_validation_months')->max('updated_at'),
        ];

        return 'wariga:daily-recommendation:'.hash('sha256', json_encode($versions));
    }
}
