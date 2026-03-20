<?php

namespace App\Http\Controllers;

use App\Models\AuditRun;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;

class AuditReportController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditRun::with(['checkIn.outlet', 'checkIn.user', 'template'])
            ->whereNotNull('completed_at');

        if ($request->filled('outlet_id')) {
            $query->whereHas('checkIn', fn ($q) => $q->where('outlet_id', $request->get('outlet_id')));
        }
        if ($request->filled('user_id')) {
            $query->whereHas('checkIn', fn ($q) => $q->where('user_id', $request->get('user_id')));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('completed_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('completed_at', '<=', $request->get('date_to'));
        }
        if ($request->filled('template_id')) {
            $query->where('audit_template_id', $request->get('template_id'));
        }
        if ($request->filled('category')) {
            $query->whereHas('template', fn ($q) => $q->where('category', $request->get('category')));
        }

        $runs = $query->latest('completed_at')->paginate(20)->withQueryString();

        $outlets = Outlet::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $reps = User::orderBy('name')->get(['id', 'name']);
        $templates = \App\Models\AuditTemplate::where('is_active', true)->orderBy('name')->get(['id', 'name', 'category']);
        $categories = \App\Models\AuditTemplate::categories();

        $statsQuery = clone $query;
        $stats = [
            'avg_compliance' => round((float) $statsQuery->avg('compliance_score'), 1),
            'total_runs' => $statsQuery->count(),
        ];

        return view('audit-reports.index', compact('runs', 'outlets', 'reps', 'templates', 'stats', 'categories'));
    }
}
