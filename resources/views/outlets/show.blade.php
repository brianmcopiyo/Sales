@extends('layouts.app')

@section('title', $outlet->name)

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('outlets.index'), 'label' => 'Back to Outlets'])
        <div class="flex flex-wrap justify-between items-start gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $outlet->name }}</h1>
                <p class="text-themeMuted mt-1">{{ $outlet->code ?? '—' }} · {{ $outlet->type ? (\App\Models\Outlet::types()[$outlet->type] ?? $outlet->type) : '—' }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasPermission('checkins.create'))
                    <a href="{{ route('check-ins.create') }}?outlet_id={{ $outlet->id }}"
                        class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Check in here
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('outlets.manage'))
                    <a href="{{ route('outlets.edit', $outlet) }}"
                        class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        Edit
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-themeHeading mb-4">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-themeMuted font-medium">Address</dt><dd class="text-themeBody">{{ $outlet->address ?? '—' }}</dd></div>
                    <div><dt class="text-themeMuted font-medium">Location</dt><dd class="text-themeBody">{{ $outlet->lat && $outlet->lng ? $outlet->lat . ', ' . $outlet->lng : '—' }}</dd></div>
                    <div><dt class="text-themeMuted font-medium">Contact</dt><dd class="text-themeBody">{{ $outlet->contact_name ?? '—' }} {{ $outlet->contact_phone ? ' · ' . $outlet->contact_phone : '' }} {{ $outlet->contact_email ? ' · ' . $outlet->contact_email : '' }}</dd></div>
                    <div><dt class="text-themeMuted font-medium">Branch</dt><dd class="text-themeBody">{{ $outlet->branch?->name ?? '—' }}</dd></div>
                    <div><dt class="text-themeMuted font-medium">Region</dt><dd class="text-themeBody">{{ $outlet->region?->name ?? '—' }}</dd></div>
                    <div><dt class="text-themeMuted font-medium">Assigned to</dt><dd class="text-themeBody">{{ $outlet->assignedUser?->name ?? '—' }}</dd></div>
                    <div><dt class="text-themeMuted font-medium">Geo-fence</dt>
                        <dd class="text-themeBody">
                            @if ($outlet->geo_fence_type === 'radius')
                                Radius {{ $outlet->geo_fence_radius_metres }} m
                            @elseif ($outlet->geo_fence_type === 'polygon')
                                Polygon
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div><dt class="text-themeMuted font-medium">Status</dt><dd><span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $outlet->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">{{ $outlet->is_active ? 'Active' : 'Inactive' }}</span></dd></div>
                </dl>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-themeHeading mb-4">Check-ins</h2>
                <p class="text-themeBody">Total check-ins at this outlet: <strong>{{ $outlet->check_ins_count ?? 0 }}</strong></p>
                @if (auth()->user()?->hasPermission('checkins.view'))
                    <a href="{{ route('check-ins.index', ['outlet_id' => $outlet->id]) }}" class="mt-3 inline-flex text-primary font-medium hover:underline">View check-in history →</a>
                @endif
            </div>
        </div>

        @if ($outlet->lat !== null && $outlet->lng !== null)
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-themeHeading mb-4">Map</h2>
            <div id="outlet-map" class="w-full rounded-xl overflow-hidden border border-themeBorder" style="height: 360px;"></div>
        </div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function () {
                var lat = {{ $outlet->lat }};
                var lng = {{ $outlet->lng }};
                var map = L.map('outlet-map').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map);
                L.marker([lat, lng]).addTo(map).bindPopup('<strong>{{ addslashes($outlet->name) }}</strong>');
                @if ($outlet->geo_fence_type === 'radius' && $outlet->geo_fence_radius_metres)
                L.circle([lat, lng], { radius: {{ (int) $outlet->geo_fence_radius_metres }}, color: '#006F78', fillOpacity: 0.15 }).addTo(map);
                @elseif ($outlet->geo_fence_type === 'polygon' && !empty($outlet->geo_fence_polygon))
                var poly = {{ json_encode(array_map(function ($p) { return [(float)$p['lat'], (float)$p['lng']]; }, $outlet->geo_fence_polygon)) }};
                if (poly.length >= 3) {
                    if (poly[0][0] !== poly[poly.length-1][0] || poly[0][1] !== poly[poly.length-1][1]) poly.push(poly[0]);
                    L.polygon(poly, { color: '#006F78', fillOpacity: 0.2 }).addTo(map);
                }
                @endif
            })();
        </script>
        @else
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <p class="text-themeMuted">Set latitude and longitude on this outlet to see the map.</p>
        </div>
        @endif
    </div>
@endsection
