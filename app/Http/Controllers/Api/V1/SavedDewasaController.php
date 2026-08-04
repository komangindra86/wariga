<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\SavedDewasa;
use App\Models\SharedDewasa;
use App\Models\WarigaActivity;
use App\Services\Wariga\DewasaSearchService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SavedDewasaController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly DewasaSearchService $search) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $items = SavedDewasa::query()->where('user_id', $request->user()->id)->with('activity:id,nama,slug,kode')->latest()->paginate($perPage);

        return $this->success([
            'items' => array_map(fn (SavedDewasa $item) => $this->serialize($item), $items->items()),
            'pagination' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total(), 'last_page' => $items->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = validator($request->all(), ['activity_id' => ['required', 'integer', 'exists:wariga_activities,id'], 'date' => ['required', 'date_format:Y-m-d'], 'title' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:2000']]);
        if ($validator->fails()) {
            return $this->failure('Data simpan tidak valid.', $validator->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }
        $activity = WarigaActivity::query()->whereKey($request->activity_id)->where('is_active', true)->first();
        if (! $activity) {
            return $this->failure('Kegiatan tidak ditemukan.', [], 'NOT_FOUND', 404);
        }
        $result = $this->search->search($activity->id, Carbon::parse($request->date), Carbon::parse($request->date), ['mode' => 'expert', 'limit' => 1]);
        $snapshot = array_merge($result->toArray(), $this->metadata());
        $recommendation = $snapshot['recommendations'][0];
        $saved = SavedDewasa::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'activity_id' => $activity->id, 'tanggal' => $request->date],
            [
                'score_snapshot' => $recommendation['score'], 'status_snapshot' => $recommendation['status'],
                'notes' => $request->notes, 'result_snapshot' => $snapshot,
                'engine_version' => $snapshot['engine_version'], 'rule_version' => $snapshot['rule_version'],
                'dataset_version' => $snapshot['dataset_version'],
            ],
        );

        return $this->success($this->serialize($saved->load('activity:id,nama,slug,kode')), 'Dewasa ayu berhasil disimpan.', $saved->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $saved = SavedDewasa::query()->where('user_id', $request->user()->id)->with('activity:id,nama,slug,kode')->find($id);

        return $saved ? $this->success($this->serialize($saved)) : $this->failure('Data tersimpan tidak ditemukan.', [], 'NOT_FOUND', 404);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = SavedDewasa::query()->where('user_id', $request->user()->id)->whereKey($id)->delete();

        return $deleted ? $this->success(null, 'Data tersimpan berhasil dihapus.') : $this->failure('Data tersimpan tidak ditemukan.', [], 'NOT_FOUND', 404);
    }

    public function share(Request $request): JsonResponse
    {
        $validator = validator($request->all(), ['saved_dewasa_id' => ['required', 'integer'], 'expires_in_days' => ['nullable', 'integer', 'between:1,365']]);
        if ($validator->fails()) {
            return $this->failure('Data berbagi tidak valid.', $validator->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }
        $saved = SavedDewasa::query()->where('user_id', $request->user()->id)->find($request->saved_dewasa_id);
        if (! $saved) {
            return $this->failure('Data tersimpan tidak ditemukan.', [], 'NOT_FOUND', 404);
        }
        $share = SharedDewasa::query()->create([
            'created_by' => $request->user()->id, 'activity_id' => $saved->activity_id,
            'tanggal' => $saved->tanggal, 'public_token' => Str::random(48),
            'result_snapshot' => $saved->result_snapshot, 'engine_version' => $saved->engine_version,
            'rule_version' => $saved->rule_version, 'dataset_version' => $saved->dataset_version,
            'expires_at' => $request->filled('expires_in_days') ? now()->addDays((int) $request->expires_in_days) : null,
            'created_at' => now(),
        ]);

        return $this->success(['token' => $share->public_token, 'url' => url('/api/v1/shared/'.$share->public_token), 'expires_at' => $share->expires_at?->toIso8601String()], 'Tautan berbagi berhasil dibuat.', 201);
    }

    public function shared(string $token): JsonResponse
    {
        $share = SharedDewasa::query()->where('public_token', $token)->first();
        if (! $share || ($share->expires_at && $share->expires_at->isPast())) {
            return $this->failure('Tautan berbagi tidak ditemukan atau kedaluwarsa.', [], 'NOT_FOUND', 404);
        }

        $share->increment('view_count');

        return $this->success(array_merge([
            'shared_at' => $share->created_at->toIso8601String(), 'view_count' => $share->view_count,
            'snapshot_outdated' => $this->isOutdated($share->engine_version, $share->rule_version, $share->dataset_version),
            'snapshot_notice' => $this->snapshotNotice($share->engine_version, $share->rule_version, $share->dataset_version),
        ], $share->result_snapshot));
    }

    private function serialize(SavedDewasa $item): array
    {
        return [
            'id' => $item->id, 'date' => $item->tanggal->toDateString(),
            'score' => $item->score_snapshot, 'status' => $item->status_snapshot, 'notes' => $item->notes,
            'engine_version' => $item->engine_version, 'rule_version' => $item->rule_version,
            'dataset_version' => $item->dataset_version,
            'snapshot_outdated' => $this->isOutdated($item->engine_version, $item->rule_version, $item->dataset_version),
            'snapshot_notice' => $this->snapshotNotice($item->engine_version, $item->rule_version, $item->dataset_version),
            'activity' => $item->activity ? ['id' => $item->activity->id, 'code' => $item->activity->kode, 'name' => $item->activity->nama, 'slug' => $item->activity->slug] : null,
            'result' => $item->result_snapshot, 'created_at' => $item->created_at->toIso8601String(),
        ];
    }

    private function isOutdated(string $engineVersion, string $ruleVersion, string $datasetVersion): bool
    {
        return $engineVersion !== (string) config('wariga.engine_version')
            || $ruleVersion !== (string) config('wariga.rule_version')
            || $datasetVersion !== (string) config('wariga.dataset_version');
    }

    private function snapshotNotice(string $engineVersion, string $ruleVersion, string $datasetVersion): ?string
    {
        return $this->isOutdated($engineVersion, $ruleVersion, $datasetVersion)
            ? 'Aturan Wariga telah diperbarui sejak hasil ini disimpan.'
            : null;
    }
}
