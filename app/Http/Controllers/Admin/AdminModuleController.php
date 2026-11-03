<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CalendarLunarDate;
use App\Models\WarigaActivity;
use App\Models\UserContribution;
use App\Services\Admin\AuditLogger;
use App\Services\Lunar\LunarCsvImporter;
use App\Services\Lunar\KalenderBaliLunarSyncService;
use App\Services\Lunar\PengalantakaLunarCalculator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminModuleController extends Controller
{
    public function contributions(Request $request): View
    {
        $status = $request->string('status')->toString();
        $query = UserContribution::query()->latest();
        if ($status !== '') $query->where('status', $status);

        return view('admin.contributions', [
            'rows' => $query->paginate(30)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function transitionContribution(Request $request, UserContribution $contribution): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('validator_wariga') || $request->user()->hasAdminRole('admin_data'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['pending_review', 'reviewing', 'accepted', 'rejected'])]]);
        $contribution->update(['status' => $data['status']]);

        return back()->with('success', 'Status kontribusi diperbarui.');
    }

    public function master(string $type): View
    {
        $tables = ['wewaran' => 'master_sapta_wara', 'wuku' => 'master_wuku', 'sasih' => 'master_sasih'];
        abort_unless(isset($tables[$type]), 404);

        return view('admin.module', ['title' => 'Master '.ucfirst($type), 'rows' => DB::table($tables[$type])->orderBy('urutan')->paginate(30)]);
    }

    public function lunar(Request $request): View
    {
        $year = min(2100, max(1900, (int) $request->integer('year', now()->year)));
        $query = CalendarLunarDate::query()->with('sasih')->whereYear('tanggal_masehi', $year);

        return view('admin.lunar', [
            'year' => $year,
            'rows' => (clone $query)->latest('tanggal_masehi')->paginate(30)->withQueryString(),
            'summary' => [
                'total' => (clone $query)->count(),
                'verified' => (clone $query)->where('verification_status', 'verified')->count(),
                'disputed' => (clone $query)->where('verification_status', 'disputed')->count(),
                'nampih' => (clone $query)->where('is_nampih_sasih', true)->count(),
            ],
            'nampihRows' => (clone $query)->where('is_nampih_sasih', true)->orderBy('tanggal_masehi')->get(),
        ]);
    }

    public function syncLunar(Request $request, KalenderBaliLunarSyncService $sync, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('admin_data'), 403);
        $data = $request->validate(['year' => ['required', 'integer', 'between:1900,2100']]);
        set_time_limit(0);
        $result = $sync->syncYear((int) $data['year']);
        $audit->record('lunar.year_synchronized', CalendarLunarDate::class, null, [
            'year' => (int) $data['year'],
            ...$result,
        ]);

        $redirect = redirect()->route('admin.lunar', ['year' => $data['year']]);
        if ($result['failed'] !== []) {
            return $redirect->withErrors([
                'sync' => 'Sinkronisasi dibatalkan agar data lama tetap aman. Gagal mengambil '
                    .count($result['failed']).' tanggal: '.implode(', ', array_slice($result['failed'], 0, 10)),
            ]);
        }

        return $redirect->with('success', "Sinkronisasi {$data['year']} selesai: {$result['imported']} tanggal diperbarui.");
    }

    public function validateLunar(Request $request, PengalantakaLunarCalculator $calculator, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('validator_wariga'), 403);
        $data = $request->validate(['year' => ['required', 'integer', 'between:1900,2100']]);
        $year = (int) $data['year'];
        $rows = CalendarLunarDate::query()
            ->with('sasih')
            ->whereYear('tanggal_masehi', $year)
            ->where('verification_status', 'verified')
            ->orderBy('tanggal_masehi')
            ->get();
        $differences = [];

        foreach ($rows as $row) {
            $calculated = $calculator->calculate(Carbon::parse($row->tanggal_masehi));
            $actual = [
                'sasih' => $row->sasih?->kode,
                'fase' => $row->fase,
                'nomor_hari' => (int) $row->nomor_hari,
                'is_purnama' => (bool) $row->is_purnama,
                'is_tilem' => (bool) $row->is_tilem,
            ];
            $expected = [
                'sasih' => $calculated->sasih['kode'] ?? null,
                'fase' => $calculated->fase,
                'nomor_hari' => $calculated->nomorHari,
                'is_purnama' => $calculated->isPurnama,
                'is_tilem' => $calculated->isTilem,
            ];
            if ($actual !== $expected) {
                $differences[] = ['date' => $row->tanggal_masehi->format('Y-m-d'), 'data' => $actual, 'formula' => $expected];
            }
        }
        $result = ['checked' => $rows->count(), 'matched' => $rows->count() - count($differences), 'differences' => $differences];
        $audit->record('lunar.formula_validated', CalendarLunarDate::class, null, ['year' => $year, ...$result]);

        return redirect()->route('admin.lunar', ['year' => $year])
            ->with('validation_result', $result)
            ->with('success', "Validasi {$year} selesai.");
    }

    public function audits(): View
    {
        return view('admin.audits', ['logs' => AuditLog::with('user')->latest('created_at')->paginate(30)]);
    }

    public function activities(): View
    {
        return view('admin.activities', ['activities' => WarigaActivity::orderBy('urutan')->paginate(30)]);
    }

    public function storeActivity(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('admin_data'), 403);
        $data = $this->activityData($request);
        $data['is_active'] = false;
        $data['slug'] = Str::slug($data['nama']);
        $activity = WarigaActivity::create($data);
        $audit->record('activity.created', $activity, null, $activity->toArray());

        return back()->with('success', 'Kegiatan berhasil ditambahkan.');
    }

    public function updateActivity(Request $request, WarigaActivity $activity, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('admin_data'), 403);
        $old = $activity->toArray();
        $data = $this->activityData($request, $activity);
        if ($data['is_active'] && ! DB::table('ala_ayu_activity_rules')->where('activity_id', $activity->id)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'Kegiatan belum dapat diaktifkan karena Basis Pengetahuannya masih kosong.',
            ]);
        }
        $data['slug'] = Str::slug($data['nama']);
        $activity->update($data);
        $audit->record('activity.updated', $activity, $old, $activity->fresh()->toArray());

        return back()->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function import(): View
    {
        return view('admin.import');
    }

    public function importLunar(Request $request, LunarCsvImporter $importer, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('admin_data'), 403);
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $result = $importer->import($request->file('csv')->getRealPath());
        $audit->record('dataset.imported', CalendarLunarDate::class, null, [
            'filename' => $request->file('csv')->getClientOriginalName(),
            'dataset_version' => (string) config('wariga.dataset_version'),
            'imported' => $result['imported'],
            'failed_count' => count($result['failed']),
            'duplicate_count' => count($result['duplicates']),
        ]);

        return back()->with('success', "{$result['imported']} baris berhasil diimpor.")
            ->with('import_result', $result);
    }

    public function transitionLunar(Request $request, CalendarLunarDate $lunar, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('validator_wariga'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['verified', 'disputed', 'archived'])]]);
        $old = $lunar->toArray();
        $changes = ['verification_status' => $data['status']];
        if ($data['status'] === 'verified') {
            $changes += ['verified_by' => $request->user()->id, 'verified_at' => now(), 'is_active' => true];
        }
        if ($data['status'] === 'archived') {
            $changes['is_active'] = false;
        }
        $lunar->update($changes);
        $audit->record('lunar.'.$data['status'], $lunar, $old, $lunar->fresh()->toArray());

        return back()->with('success', 'Status data lunar diperbarui.');
    }

    public function settings(): View
    {
        return view('admin.settings', ['settings' => [
            'engine_version' => config('wariga.engine_version'),
            'rule_version' => config('wariga.rule_version'),
            'dataset_version' => config('wariga.dataset_version'),
            'initial_score' => config('wariga.initial_score'),
            'search.cache_ttl' => config('wariga.search.cache_ttl'),
        ]]);
    }

    public function updateSettings(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'engine_version' => ['required', 'string', 'max:50'],
            'rule_version' => ['required', 'string', 'max:50'],
            'dataset_version' => ['sometimes', 'string', 'max:50'],
            'initial_score' => ['required', 'integer', 'between:0,100'],
            'search_cache_ttl' => ['required', 'integer', 'between:60,86400'],
        ]);
        $values = [
            'engine_version' => $data['engine_version'], 'rule_version' => $data['rule_version'],
            'dataset_version' => $data['dataset_version'] ?? (string) config('wariga.dataset_version'),
            'initial_score' => (int) $data['initial_score'], 'search.cache_ttl' => (int) $data['search_cache_ttl'],
        ];
        foreach ($values as $key => $value) {
            $old = DB::table('engine_settings')->where('key', $key)->value('value');
            $old = $old === null ? config("wariga.{$key}") : json_decode($old, true);
            DB::table('engine_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value), 'description' => 'Dikelola dari panel admin', 'updated_by' => $request->user()->id, 'updated_at' => now(), 'created_at' => now()],
            );
            if ($old !== $value) {
                $action = match ($key) {
                    'engine_version' => 'engine.version_changed',
                    'rule_version' => 'rule.version_changed',
                    'dataset_version' => 'dataset.version_changed',
                    'initial_score' => 'engine.score_threshold_changed',
                    default => 'engine.formula_configuration_changed',
                };
                $audit->record($action, 'engine_settings', ['key' => $key, 'value' => $old], ['key' => $key, 'value' => $value]);
            }
            config(["wariga.{$key}" => $value]);
        }

        return back()->with('success', 'Konfigurasi engine berhasil disimpan.');
    }

    private function activityData(Request $request, ?WarigaActivity $activity = null): array
    {
        return $request->validate([
            'kode' => ['required', 'max:30', Rule::unique('wariga_activities')->ignore($activity)],
            'nama' => ['required', 'max:255'],
            'kategori' => ['required', 'max:100'],
            'deskripsi' => ['required'],
            'disclaimer' => ['required'],
            'urutan' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
