<?php

namespace App\DTO;

use JsonSerializable;

final readonly class DewasaSearchResult implements JsonSerializable
{
    /** @param array<int, DewasaDateRecommendation> $recommendations */
    public function __construct(
        public int $totalDatesChecked,
        public int $recommendedCount,
        public int $considerationCount,
        public int $avoidCount,
        public array $recommendations,
        public string $mode,
    ) {}

    public function toArray(): array
    {
        return [
            'summary' => [
                'total_dates_checked' => $this->totalDatesChecked,
                'recommended_count' => $this->recommendedCount,
                'consideration_count' => $this->considerationCount,
                'avoid_count' => $this->avoidCount,
            ],
            'recommendations' => array_map(
                fn (DewasaDateRecommendation $item): array => $item->toArray($this->mode),
                $this->recommendations,
            ),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
