<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlannedVisit;
use Illuminate\Http\Request;

class PlannedVisitApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = PlannedVisit::query()->with(['outlet:id,name,code,address,lat,lng', 'user:id,name']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->string('user_id'));
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('planned_date')) {
            $query->whereDate('planned_date', $request->string('planned_date'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('planned_date', '>=', $request->string('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('planned_date', '<=', $request->string('date_to'));
        }

        $visits = $query
            ->orderBy('planned_date')
            ->orderBy('sequence')
            ->get()
            ->map(function (PlannedVisit $visit) {
                return [
                    'id' => $visit->id,
                    'user_id' => $visit->user_id,
                    'user_name' => $visit->user?->name,
                    'outlet_id' => $visit->outlet_id,
                    'planned_date' => optional($visit->planned_date)->toDateString(),
                    'sequence' => $visit->sequence,
                    'notes' => $visit->notes,
                    'outlet' => $visit->outlet ? [
                        'id' => $visit->outlet->id,
                        'name' => $visit->outlet->name,
                        'code' => $visit->outlet->code,
                        'address' => $visit->outlet->address,
                        'lat' => $visit->outlet->lat,
                        'lng' => $visit->outlet->lng,
                    ] : null,
                ];
            });

        return response()->json(['planned_visits' => $visits]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'planned_date' => 'required|date',
            'outlet_ids' => 'required|array|min:1',
            'outlet_ids.*' => 'exists:outlets,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $userId = $validated['user_id'] ?? $request->user()->id;
        $plannedDate = date('Y-m-d', strtotime((string) $validated['planned_date']));
        $outletIds = array_values(array_unique($validated['outlet_ids']));
        $notes = $validated['notes'] ?? null;

        $existing = PlannedVisit::where('user_id', $userId)
            ->whereDate('planned_date', $plannedDate)
            ->pluck('outlet_id')
            ->all();
        $toAdd = array_values(array_diff($outletIds, $existing));

        $created = [];
        foreach ($toAdd as $idx => $outletId) {
            $created[] = PlannedVisit::create([
                'user_id' => $userId,
                'outlet_id' => $outletId,
                'planned_date' => $plannedDate,
                'sequence' => $idx + 1,
                'notes' => $idx === 0 ? $notes : null,
            ]);
        }

        return response()->json([
            'message' => count($created) . ' planned visit(s) created.',
            'planned_visits' => collect($created)->map(fn (PlannedVisit $v) => [
                'id' => $v->id,
                'user_id' => $v->user_id,
                'outlet_id' => $v->outlet_id,
                'planned_date' => optional($v->planned_date)->toDateString(),
                'sequence' => $v->sequence,
                'notes' => $v->notes,
            ]),
        ], 201);
    }

    public function destroy(PlannedVisit $plannedVisit)
    {
        $plannedVisit->delete();
        return response()->json(['message' => 'Planned visit removed.']);
    }
}
