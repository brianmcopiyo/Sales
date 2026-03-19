<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\PlannedVisit;
use Illuminate\Http\Request;

class DcrApiController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'date' => 'nullable|date',
        ]);

        $userId = $validated['user_id'] ?? $request->user()->id;
        $date = $validated['date'] ?? now()->toDateString();

        $planned = PlannedVisit::with('outlet:id,name,code')
            ->where('user_id', $userId)
            ->whereDate('planned_date', $date)
            ->orderBy('sequence')
            ->get();

        $checkIns = CheckIn::with('outlet:id,name,code')
            ->where('user_id', $userId)
            ->whereDate('check_in_at', $date)
            ->orderBy('check_in_at')
            ->get();

        $plannedOutletIds = $planned->pluck('outlet_id')->all();
        $checkedOutletIds = $checkIns->pluck('outlet_id')->all();

        $rows = [];
        foreach ($planned as $pv) {
            $ci = $checkIns->firstWhere('outlet_id', $pv->outlet_id);
            $rows[] = [
                'type' => 'planned',
                'outlet_id' => $pv->outlet_id,
                'outlet_name' => $pv->outlet?->name,
                'outlet_code' => $pv->outlet?->code,
                'sequence' => $pv->sequence,
                'planned' => true,
                'checked_in' => (bool) $ci,
                'check_in_at' => $ci?->check_in_at?->toIso8601String(),
                'check_out_at' => $ci?->check_out_at?->toIso8601String(),
            ];
        }

        foreach ($checkIns as $ci) {
            if (in_array($ci->outlet_id, $plannedOutletIds, true)) {
                continue;
            }
            $rows[] = [
                'type' => 'unplanned',
                'outlet_id' => $ci->outlet_id,
                'outlet_name' => $ci->outlet?->name,
                'outlet_code' => $ci->outlet?->code,
                'sequence' => null,
                'planned' => false,
                'checked_in' => true,
                'check_in_at' => $ci->check_in_at?->toIso8601String(),
                'check_out_at' => $ci->check_out_at?->toIso8601String(),
            ];
        }

        return response()->json([
            'user_id' => $userId,
            'date' => $date,
            'total_planned' => $planned->count(),
            'total_checked_in' => $checkIns->count(),
            'completed_planned' => count(array_intersect($plannedOutletIds, $checkedOutletIds)),
            'rows' => $rows,
        ]);
    }
}
