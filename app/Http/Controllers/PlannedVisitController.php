<?php

namespace App\Http\Controllers;

use App\Models\PlannedVisit;
use App\Models\Outlet;
use App\Models\User;
use App\Services\BeatOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlannedVisitController extends Controller
{
    public function __construct(protected BeatOptimizerService $optimizer) {}

    public function index(Request $request)
    {
        $query = PlannedVisit::with(['user', 'outlet']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }
        if ($request->filled('planned_date')) {
            $query->whereDate('planned_date', $request->get('planned_date'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('planned_date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('planned_date', '<=', $request->get('date_to'));
        }

        $plannedVisits = $query->orderBy('planned_date')->orderBy('user_id')->orderBy('sequence')->paginate(20)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('planned-visits.index', compact('plannedVisits', 'users'));
    }

    public function create(Request $request)
    {
        $users = User::orderBy('name')->get(['id', 'name']);
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $defaultDate = $request->get('date', today()->format('Y-m-d'));
        $defaultUserId = $request->get('user_id');

        return view('planned-visits.create', compact('users', 'outlets', 'defaultDate', 'defaultUserId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'planned_date' => 'required|date',
            'outlet_ids' => 'required|array',
            'outlet_ids.*' => 'exists:outlets,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $userId = $validated['user_id'];
        $plannedDate = Carbon::parse($validated['planned_date'])->toDateString();
        $outletIds = array_values(array_unique($validated['outlet_ids']));
        $notes = $validated['notes'] ?? null;

        $existing = PlannedVisit::where('user_id', $userId)->whereDate('planned_date', $plannedDate)->pluck('outlet_id')->all();
        $toAdd = array_diff($outletIds, $existing);

        foreach ($toAdd as $sequence => $outletId) {
            PlannedVisit::create([
                'user_id' => $userId,
                'outlet_id' => $outletId,
                'planned_date' => $plannedDate,
                'sequence' => $sequence + 1,
                'notes' => $sequence === 0 ? $notes : null,
            ]);
        }

        $message = count($toAdd) > 0
            ? count($toAdd) . ' visit(s) planned for ' . $plannedDate . '.'
            : 'No new visits added (outlets already planned for that day).';

        return redirect()->route('planned-visits.index', ['user_id' => $userId, 'planned_date' => $plannedDate])->with('success', $message);
    }

    public function optimize(Request $request, User $user, string $date)
    {
        $plannedDate = Carbon::parse($date)->toDateString();

        $visits = PlannedVisit::where('user_id', $user->id)
            ->whereDate('planned_date', $plannedDate)
            ->with('outlet')
            ->orderBy('sequence')
            ->get();

        if ($visits->isEmpty()) {
            return redirect()
                ->route('planned-visits.index', ['user_id' => $user->id, 'planned_date' => $plannedDate])
                ->with('error', 'No planned visits found for this user on that date.');
        }

        $result = $this->optimizer->optimize($visits);
        $reordered = $result['visits'];

        DB::transaction(function () use ($reordered) {
            foreach ($reordered->values() as $index => $visit) {
                $visit->update(['sequence' => $index + 1]);
            }
        });

        $km = number_format($result['total_distance_metres'] / 1000, 1);

        return redirect()
            ->route('planned-visits.index', ['user_id' => $user->id, 'planned_date' => $plannedDate])
            ->with('success', "Route optimized. Estimated distance: {$km} km.");
    }

    public function destroy(PlannedVisit $plannedVisit)
    {
        $plannedVisit->delete();
        return redirect()->back()->with('success', 'Planned visit removed.');
    }
}
