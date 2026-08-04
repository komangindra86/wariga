<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\WarigaActivityResource;
use App\Models\WarigaActivity;
use App\Services\Wariga\DewasaSearchService;
use App\Services\Wariga\DailyRecommendationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DewasaController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly DewasaSearchService $search,
        private readonly DailyRecommendationService $daily,
    ) {}

    public function today(): JsonResponse
    {
        $timezone = (string) config('wariga.timezone', 'Asia/Makassar');

        return $this->success(array_merge(
            $this->daily->forDate(Carbon::now($timezone)),
            $this->metadata(),
        ));
    }

    public function activities(Request $request): JsonResponse
    {
        $perPage = min(250, max(1, (int) $request->input('per_page', 20)));
        $paginator = WarigaActivity::query()->where('is_active', true)->orderBy('urutan')->paginate($perPage);

        return $this->success(array_merge([
            'items' => WarigaActivityResource::collection($paginator->items())->resolve(),
            'pagination' => [
                'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
                'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
            ],
        ], $this->metadata()));
    }

    public function search(Request $request): JsonResponse
    {
        $validator = validator($request->query(), [
            'activity' => ['required', 'string'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'mode' => ['nullable', 'in:simple,complete,expert'],
            'sort' => ['nullable', 'in:ranking,date_asc,date_desc,score_desc'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
            'status' => ['nullable', 'string'],
        ]);
        if ($validator->fails()) {
            return $this->failure('Parameter pencarian tidak valid.', $validator->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }
        $activity = $this->findActivity($request->string('activity')->toString());
        if (! $activity) {
            return $this->failure('Kegiatan tidak ditemukan.', [], 'NOT_FOUND', 404);
        }
        try {
            $result = $this->search->search($activity->id, Carbon::parse($request->start_date), Carbon::parse($request->end_date), [
                'mode' => $request->input('mode', 'simple'), 'sort' => $request->input('sort', 'ranking'),
                'limit' => (int) $request->input('limit', 10), 'status_filter' => $request->input('status'),
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->failure($e->getMessage(), [], 'VALIDATION_ERROR', 422);
        }

        return $this->success(array_merge(['activity' => (new WarigaActivityResource($activity))->resolve()], $result->toArray(), $this->metadata('verified')));
    }

    public function show(string $activity, string $date): JsonResponse
    {
        request()->query->add(['activity' => $activity, 'start_date' => $date, 'end_date' => $date, 'mode' => 'expert', 'limit' => 1]);

        return $this->search(request());
    }

    private function findActivity(string $value): ?WarigaActivity
    {
        return WarigaActivity::query()->where('is_active', true)
            ->where(fn ($query) => $query->where('slug', $value)->orWhere('kode', $value)->when(ctype_digit($value), fn ($q) => $q->orWhere('id', (int) $value)))
            ->first();
    }
}
