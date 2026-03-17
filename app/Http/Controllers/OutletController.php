<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Branch;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function index(Request $request)
    {
        $query = Outlet::with(['branch', 'region', 'assignedUser']);

        if ($request->filled('search')) {
            $term = $request->get('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhere('contact_name', 'like', "%{$term}%");
            });
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->get('region_id'));
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->get('assigned_to'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }
        if ($request->filled('status')) {
            if ($request->get('status') === 'active') {
                $query->where('is_active', true);
            }
            if ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $outlets = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Outlet::count(),
            'active' => Outlet::where('is_active', true)->count(),
            'inactive' => Outlet::where('is_active', false)->count(),
        ];

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $regions = Region::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('outlets.index', compact('outlets', 'stats', 'branches', 'regions', 'users'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $regions = Region::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name']);
        return view('outlets.create', compact('branches', 'regions', 'users'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'geo_fence_type' => $request->input('geo_fence_type') ?: null,
        ]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:outlets,code',
            'type' => 'nullable|string|in:retail,kiosk,dealer,wholesale,other',
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'region_id' => 'nullable|exists:regions,id',
            'assigned_to' => 'nullable|exists:users,id',
            'geo_fence_type' => 'nullable|string|in:radius,polygon',
            'geo_fence_radius_metres' => 'nullable|integer|min:10|max:5000',
            'geo_fence_polygon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['geo_fence_type'])) {
            $validated['geo_fence_radius_metres'] = null;
            $validated['geo_fence_polygon'] = null;
        } elseif ($validated['geo_fence_type'] === 'radius') {
            $validated['geo_fence_polygon'] = null;
        } else {
            $validated['geo_fence_radius_metres'] = null;
            $validated['geo_fence_polygon'] = $this->parseAndValidatePolygon($request->input('geo_fence_polygon'));
            if ($validated['geo_fence_polygon'] === false) {
                return redirect()->back()->withInput()->withErrors(['geo_fence_polygon' => 'Polygon must be a JSON array of at least 4 points with lat and lng (e.g. [{"lat":-6.16,"lng":35.75},...]).']);
            }
        }

        Outlet::create($validated);

        return redirect()->route('outlets.index')->with('success', 'Outlet created successfully.');
    }

    public function show(Outlet $outlet)
    {
        $outlet->load(['branch', 'region', 'assignedUser']);
        $outlet->loadCount('checkIns');
        return view('outlets.show', compact('outlet'));
    }

    public function edit(Outlet $outlet)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $regions = Region::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name']);
        return view('outlets.edit', compact('outlet', 'branches', 'regions', 'users'));
    }

    public function update(Request $request, Outlet $outlet)
    {
        $request->merge([
            'geo_fence_type' => $request->input('geo_fence_type') ?: null,
        ]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:outlets,code,' . $outlet->id,
            'type' => 'nullable|string|in:retail,kiosk,dealer,wholesale,other',
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'region_id' => 'nullable|exists:regions,id',
            'assigned_to' => 'nullable|exists:users,id',
            'geo_fence_type' => 'nullable|string|in:radius,polygon',
            'geo_fence_radius_metres' => 'nullable|integer|min:10|max:5000',
            'geo_fence_polygon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['geo_fence_type'])) {
            $validated['geo_fence_radius_metres'] = null;
            $validated['geo_fence_polygon'] = null;
        } elseif ($validated['geo_fence_type'] === 'radius') {
            $validated['geo_fence_polygon'] = null;
        } else {
            $validated['geo_fence_radius_metres'] = null;
            $validated['geo_fence_polygon'] = $this->parseAndValidatePolygon($request->input('geo_fence_polygon'));
            if ($validated['geo_fence_polygon'] === false) {
                return redirect()->back()->withInput()->withErrors(['geo_fence_polygon' => 'Polygon must be a JSON array of at least 4 points with lat and lng (e.g. [{"lat":-6.16,"lng":35.75},...]).']);
            }
        }

        $outlet->update($validated);

        return redirect()->route('outlets.index')->with('success', 'Outlet updated successfully.');
    }

    public function destroy(Outlet $outlet)
    {
        $outlet->delete();
        return redirect()->route('outlets.index')->with('success', 'Outlet deleted successfully.');
    }

    /**
     * Parse and validate geo_fence_polygon JSON. Returns array of {lat, lng} (4+ points) or false.
     * Accepts closed or open polygon; we store as-is (GeoFenceService handles both).
     */
    private function parseAndValidatePolygon(?string $input): array|false
    {
        if ($input === null || trim($input) === '') {
            return false;
        }
        $decoded = json_decode(trim($input), true);
        if (!is_array($decoded) || count($decoded) < 4) {
            return false;
        }
        $normalized = [];
        foreach ($decoded as $point) {
            if (!is_array($point)) {
                return false;
            }
            $lat = $point['lat'] ?? $point[0] ?? null;
            $lng = $point['lng'] ?? $point[1] ?? null;
            if ($lat === null || $lng === null || !is_numeric($lat) || !is_numeric($lng)) {
                return false;
            }
            $normalized[] = ['lat' => (float) $lat, 'lng' => (float) $lng];
        }
        return $normalized;
    }
}
