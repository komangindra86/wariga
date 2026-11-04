<?php

namespace App\Services\Wariga;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class AlaAyuMasterEvaluationService
{
    // Alahing dewasa: angka kecil mengalahkan angka besar.
    public const LEVEL_LABELS = [
        1 => 'sasih (bulan Bali)',
        2 => 'penanggal/pangelong (hitungan hari dalam bulan Bali)',
        3 => 'wuku (pekan Bali)',
        4 => 'hari (wewaran)',
    ];

    public function hasDefinitions(int $activityId): bool
    {
        return DB::table('ala_ayu_activity_rules')->where('activity_id', $activityId)->exists();
    }

    /**
     * @return array{
     *   score: int, classification: string, status: string,
     *   positive_count: int, negative_count: int, reasons: list<array<string, mixed>>,
     *   validation: 'selected'|'not_selected'|null
     * }
     */
    public function evaluate(int $activityId, array $calendar, array $lunar, bool $expert): array
    {
        $context = $this->context($calendar, $lunar);
        $matched = [];

        $rules = DB::table('ala_ayu_activity_rules as relation')
            ->join('ala_ayu_masters as master', 'master.id', '=', 'relation.ala_ayu_master_id')
            ->where('relation.activity_id', $activityId)
            ->orderBy('master.nama')
            ->get([
                'relation.id', 'relation.effect', 'relation.source_url',
                'master.nama', 'master.deskripsi', 'master.komposisi',
            ]);

        foreach ($rules as $rule) {
            $alternatives = json_decode((string) $rule->komposisi, true) ?: [];
            foreach ($alternatives as $alternative) {
                $tokens = $alternative['tokens'] ?? [];
                if (! $this->matches($tokens, $context)) {
                    continue;
                }

                $priority = $this->priority($tokens, $context);
                $effect = $rule->effect === 'positive' ? 1 : -1;
                $composition = $alternative['raw'] ?? implode(' ', $tokens);
                $description = trim((string) $rule->deskripsi);
                $reason = [
                    'rule_id' => (int) $rule->id,
                    'rule_code' => 'KB-MASTER-'.(int) $rule->id,
                    'rule_name' => $rule->nama,
                    'rule_type' => $rule->effect === 'positive' ? 'positive' : 'prohibition',
                    'severity' => $rule->effect === 'positive' ? 'info' : 'high',
                    'matched' => true,
                    'score_effect' => $effect * (50 - ($priority * 10)),
                    'reason' => $description !== '' ? $description : ($rule->effect === 'positive'
                        ? "Unsur {$rule->nama} cocok pada tanggal ini dan mendukung kegiatan yang dipilih."
                        : "Unsur {$rule->nama} cocok pada tanggal ini dan perlu dipertimbangkan untuk kegiatan yang dipilih."),
                    'alahing_priority' => $priority,
                    'alahing_label' => self::LEVEL_LABELS[$priority],
                    'matched_composition' => $composition,
                ];
                if ($expert) {
                    $reason += [
                        'attribute' => 'ala_ayu_master',
                        'operator' => 'all_tokens_match',
                        'comparison_value' => $tokens,
                        'actual_value' => $context['values'],
                        'source_name' => 'Kalender Bali Digital — referensi eksternal',
                        'source_reference' => $rule->source_url,
                        'source_page' => 'referensialaayu.php / pilihdewasa.php',
                        'verification_status' => 'external_reference',
                        'rule_version' => config('wariga.rule_version'),
                    ];
                }
                $matched[] = $reason;
                break;
            }
        }
        // Unsur paling kuat (alahing terkecil) tampil lebih dulu; bila setara, larangan dulu.
        usort($matched, fn (array $a, array $b): int => [$a['alahing_priority'], $a['rule_type'] === 'positive']
            <=> [$b['alahing_priority'], $b['rule_type'] === 'positive']);

        $positive = array_values(array_filter(
            $matched,
            fn (array $reason): bool => $reason['rule_type'] === 'positive',
        ));
        $negative = array_values(array_filter(
            $matched,
            fn (array $reason): bool => $reason['rule_type'] === 'prohibition',
        ));
        $bestPositive = $this->bestPriority($positive);
        $bestNegative = $this->bestPriority($negative);

        if ($bestPositive !== null && ($bestNegative === null || $bestPositive < $bestNegative)) {
            $score = 100 - ($bestPositive * 10);
        } elseif ($bestNegative !== null) {
            $score = max(0, ($bestNegative - 1) * 10);
        } else {
            $score = 50;
        }

        $classification = $this->classification($score);

        $result = [
            'score' => $score,
            'classification' => $classification,
            'status' => $score >= 55 ? 'recommended' : ($score >= 40 ? 'consideration' : 'avoid'),
            'positive_count' => count($positive),
            'negative_count' => count($negative),
            'reasons' => $matched,
            'validation' => null,
        ];

        return $this->applyValidatedSelection($activityId, $calendar, $result);
    }

