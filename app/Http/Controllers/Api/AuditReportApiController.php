<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditRun;
use Illuminate\Http\Request;

class AuditReportApiController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditRun::query()
            ->with(['checkIn.outlet:id,name,code', 'checkIn.user:id,name', 'template:id,name'])
            ->whereNotNull('completed_at');

        if ($request->filled('outlet_id')) {
            $query->whereHas('checkIn', fn ($q) => $q->where('outlet_id', $request->string('outlet_id')));
        }
        if ($request->filled('user_id')) {
            $query->whereHas('checkIn', fn ($q) => $q->where('user_id', $request->string('user_id')));
        } else {
            $query->whereHas('checkIn', fn ($q) => $q->where('user_id', $request->user()->id));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('completed_at', '>=', $request->string('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('completed_at', '<=', $request->string('date_to'));
        }
        if ($request->filled('template_id')) {
            $query->where('audit_template_id', $request->string('template_id'));
        }

        $runs = $query->latest('completed_at')->get();

        return response()->json([
            'stats' => [
                'total_runs' => $runs->count(),
                'avg_compliance' => round((float) $runs->avg('compliance_score'), 1),
            ],
            'runs' => $runs->map(function (AuditRun $run) {
                return [
                    'id' => $run->id,
                    'check_in_id' => $run->check_in_id,
                    'completed_at' => $run->completed_at?->toIso8601String(),
                    'compliance_score' => $run->compliance_score,
                    'template' => $run->template ? [
                        'id' => $run->template->id,
                        'name' => $run->template->name,
                    ] : null,
                    'outlet' => $run->checkIn?->outlet ? [
                        'id' => $run->checkIn->outlet->id,
                        'name' => $run->checkIn->outlet->name,
                        'code' => $run->checkIn->outlet->code,
                    ] : null,
                    'user' => $run->checkIn?->user ? [
                        'id' => $run->checkIn->user->id,
                        'name' => $run->checkIn->user->name,
                    ] : null,
                ];
            }),
        ]);
    }
}
