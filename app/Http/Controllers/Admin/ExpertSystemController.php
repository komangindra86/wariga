<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlaAyuMaster;
use App\Models\WarigaActivity;
use App\Contracts\LunarCalendarProviderInterface;
use App\Services\Admin\AuditLogger;
use App\Services\Calendar\BaliCalendarService;
use App\Services\Wariga\AlaAyuMasterEvaluationService;
use App\Services\Wariga\ExpertLearningService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpertSystemController extends Controller
{
    public function index(ExpertLearningService $learning): View
    {
        $feedback = $learning->userFeedbackInsights();

        return view('admin.expert.index', [
            'metrics' => $learning->metrics(),
            'activities' => WarigaActivity::where('is_active', true)->orderBy('nama')->get(),
            'recentCases' => DB::table('expert_validation_cases as cases')
                ->join('wariga_activities as activity', 'activity.id', '=', 'cases.activity_id')
                ->latest('cases.id')->limit(8)
                ->get(['cases.*', 'activity.nama as activity_name']),
            'proposals' => DB::table('expert_learning_proposals as proposal')
                ->join('wariga_activities as activity', 'activity.id', '=', 'proposal.activity_id')
                ->latest('proposal.id')->limit(8)
                ->get(['proposal.*', 'activity.nama as activity_name']),
            'feedbackGroups' => $feedback['groups'],
            'recentFeedback' => $feedback['recent'],
        ]);
    }

    public function knowledge(Request $request): View
    {
        $activityId = $request->integer('activity_id') ?: null;

        return view('admin.expert.knowledge', [
            'activities' => WarigaActivity::where('is_active', true)->orderBy('nama')->get(),
            'selectedActivity' => $activityId ? WarigaActivity::find($activityId) : null,
            'masters' => AlaAyuMaster::query()->orderBy('nama')->get(),
            'mappings' => $activityId
                ? DB::table('ala_ayu_activity_rules as relation')
                    ->join('ala_ayu_masters as master', 'master.id', '=', 'relation.ala_ayu_master_id')
                    ->where('relation.activity_id', $activityId)->orderBy('master.nama')
                    ->get(['relation.*', 'master.nama', 'master.deskripsi', 'master.komposisi_teks'])
                : collect(),
        ]);
    }

    public function simulator(): View
    {
        return view('admin.expert.simulator', [
            'activities' => WarigaActivity::where('is_active', true)
                ->whereExists(fn ($query) => $query->selectRaw('1')
                    ->from('ala_ayu_activity_rules')
                    ->whereColumn('ala_ayu_activity_rules.activity_id', 'wariga_activities.id'))
                ->orderBy('nama')->get(),
            'simulation' => null,
        ]);
    }

    public function simulate(
        Request $request,
        BaliCalendarService $calendarService,
        LunarCalendarProviderInterface $lunarProvider,
        AlaAyuMasterEvaluationService $evaluation,
    ): View {
        $data = $request->validate([
            'activity_id' => ['required', 'exists:wariga_activities,id'],
            'date' => ['required', 'date'],
        ]);
        abort_unless($evaluation->hasDefinitions((int) $data['activity_id']), 422, 'Basis pengetahuan kegiatan belum tersedia.');
        $date = Carbon::parse($data['date']);
        $calendar = $calendarService->calculate($date)->toArray();
        $lunar = $lunarProvider->getForDate($date)->toArray();
        $activity = WarigaActivity::findOrFail($data['activity_id']);

        return view('admin.expert.simulator', [
            'activities' => WarigaActivity::where('is_active', true)
                ->whereExists(fn ($query) => $query->selectRaw('1')
                    ->from('ala_ayu_activity_rules')
                    ->whereColumn('ala_ayu_activity_rules.activity_id', 'wariga_activities.id'))
                ->orderBy('nama')->get(),
            'simulation' => [
                'activity' => $activity,
                'date' => $date->toDateString(),
                'calendar' => $calendar,
                'lunar' => $lunar,
                'result' => $evaluation->evaluate((int) $data['activity_id'], $calendar, $lunar, true),
            ],
        ]);
    }

    public function storeMapping(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('admin_data'), 403);
        $data = $request->validate([
            'activity_id' => ['required', 'exists:wariga_activities,id'],
            'ala_ayu_master_id' => ['required', 'exists:ala_ayu_masters,id'],
            'effect' => ['required', Rule::in(['positive', 'negative'])],
            'alahing_priority' => ['nullable', 'integer', 'between:1,4'],
            'source_url' => ['nullable', 'url', 'max:2000'],
        ]);
        $data['source_url'] ??= 'https://kalenderbali.org/pilihdewasa.php';
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('ala_ayu_activity_rules')->updateOrInsert(
            [
                'activity_id' => $data['activity_id'],
                'ala_ayu_master_id' => $data['ala_ayu_master_id'],
                'effect' => $data['effect'],
            ],
            $data,
        );
        $audit->record('knowledge.mapping_saved', 'ala_ayu_activity_rules', null, $data);

        return back()->with('success', 'Pemetaan pengetahuan disimpan. Perubahan dapat diuji sebelum digunakan.');
    }

    public function destroyMapping(Request $request, int $mapping, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('admin_data'), 403);
        $old = (array) DB::table('ala_ayu_activity_rules')->where('id', $mapping)->first();
        abort_if($old === [], 404);
        DB::table('ala_ayu_activity_rules')->where('id', $mapping)->delete();
        $audit->record('knowledge.mapping_deleted', 'ala_ayu_activity_rules', $old, null, $mapping);

        return back()->with('success', 'Pemetaan dihapus.');
    }

    public function storeCase(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'activity_id' => ['required', 'exists:wariga_activities,id'],
            'case_date' => ['required', 'date'],
            'expected_status' => ['required', Rule::in(['recommended', 'consideration', 'avoid'])],
            'predicted_status' => ['required', Rule::in(['recommended', 'consideration', 'avoid'])],
            'predicted_score' => ['nullable', 'integer', 'between:0,100'],
            'source_name' => ['required', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'validator_note' => ['nullable', 'max:5000'],
        ]);
        $data += ['created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()];
        DB::table('expert_validation_cases')->updateOrInsert(
            [
                'activity_id' => $data['activity_id'],
                'case_date' => $data['case_date'],
                'source_name' => $data['source_name'],
            ],
            $data,
        );
        $audit->record('learning.case_saved', 'expert_validation_cases', null, $data);

        return back()->with('success', 'Kasus pembanding disimpan sebagai bahan belajar mesin.');
    }

    public function generate(Request $request, ExpertLearningService $learning): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('validator_wariga'), 403);
        $count = $learning->generateProposals();

        return back()->with('success', "{$count} kelompok usulan pembelajaran diperbarui.");
    }

    public function transitionProposal(Request $request, int $proposal, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasAdminRole('validator_wariga'), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['review', 'approved', 'rejected'])],
            'review_note' => ['required', 'max:5000'],
        ]);
        $old = (array) DB::table('expert_learning_proposals')->where('id', $proposal)->first();
        abort_if($old === [], 404);
        DB::table('expert_learning_proposals')->where('id', $proposal)->update($data + [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);
        $audit->record('learning.proposal_'.$data['status'], 'expert_learning_proposals', $old, $data, $proposal);

        return back()->with('success', 'Status usulan diperbarui. Persetujuan tidak mengubah rule secara otomatis.');
    }
}