    private function applyValidatedSelection(int $activityId, array $calendar, array $result): array
    {
        $date = Carbon::parse($calendar['tanggal_masehi']);
        $validation = DB::table('kalender_bali_validation_months')
            ->where('activity_id', $activityId)
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->first();
        if (! $validation) {
            return $result;
        }

        $recommendedDates = json_decode((string) $validation->recommended_dates, true) ?: [];
        $isSelected = in_array($date->toDateString(), $recommendedDates, true);
        if (! $isSelected && $result['status'] === 'recommended') {
            $result['score'] = 50;
            $result['classification'] = 'Perlu Pertimbangan';
            $result['status'] = 'consideration';
            $result['validation'] = 'not_selected';
        } elseif ($isSelected && $result['status'] !== 'recommended') {
            $result['score'] = 60;
            $result['classification'] = 'Cukup Baik';
            $result['status'] = 'recommended';
            $result['validation'] = 'selected';
        }

        return $result;
    }

    private function context(array $calendar, array $lunar): array
    {
        $values = [];
        foreach (($calendar['wewaran'] ?? []) as $element) {
            if (is_array($element) && isset($element['nama'])) {
                $values[] = $this->normalize((string) $element['nama']);
            }
        }
        $wuku = $this->normalize((string) data_get($calendar, 'wuku.nama', ''));
        $sasih = $this->normalize((string) data_get($lunar, 'sasih.nama', ''));
        $values = array_values(array_unique(array_filter([...$values, $wuku, $sasih])));

        return [
            'values' => $values,
            'wuku' => $wuku,
            'sasih' => $sasih,
            'fase' => strtolower((string) ($lunar['fase'] ?? '')),
            'nomor_hari' => isset($lunar['nomor_hari']) ? (int) $lunar['nomor_hari'] : null,
            'is_purnama' => (bool) ($lunar['is_purnama'] ?? false),
            'is_tilem' => (bool) ($lunar['is_tilem'] ?? false),
        ];
    }

    private function matches(array $tokens, array $context): bool
    {
        foreach ($tokens as $index => $token) {
            $normalized = $this->normalize((string) $token);
            if (ctype_digit($normalized)) {
                if ($context['nomor_hari'] !== (int) $normalized) {
                    return false;
                }
            } elseif ($normalized === 'penanggal' || $normalized === 'pangelong') {
                if ($context['fase'] !== $normalized) {
                    return false;
                }
            } elseif ($normalized === 'purnama') {
                if (! $context['is_purnama']) {
                    return false;
                }
            } elseif ($normalized === 'tilem') {
                if (! $context['is_tilem']) {
                    return false;
                }
            } elseif ($normalized === '=' || $normalized === 'sasih') {
                continue;
            } elseif (! in_array($normalized, $context['values'], true)) {
                return false;
            }
        }

        return $tokens !== [];
    }

    private function priority(array $tokens, array $context): int
    {
        $normalized = array_map(fn ($token) => $this->normalize((string) $token), $tokens);
        if ($context['sasih'] !== '' && in_array($context['sasih'], $normalized, true)) {
            return 1;
        }
        if (array_intersect($normalized, ['penanggal', 'pangelong', 'purnama', 'tilem'])) {
            return 2;
        }
        if ($context['wuku'] !== '' && in_array($context['wuku'], $normalized, true)) {
            return 3;
        }

        return 4;
    }

    private function bestPriority(array $reasons): ?int
    {
        if ($reasons === []) {
            return null;
        }

        return min(array_column($reasons, 'alahing_priority'));
    }

    private function classification(int $score): string
    {
        foreach (config('wariga.classifications', []) as $classification) {
            if ($score >= $classification['min'] && $score <= $classification['max']) {
                return $classification['label'];
            }
        }

        return 'Perlu Pertimbangan';
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'keliwon' => 'kliwon',
            'warigadean' => 'warigadian',
            'kulawu' => 'kelawu',
            'dunggulan' => 'dungulan',
            'kedasa' => 'kadasa',
            default => $value,
        };
    }
}
