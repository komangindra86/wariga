<?php

namespace App\Services\Wariga;

/**
 * Menjelaskan hasil dewasa dengan bahasa sehari-hari: unsur mana yang menentukan,
 * dan mengapa unsur itu mengalahkan unsur lain menurut alahing dewasa.
 */
final class DewasaExplanationService
{
    /**
     * @param  array{status: string, reasons: list<array<string, mixed>>, validation?: string|null}  $result
     * @param  list<string>  $rerainan  nama hari raya/rerainan pada tanggal itu, yang utama lebih dulu
     * @return array{summary: string, points: list<string>}
     */
    public function explain(string $activity, array $result, array $rerainan = []): array
    {
        $positive = array_values(array_filter($result['reasons'], fn (array $r): bool => $r['rule_type'] === 'positive'));
        $negative = array_values(array_filter($result['reasons'], fn (array $r): bool => $r['rule_type'] !== 'positive'));
        $bestPositive = $positive[0] ?? null;
        $bestNegative = $negative[0] ?? null;
        $points = [];

        if ($bestPositive && (! $bestNegative || $bestPositive['alahing_priority'] < $bestNegative['alahing_priority'])) {
            $summary = "Baik untuk {$activity} karena ada {$bestPositive['rule_name']}.";
            $points[] = $this->falls($bestPositive);
            if ($bestNegative) {
                $points[] = "Ada juga {$bestNegative['rule_name']} yang kurang baik, tetapi kalah kuat: "
                    ."{$bestPositive['rule_name']} dihitung dari {$bestPositive['alahing_label']}, "
                    ."sedangkan {$bestNegative['rule_name']} hanya dari {$bestNegative['alahing_label']}.";
            }
        } elseif ($bestNegative) {
            $summary = "Sebaiknya dihindari untuk {$activity} karena ada {$bestNegative['rule_name']}.";
            $points[] = $this->falls($bestNegative);
            if (! $bestPositive) {
                $points[] = "Tidak ada hari baik (dewasa ayu) untuk {$activity} yang jatuh pada tanggal ini.";
            } elseif ($bestPositive['alahing_priority'] === $bestNegative['alahing_priority']) {
                $points[] = "Ada juga unsur baik, yaitu {$bestPositive['rule_name']}, dengan kekuatan yang sama. "
                    .'Bila kekuatannya sama, unsur yang kurang baik didahulukan demi kehati-hatian.';
            } else {
                $points[] = "Ada juga unsur baik, yaitu {$bestPositive['rule_name']}, tetapi kalah kuat: "
                    ."{$bestNegative['rule_name']} dihitung dari {$bestNegative['alahing_label']}, "
                    ."sedangkan {$bestPositive['rule_name']} hanya dari {$bestPositive['alahing_label']}.";
            }
        } else {
            $summary = "Tidak ada unsur baik atau buruk khusus untuk {$activity} pada tanggal ini.";
            $points[] = 'Tanggal ini netral. Bila bisa, pilih tanggal yang berstatus Baik.';
        }

        if ($bestPositive && $bestNegative) {
            $points[] = 'Menurut aturan alahing dewasa, yang paling kuat adalah sasih, lalu penanggal/pangelong, '
                .'lalu wuku, lalu hari (wewaran). Unsur yang lebih kuat menentukan hasil.';
        }

        if (($result['validation'] ?? null) === 'selected') {
            $summary = "Baik untuk {$activity} menurut daftar hari baik Kalender Bali bulan ini.";
            $points[] = 'Tanggal ini tercantum dalam daftar hari baik rujukan Kalender Bali untuk bulan ini.';
        } elseif (($result['validation'] ?? null) === 'not_selected') {
            $summary = "Perlu dipertimbangkan untuk {$activity}.";
            $points[] = 'Tanggal ini tidak tercantum dalam daftar hari baik rujukan Kalender Bali bulan ini, '
                .'jadi nilainya diturunkan menjadi pertimbangan.';
        }

        $matchedNames = array_column($result['reasons'], 'rule_name');
        $rerainan = array_values(array_diff($rerainan, $matchedNames));
        if ($rerainan !== [] && $result['status'] !== 'recommended') {
            $points[] = "Hari ini juga {$rerainan[0]}. "
                ."Rerainan adalah hari suci untuk sembahyang, tetapi tidak otomatis menjadi hari baik untuk {$activity}.";
        }

        return ['summary' => $summary, 'points' => $points];
    }

    /**
     * @param  array<string, mixed>  $reason
     */
    private function falls(array $reason): string
    {
        $sentence = "{$reason['rule_name']} ({$reason['matched_composition']}) jatuh pada tanggal ini.";
        $description = trim((string) ($reason['reason'] ?? ''));

        return $description === '' || str_starts_with($description, 'Unsur ') ? $sentence : "{$sentence} {$description}";
    }
}
