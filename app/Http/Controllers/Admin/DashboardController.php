<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CalendarLunarDate;
use App\Models\WarigaActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'Kegiatan aktif' => WarigaActivity::where('is_active', true)->count(),
                'Master Ala Ayu' => DB::table('ala_ayu_masters')->count(),
                'Pemetaan pengetahuan' => DB::table('ala_ayu_activity_rules')->count(),
                'Kasus pembelajaran' => DB::table('expert_validation_cases')->count(),
                'Usulan perlu ditinjau' => DB::table('expert_learning_proposals')->whereIn('status', ['suggested', 'review'])->count(),
                'Usulan disetujui' => DB::table('expert_learning_proposals')->where('status', 'approved')->count(),
                'Lunar draft' => CalendarLunarDate::where('verification_status', 'draft')->count(),
                'Lunar verified' => CalendarLunarDate::where('verification_status', 'verified')->count(),
            ],
            'recentChanges' => AuditLog::with('user')->latest('created_at')->limit(8)->get(),
            'recentValidations' => DB::table('expert_learning_proposals as proposal')
                ->join('wariga_activities as activity', 'activity.id', '=', 'proposal.activity_id')
                ->leftJoin('users as reviewer', 'reviewer.id', '=', 'proposal.reviewed_by')
                ->whereNotNull('proposal.reviewed_at')->latest('proposal.reviewed_at')->limit(8)
                ->get(['proposal.title', 'proposal.status', 'activity.nama as activity_name', 'reviewer.name as reviewer_name']),
        ]);
    }
}
