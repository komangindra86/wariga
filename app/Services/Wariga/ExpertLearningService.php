<?php

namespace App\Services\Wariga;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ExpertLearningService
{
    public function metrics(): array
    {
        $rows = DB::table('expert_validation_cases')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when expected_status = predicted_status then 1 else 0 end) as correct')
            ->selectRaw("sum(case when expected_status = 'recommended' and predicted_status != 'recommended' then 1 else 0 end) as false_negative")
            ->selectRaw("sum(case when expected_status != 'recommended' and predicted_status = 'recommended' then 1 else 0 end) as false_positive")
            ->first();

        $total = (int) ($rows->total ?? 0);
        $correct = (int) ($rows->correct ?? 0);

        $feedback = DB::table('dewasa_user_feedback')
            ->where('learning_consent', true)
            ->where('is_suspicious', false)
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when rating = 'helpful' then 1 else 0 end) as helpful")
            ->selectRaw("sum(case when rating = 'not_helpful' then 1 else 0 end) as not_helpful")
            ->first();

        return [
            'total' => $total,
            'correct' => $correct,
            'accuracy' => $total > 0 ? round(($correct / $total) * 100, 1) : null,
            'false_negative' => (int) ($rows->false_negative ?? 0),
            'false_positive' => (int) ($rows->false_positive ?? 0),
            'user_feedback_total' => (int) ($feedback->total ?? 0),
            'user_feedback_helpful' => (int) ($feedback->helpful ?? 0),
            'user_feedback_not_helpful' => (int) ($feedback->not_helpful ?? 0),
        ];
    }

    public function generateProposals(): int
    {
        $groups = DB::table('expert_validation_cases')
            ->whereColumn('expected_status', '!=', 'predicted_status')
            ->get()
            ->groupBy(fn ($case): string => $case->activity_id.'|'.$this->proposalType($case));

        $created = 0;
        foreach ($groups as $cases) {
            if ($cases->isEmpty()) {
                continue;
            }
            $first = $cases->first();
            $type = $this->proposalType($first);
            $dates = $cases->pluck('case_date')->values()->all();
            $confidence = min(95, 50 + ($cases->count() * 5));

            DB::table('expert_learning_proposals')->updateOrInsert(
                [
                    'activity_id' => $first->activity_id,
                    'proposal_type' => $type,
                    'status' => 'suggested',
                ],
                [
                    'title' => $this->title($type),
                    'rationale' => $this->rationale($type, $cases),
                    'evidence' => json_encode(['case_ids' => $cases->pluck('id'), 'dates' => $dates]),
                    'confidence' => $confidence,
                    'sample_size' => $cases->count(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            $created++;
        }

        return $created + $this->generateUserFeedbackProposals();
    }

    public function userFeedbackInsights(): array
    {
        $eligible = DB::table('dewasa_user_feedback as feedback')
            ->join('wariga_activities as activity', 'activity.id', '=', 'feedback.activity_id')
            ->where('feedback.learning_consent', true)
            ->where('feedback.is_suspicious', false);

        return [
            'groups' => (clone $eligible)
                ->select('activity.nama as activity_name', 'feedback.region', 'feedback.tradition')
                ->selectRaw('count(*) as total')
                ->selectRaw("sum(case when feedback.rating = 'helpful' then 1 else 0 end) as helpful")
                ->selectRaw("sum(case when feedback.rating = 'not_helpful' then 1 else 0 end) as not_helpful")
                ->groupBy('activity.id', 'activity.nama', 'feedback.region', 'feedback.tradition')
                ->orderByDesc('total')->limit(12)->get(),
            'recent' => (clone $eligible)
                ->latest('feedback.id')->limit(10)
                ->get([
                    'feedback.id', 'activity.nama as activity_name',
                    'feedback.recommendation_date',
                    'feedback.rating', 'feedback.region', 'feedback.tradition',
                    'feedback.consulted_expert', 'feedback.expert_role',
                    'feedback.reason', 'feedback.created_at',
                ]),
        ];
    }

    private function generateUserFeedbackProposals(): int
    {
        $groups = DB::table('dewasa_user_feedback')
            ->where('learning_consent', true)
            ->where('is_suspicious', false)
            ->get()
            ->groupBy('activity_id');
        $created = 0;

        foreach ($groups as $activityId => $items) {
            if ($items->count() < 5) {
                continue;
            }
            $disagreements = $items->where('rating', 'not_helpful');
            $weightedTotal = $items->sum(fn ($item): int => $item->consulted_expert ? 2 : 1);
            $weightedDisagreement = $disagreements->sum(fn ($item): int => $item->consulted_expert ? 2 : 1);
            $ratio = $weightedTotal > 0 ? $weightedDisagreement / $weightedTotal : 0;
            if ($ratio < 0.6) {
                continue;
            }
            $mostlyRecommended = $disagreements->where('predicted_status', 'recommended')->count()
                >= (int) ceil($disagreements->count() / 2);
            $type = $mostlyRecommended ? 'review_negative' : 'review_priority';
            $title = 'Tinjau pola masukan pengguna';

            DB::table('expert_learning_proposals')->updateOrInsert(
                [
                    'activity_id' => (int) $activityId,
                    'proposal_type' => $type,
                    'title' => $title,
                    'status' => 'suggested',
                ],
                [
                    'rationale' => $disagreements->count().' dari '.$items->count()
                        .' pengguna menilai rekomendasi kurang tepat.',
                    'evidence' => json_encode([
                        'source' => 'anonymized_user_feedback',
                        'feedback_ids' => $disagreements->pluck('id')->values(),
                        'regions' => $disagreements->pluck('region')->filter()->unique()->values(),
                        'traditions' => $disagreements->pluck('tradition')->filter()->unique()->values(),
                    ]),
                    'confidence' => round(min(95, $ratio * 100), 2),
                    'sample_size' => $items->count(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
            $created++;
        }

        return $created;
    }

    private function proposalType(object $case): string
    {
        if ($case->expected_status === 'recommended' && $case->predicted_status !== 'recommended') {
            return 'review_positive';
        }
        if ($case->expected_status !== 'recommended' && $case->predicted_status === 'recommended') {
            return 'review_negative';
        }

        return 'review_priority';
    }

    private function title(string $type): string
    {
        return match ($type) {
            'review_positive' => 'Tinjau faktor baik yang belum dikenali',
            'review_negative' => 'Tinjau larangan atau faktor pengalah',
            default => 'Tinjau prioritas alahing dewasa',
        };
    }

    private function rationale(string $type, Collection $cases): string
    {
        $count = $cases->count();

        return match ($type) {
            'review_positive' => "{$count} tanggal rujukan direkomendasikan, tetapi mesin belum merekomendasikannya.",
            'review_negative' => "{$count} tanggal direkomendasikan mesin, tetapi tidak dipilih oleh rujukan.",
            default => "{$count} tanggal berbeda pada tingkat keputusan. Periksa urutan faktor yang saling mengalahkan.",
        };
    }
}
