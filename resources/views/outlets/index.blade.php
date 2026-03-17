@extends('layouts.app')

@section('title', 'Outlets')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('dashboard'), 'label' => 'Back to Dashboard'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Outlets</h1>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasPermission('checkins.create'))
                    <a href="{{ route('check-ins.create') }}"
                        class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Check in</span>
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('checkins.view'))
                    <a href="{{ route('check-ins.index') }}"
                        class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">
                        <span>Check-in history</span>
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('outlets.manage'))
                    <a href="{{ route('outlets.create') }}"
                        class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Outlet</span>
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('outlets.index') }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm cursor-pointer transition-all hover:shadow-lg {{ !request('status') ? 'ring-2 ring-primary border-primary' : '' }}">
                <div class="text-sm font-medium text-themeMuted mb-1">Total</div>
                <div class="text-2xl font-semibold text-themeHeading">{{ $stats['total'] }}</div>
            </a>
            <a href="{{ route('outlets.index', ['status' => 'active']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm cursor-pointer transition-all hover:shadow-lg {{ request('status') === 'active' ? 'ring-2 ring-primary border-primary' : '' }}">
                <div class="text-sm font-medium text-themeMuted mb-1">Active</div>
                <div class="text-2xl font-semibold text-emerald-600">{{ $stats['active'] }}</div>
            </a>
            <a href="{{ route('outlets.index', ['status' => 'inactive']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm cursor-pointer transition-all hover:shadow-lg {{ request('status') === 'inactive' ? 'ring-2 ring-primary border-primary' : '' }}">
                <div class="text-sm font-medium text-themeMuted mb-1">Inactive</div>
                <div class="text-2xl font-semibold text-themeHeading">{{ $stats['inactive'] }}</div>
            </a>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-sm">
            <form method="GET" action="{{ route('outlets.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Name, code, address..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-themeHeading">
                </div>
                <div class="w-44">
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch_id" name="branch_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-themeHeading">
                        <option value="">All</option>
                        @foreach ($branches ?? [] as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="region_id" class="block text-sm font-medium text-themeBody mb-2">Region</label>
                    <select id="region_id" name="region_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-themeHeading">
                        <option value="">All</option>
                        @foreach ($regions ?? [] as $r)
                            <option value="{{ $r->id }}" {{ request('region_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="type" class="block text-sm font-medium text-themeBody mb-2">Type</label>
                    <select id="type" name="type"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-themeHeading">
                        <option value="">All</option>
                        @foreach (\App\Models\Outlet::types() as $value => $label)
                            <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-themeHeading">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Filter</button>
                @if (request()->hasAny(['search', 'branch_id', 'region_id', 'type', 'status']))
                    <a href="{{ route('outlets.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        @php
            $outletsWithLocation = $outlets->filter(fn ($o) => $o->lat !== null && $o->lng !== null);
            $outletsMapData = $outletsWithLocation->map(function ($o) {
                $arr = ['id' => $o->id, 'name' => $o->name, 'lat' => (float) $o->lat, 'lng' => (float) $o->lng];
                if ($o->geo_fence_type === 'radius' && $o->geo_fence_radius_metres) {
                    $arr['radius'] = (int) $o->geo_fence_radius_metres;
                } elseif ($o->geo_fence_type === 'polygon' && !empty($o->geo_fence_polygon)) {
                    $arr['polygon'] = array_map(function ($p) { return [(float)($p['lat'] ?? $p[0] ?? 0), (float)($p['lng'] ?? $p[1] ?? 0)]; }, $o->geo_fence_polygon);
                }
                return $arr;
            })->values();
        @endphp
        @if ($outletsWithLocation->isNotEmpty())
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-themeHeading mb-4">Map (outlets on this page)</h2>
            <div id="outlets-map" class="w-full rounded-xl overflow-hidden border border-themeBorder" style="height: 400px;"></div>
        </div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function () {
                var outlets = @json($outletsMapData);
                if (outlets.length === 0) return;
                var center = [outlets[0].lat, outlets[0].lng];
                var map = L.map('outlets-map').setView(center, outlets.length === 1 ? 15 : 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
                var bounds = [];
                outlets.forEach(function (o) {
                    var latlng = [o.lat, o.lng];
                    bounds.push(latlng);
                    var popup = '<a href="{{ url('outlets') }}/' + o.id + '">' + (o.name || 'Outlet') + '</a>';
                    L.marker(latlng).addTo(map).bindPopup(popup);
                    if (o.radius) L.circle(latlng, { radius: o.radius, color: '#006F78', fillOpacity: 0.12 }).addTo(map);
                    if (o.polygon && o.polygon.length >= 3) {
                        var p = o.polygon.slice();
                        if (p[0][0] !== p[p.length-1][0] || p[0][1] !== p[p.length-1][1]) p.push(p[0]);
                        L.polygon(p, { color: '#006F78', fillOpacity: 0.15 }).addTo(map);
                    }
                });
                if (outlets.length > 1) map.fitBounds(bounds, { padding: [20, 20] });
            })();
        </script>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Code / Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Branch / Region</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Assigned</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Geo-fence</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Status</th>
                            @if (auth()->user()?->hasPermission('outlets.view'))
                                <th class="px-4 py-3 text-right text-xs font-semibold text-themeMuted uppercase">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-themeBorder">
                        @forelse ($outlets as $outlet)
                            <tr class="hover:bg-themeInput/30 transition">
                                <td class="px-4 py-3">
                                    @if (auth()->user()?->hasPermission('outlets.view'))
                                        <a href="{{ route('outlets.show', $outlet) }}" class="font-medium text-primary hover:underline">{{ $outlet->name }}</a>
                                    @else
                                        <span class="font-medium text-themeHeading">{{ $outlet->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $outlet->code ?? '—' }} · {{ $outlet->type ? \App\Models\Outlet::types()[$outlet->type] ?? $outlet->type : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $outlet->branch?->name ?? '—' }} / {{ $outlet->region?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $outlet->assignedUser?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">
                                    @if ($outlet->geo_fence_type === 'radius')
                                        {{ $outlet->geo_fence_radius_metres }} m
                                    @elseif ($outlet->geo_fence_type === 'polygon')
                                        Polygon
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $outlet->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">{{ $outlet->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                @if (auth()->user()?->hasPermission('outlets.view'))
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('outlets.show', $outlet) }}" class="text-primary hover:underline text-sm">View</a>
                                        @if (auth()->user()?->hasPermission('outlets.manage'))
                                            <a href="{{ route('outlets.edit', $outlet) }}" class="ml-3 text-primary hover:underline text-sm">Edit</a>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-themeMuted">No outlets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($outlets->hasPages())
                <div class="px-4 py-3 border-t border-themeBorder">{{ $outlets->links() }}</div>
            @endif
        </div>
    </div>
@endsection
