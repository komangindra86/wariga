<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\DewasaUserFeedback;
use App\Models\WarigaActivity;
use App\Services\Wariga\DewasaSearchService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UserFeedbackController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly DewasaSearchService $search) {}

    public function store(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'activity_id' => ['required', 'integer', 'exists:wariga_activities,id'],
            'recommendation_date' => ['required', 'date_format:Y-m-d'],
            'rating' => ['required', Rule::in(['helpful', 'not_helpful', 'unsure'])],
            'reason' => ['nullable', 'string', 'max:1000'],
            'region' => ['nullable', 'string', 'max:120'],
            'tradition' => ['nullable', 'string', 'max:160'],
            'consulted_expert' => ['nullable', 'boolean'],
            'expert_role' => ['nullable', 'string', 'max:120', 'required_if:consulted_expert,true'],
            'learning_consent' => ['accepted'],
        ]);
        if ($validator->fails()) {
            return $this->failure(
                'Umpan balik belum dapat disimpan.',
                $validator->errors()->toArray(),
                'VALIDATION_ERROR',
                422,
            );
        }

        $data = $validator->validated();
        $activity = WarigaActivity::query()->whereKey($data['activity_id'])
            ->where('is_active', true)->first();
        if (! $activity) {
            return $this->failure('Kegiatan tidak ditemukan.', [], 'NOT_FOUND', 404);
        }

        $date = Carbon::parse($data['recommendation_date']);
        $result = $this->search->search($activity->id, $date, $date, [
            'mode' => 'expert', 'limit' => 1,
        ]);
        $recommendation = $result->toArray()['recommendations'][0];
        $existing = DewasaUserFeedback::query()->where([
            'user_id' => $request->user()->id,
            'activity_id' => $activity->id,
            'recommendation_date' => $date->toDateString(),
        ])->first();
        $submissionCount = ($existing?->submission_count ?? 0) + 1;

        $feedback = DewasaUserFeedback::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'activity_id' => $activity->id,
                'recommendation_date' => $date->toDateString(),
            ],
            [
                'rating' => $data['rating'],
                'reason' => $data['reason'] ?? null,
                'region' => $data['region'] ?? null,
                'tradition' => $data['tradition'] ?? null,
                'consulted_expert' => (bool) ($data['consulted_expert'] ?? false),
                'expert_role' => ($data['consulted_expert'] ?? false)
                    ? ($data['expert_role'] ?? null) : null,
                'predicted_status' => $recommendation['status'],
                'predicted_score' => $recommendation['score'],
                'learning_consent' => true,
                'submission_count' => $submissionCount,
                'is_suspicious' => $submissionCount > 10,
                'consented_at' => now(),
            ],
        );

        return $this->success([
            'id' => $feedback->id,
            'rating' => $feedback->rating,
            'learning_consent' => true,
            'updated' => ! $feedback->wasRecentlyCreated,
        ], 'Terima kasih. Masukan Anda akan menjadi bahan tinjauan, bukan langsung mengubah aturan.', $feedback->wasRecentlyCreated ? 201 : 200);
    }
}
