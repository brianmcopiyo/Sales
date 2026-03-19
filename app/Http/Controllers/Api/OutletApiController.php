<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;

class OutletApiController extends Controller
{
    /**
     * List outlets for the mapping app (all or filtered by branch).
     */
    public function index(Request $request)
    {
        $query = Outlet::orderBy('name');

        if ($request->user()->branch_id) {
            $query->where(function ($q) use ($request) {
                $q->where('branch_id', $request->user()->branch_id)
                    ->orWhere('assigned_to', $request->user()->id);
            });
        }

        $outlets = $query->get([
            'id', 'name', 'code', 'address', 'lat', 'lng',
            'geo_fence_type', 'geo_fence_radius_metres', 'geo_fence_polygon',
            'branch_id', 'is_active',
        ]);

        return response()->json(['outlets' => $outlets]);
    }

    /**
     * Single outlet for editing on the map.
     */
    public function show(Request $request, string $id)
    {
        $outlet = Outlet::findOrFail($id);
        $user = $request->user();
        if ($user->branch_id && $outlet->branch_id !== $user->branch_id && $outlet->assigned_to !== $user->id) {
            abort(403);
        }
        return response()->json(['outlet' => $outlet]);
    }

    /**
     * Create outlet (mapping app: set location on map, name, optional radius).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:outlets,code',
            'type' => 'nullable|string|in:retail,kiosk,dealer,wholesale,other',
            'address' => 'nullable|string',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
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

        $validated = $this->normalizeGeoFence($request, $validated);
        if ($validated === null) {
            return response()->json(['message' => 'Invalid geo_fence_polygon (need 4+ points with lat/lng).'], 422);
        }

        // Mobile app create flow does not send branch/assignee; align with list visibility
        // by defaulting new outlets to the current user's branch and user assignment.
        if (!array_key_exists('branch_id', $validated) || empty($validated['branch_id'])) {
            $validated['branch_id'] = $user?->branch_id;
        }
        if (!array_key_exists('assigned_to', $validated) || empty($validated['assigned_to'])) {
            $validated['assigned_to'] = $user?->id;
        }

        $outlet = Outlet::create($validated);
        return response()->json(['outlet' => $outlet], 201);
    }

    /**
     * Update outlet (mapping app: update location, radius, etc.).
     */
    public function update(Request $request, string $id)
    {
        $outlet = Outlet::findOrFail($id);
        $user = $request->user();
        if ($user->branch_id && $outlet->branch_id !== $user->branch_id && $outlet->assigned_to !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:outlets,code,' . $id,
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

        $validated = $this->normalizeGeoFence($request, $validated);
        if ($validated === null) {
            return response()->json(['message' => 'Invalid geo_fence_polygon (need 4+ points with lat/lng).'], 422);
        }

        $outlet->update($validated);
        return response()->json(['outlet' => $outlet]);
    }

    private function normalizeGeoFence(Request $request, array $validated): ?array
    {
        $type = $validated['geo_fence_type'] ?? null;
        if (empty($type)) {
            $validated['geo_fence_radius_metres'] = null;
            $validated['geo_fence_polygon'] = null;
            return $validated;
        }
        if ($type === 'radius') {
            $validated['geo_fence_polygon'] = null;
            return $validated;
        }
        $validated['geo_fence_radius_metres'] = null;
        $input = $request->input('geo_fence_polygon');
        if (is_array($input)) {
            $input = json_encode($input);
        }
        $polygon = $this->parseAndValidatePolygon($input);
        if ($polygon === false) {
            return null;
        }
        $validated['geo_fence_polygon'] = $polygon;
        return $validated;
    }

    private function parseAndValidatePolygon(?string $input): array|false
    {
        if ($input === null || trim((string) $input) === '') {
            return false;
        }
        $decoded = json_decode(trim((string) $input), true);
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
