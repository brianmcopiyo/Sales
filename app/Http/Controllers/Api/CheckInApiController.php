<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Outlet;
use App\Services\GeoFenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CheckInApiController extends Controller
{
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
            return response()->json(['message' => $errorMessage ?? 'Location outside geo-fence.'], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('check-ins', 'public');
        }

        $checkIn = CheckIn::create([
            'user_id' => $request->user()->id,
            'outlet_id' => $outlet->id,
            'check_in_at' => now(),
            'lat_in' => $validated['lat'],
            'lng_in' => $validated['lng'],
            'photo_path' => $photoPath,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Check-in recorded.',
            'check_in' => [
                'id' => $checkIn->id,
                'outlet_id' => $checkIn->outlet_id,
                'check_in_at' => $checkIn->check_in_at->toIso8601String(),
            ],
        ], 201);
    }
}
