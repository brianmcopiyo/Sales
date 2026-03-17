<?php

namespace App\Http\Controllers;

use App\Models\PlannedVisit;
use App\Models\CheckIn;
use App\Models\User;
use App\Exports\DcrExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class DcrController extends Controller
{
    /**
     * DCR report: by user + date, planned vs actual (from check_ins).
     */
    public function index(Request $request)
    {
        $userId = $request->get('user_id');
        $date = $request->get('date', today()->format('Y-m-d'));

        $users = User::orderBy('name')->get(['id', 'name']);
        $report = null;

        if ($userId && $date) {
            $user = User::find($userId);
            $dateParsed = Carbon::parse($date)->toDateString();

            $planned = PlannedVisit::with('outlet')
                ->where('user_id', $userId)
                ->whereDate('planned_date', $dateParsed)
                ->orderBy('sequence')
                ->get();

            $checkIns = CheckIn::with('outlet')
                ->where('user_id', $userId)
                ->whereDate('check_in_at', $dateParsed)
                ->orderBy('check_in_at')
                ->get();

            $plannedOutletIds = $planned->pluck('outlet_id')->all();
            $checkedOutletIds = $checkIns->pluck('outlet_id')->all();

            $rows = [];
            foreach ($planned as $pv) {
                $ci = $checkIns->firstWhere('outlet_id', $pv->outlet_id);
                $rows[] = [
                    'type' => 'planned',
                    'outlet' => $pv->outlet,
                    'sequence' => $pv->sequence,
                    'planned' => true,
                    'checked_in' => (bool) $ci,
                    'check_in_at' => $ci?->check_in_at,
                    'check_out_at' => $ci?->check_out_at,
                ];
            }
            foreach ($checkIns as $ci) {
                if (in_array($ci->outlet_id, $plannedOutletIds, true)) {
                    continue;
                }
                $rows[] = [
                    'type' => 'unplanned',
                    'outlet' => $ci->outlet,
                    'sequence' => null,
                    'planned' => false,
                    'checked_in' => true,
                    'check_in_at' => $ci->check_in_at,
                    'check_out_at' => $ci->check_out_at,
                ];
            }

            $report = [
                'user' => $user,
                'date' => $dateParsed,
                'rows' => $rows,
                'total_planned' => $planned->count(),
                'total_checked_in' => $checkIns->count(),
                'completed_planned' => count(array_intersect($plannedOutletIds, $checkedOutletIds)),
            ];
        }

        return view('dcr.index', compact('users', 'report', 'userId', 'date'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        $filename = 'dcr-' . $request->get('date') . '-user-' . $request->get('user_id') . '.xlsx';
        return Excel::download(new DcrExport($request->get('user_id'), $request->get('date')), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }
}
