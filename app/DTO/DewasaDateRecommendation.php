<?php

namespace App\DTO;

final readonly class DewasaDateRecommendation
{
    /**
     * @param  array<int, array<string, mixed>>  $reasons
     * @param  array{summary: string, points: list<string>}  $explanation
     * @param  array<string, mixed>  $calendar
     * @param  array<string, mixed>  $lunar
     */
    public function __construct(
        public string $date,
        public int $score,
        public string $classification,
        public string $status,
        public int $positiveRuleCount,
        public int $prohibitionCount,
        public array $reasons,
        public array $explanation,
        public array $calendar,
        public array $lunar,
    ) {}

    public function toArray(string $mode = 'simple'): array
    {
        $result = [
            'date' => $this->date,
            'score' => $this->score,
            'classification' => $this->classification,
            'status' => $this->status,
            'explanation' => $this->explanation,
            'main_reasons' => $mode === 'simple' ? array_slice($this->reasons, 0, 3) : $this->reasons,
        ];

        if ($mode !== 'simple') {
            $result['positive_rule_count'] = $this->positiveRuleCount;
            $result['prohibition_count'] = $this->prohibitionCount;
            $result['calendar'] = $this->calendar;
            $result['lunar'] = $this->lunar;
        }

        if ($mode === 'expert') {
            $result['ranking_factors'] = [
                'status_priority' => self::statusPriority($this->status),
                'positive_rule_count' => $this->positiveRuleCount,
                'prohibition_count' => $this->prohibitionCount,
            ];
        }

        return $result;
    }

    public static function statusPriority(string $status): int
    {
        return match ($status) {
            'recommended' => 3,
            'consideration' => 2,
            default => 1,
        };
    }
}
