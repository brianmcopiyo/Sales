<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Outlet;
use App\Services\GeoFenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class SyncApiController extends Controller
{
    /**
     * Sync pending check-ins from offline queue. Body: { "items": [ { "client_id", "outlet_id", "lat", "lng", "notes", "photo_base64"?, "check_in_at" } ] }
     * Returns { "synced": [ { "client_id", "server_id" } ], "failed": [ { "client_id", "message" } ] }
     */
    public function syncCheckIns(Request $request, GeoFenceService $geoFence)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.client_id' => 'required|string|max:64',
            'items.*.outlet_id' => 'required|exists:outlets,id',
            'items.*.lat' => 'required|numeric|between:-90,90',
            'items.*.lng' => 'required|numeric|between:-180,180',
            'items.*.notes' => 'nullable|string|max:2000',
            'items.*.photo_base64' => 'nullable|string',
            'items.*.check_in_at' => 'required|date',
        ]);

        $synced = [];
        $failed = [];

        foreach ($request->input('items') as $item) {
            $clientId = $item['client_id'];

            // Idempotent sync: if this client payload was already processed, return existing mapping.
            if (Schema::hasColumn('check_ins', 'client_ref')) {
                $existing = CheckIn::query()
                    ->where('user_id', $request->user()->id)
                    ->where('client_ref', $clientId)
                    ->first();
                if ($existing) {
                    $synced[] = ['client_id' => $clientId, 'server_id' => $existing->id];
                    continue;
                }
            }

            $outlet = Outlet::find($item['outlet_id']);
            if (!$outlet) {
                $failed[] = ['client_id' => $clientId, 'message' => 'Outlet not found.'];
                continue;
            }

            [$allowed, $errorMessage] = $geoFence->validatePointForOutlet(
                (float) $item['lat'],
                (float) $item['lng'],
                $outlet->geo_fence_type,
                $outlet->lat ? (float) $outlet->lat : null,
                $outlet->lng ? (float) $outlet->lng : null,
                $outlet->geo_fence_radius_metres,
                $outlet->geo_fence_polygon
            );

            if (!$allowed) {
                $failed[] = ['client_id' => $clientId, 'message' => $errorMessage ?? 'Outside geo-fence.'];
                continue;
            }

            $photoPath = null;
            if (!empty($item['photo_base64'])) {
                $decoded = base64_decode($item['photo_base64'], true);
                if ($decoded !== false && strlen($decoded) < 6 * 1024 * 1024) {
                    $filename = 'check-ins/' . uniqid('sync_', true) . '.jpg';
                    Storage::disk('public')->put($filename, $decoded);
                    $photoPath = $filename;
                }
            }

            $checkIn = CheckIn::create([
                'user_id' => $request->user()->id,
                'outlet_id' => $outlet->id,
                'check_in_at' => $item['check_in_at'],
                'lat_in' => $item['lat'],
                'lng_in' => $item['lng'],
                'photo_path' => $photoPath,
                'notes' => $item['notes'] ?? null,
                'client_ref' => Schema::hasColumn('check_ins', 'client_ref') ? $clientId : null,
            ]);

            $synced[] = ['client_id' => $clientId, 'server_id' => $checkIn->id];
        }

        return response()->json(compact('synced', 'failed'));
    }
}
