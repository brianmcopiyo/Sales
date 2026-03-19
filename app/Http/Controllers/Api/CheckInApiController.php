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

    public function checkOut(Request $request, string $id, GeoFenceService $geoFence)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string|max:2000',
        ]);

        $checkIn = CheckIn::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('outlet')
            ->firstOrFail();

        if ($checkIn->check_out_at) {
            return response()->json(['message' => 'Check-out already recorded.'], 422);
        }

        [$allowed, $errorMessage] = $geoFence->validatePointForOutlet(
            (float) $validated['lat'],
            (float) $validated['lng'],
            $checkIn->outlet->geo_fence_type,
            $checkIn->outlet->lat ? (float) $checkIn->outlet->lat : null,
            $checkIn->outlet->lng ? (float) $checkIn->outlet->lng : null,
            $checkIn->outlet->geo_fence_radius_metres,
            $checkIn->outlet->geo_fence_polygon
        );

        if (!$allowed) {
            return response()->json(['message' => $errorMessage ?? 'Location outside geo-fence.'], 422);
        }

        $checkIn->update([
            'check_out_at' => now(),
            'lat_out' => $validated['lat'],
            'lng_out' => $validated['lng'],
            'notes' => $validated['notes'] ?? $checkIn->notes,
        ]);

        return response()->json([
            'message' => 'Check-out recorded.',
            'check_in' => [
                'id' => $checkIn->id,
                'outlet_id' => $checkIn->outlet_id,
                'check_in_at' => $checkIn->check_in_at?->toIso8601String(),
                'check_out_at' => $checkIn->check_out_at?->toIso8601String(),
            ],
        ]);
    }
}
