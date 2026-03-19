<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\Outlet;
use App\Models\User;
use App\Services\GeoFenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CheckInController extends Controller
{
    public function index(Request $request)
    {
        $query = CheckIn::with(['user', 'outlet', 'auditRuns']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }
        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->get('outlet_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('check_in_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('check_in_at', '<=', $request->get('date_to'));
        }

        $checkIns = $query->latest('check_in_at')->paginate(20)->withQueryString();

        $currentUser = Auth::user();
        $users = User::visibleTo($currentUser)->orderBy('name')->get(['id', 'name']);
        $outlets = Outlet::orderBy('name')->get(['id', 'name', 'code']);

        return view('check-ins.index', compact('checkIns', 'users', 'outlets'));
    }

    /**
     * Show the check-in form (select outlet, then submit with GPS/photo/note).
     */
    public function create(Request $request)
    {
        $outlets = Outlet::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'address', 'lat', 'lng', 'geo_fence_type', 'geo_fence_radius_metres', 'geo_fence_polygon']);
        return view('check-ins.create', compact('outlets'));
    }

    /**
     * Store a new check-in. Validates geo-fence if outlet has one.
     */
    public function store(Request $request, GeoFenceService $geoFence)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'photo' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:2000',
        ]);

        $outlet = Outlet::findOrFail($validated['outlet_id']);

        [$allowed, $errorMessage] = $geoFence->validatePointForOutlet(
            (float) $validated['lat'],
            (float) $validated['lng'],
            $outlet->geo_fence_type,
            $outlet->lat ? (float) $outlet->lat : null,
            $outlet->lng ? (float) $outlet->lng : null,
            $outlet->geo_fence_radius_metres,
            $outlet->geo_fence_polygon
        );

        if (!$allowed) {
            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('check-ins', 'public');
        }

        CheckIn::create([
            'user_id' => $request->user()->id,
            'outlet_id' => $outlet->id,
            'check_in_at' => now(),
            'lat_in' => $validated['lat'],
            'lng_in' => $validated['lng'],
            'photo_path' => $photoPath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('check-ins.index')->with('success', 'Check-in recorded successfully at ' . $outlet->name . '.');
    }
}
