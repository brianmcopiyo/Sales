@extends('layouts.app')

@section('title', 'Edit Outlet')

@section('content')
    <div class="w-full">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Outlet</h1>
            <a href="{{ route('outlets.show', $outlet) }}"
                class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Back</span>
            </a>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <form method="POST" action="{{ route('outlets.update', $outlet) }}" class="space-y-6" x-data="{ branchSelected: {{ json_encode((bool) old('branch_id', $outlet->branch_id)) }} }">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-themeBody mb-1">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $outlet->name) }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary @error('name') border-red-300 @enderror">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="code" class="block text-sm font-medium text-themeBody mb-1">Code</label>
                        <input type="text" id="code" name="code" value="{{ old('code', $outlet->code) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-medium text-themeBody mb-1">Type</label>
                        <select id="type" name="type"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">—</option>
                            @foreach (\App\Models\Outlet::types() as $value => $label)
                                <option value="{{ $value }}" {{ old('type', $outlet->type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-themeBody mb-1">Branch</label>
                        <select id="branch_id" name="branch_id"
                            @change="branchSelected = $event.target.value !== ''; if ($event.target.value) document.getElementById('region_id').value = ''"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">—</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id', $outlet->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="!branchSelected" x-cloak>
                        <label for="region_id" class="block text-sm font-medium text-themeBody mb-1">Region</label>
                        <select id="region_id" name="region_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">—</option>
                            @foreach ($regions as $r)
                                <option value="{{ $r->id }}" {{ old('region_id', $outlet->region_id) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-themeBody mb-1">Assigned to</label>
                        <select id="assigned_to" name="assigned_to"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">—</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" {{ old('assigned_to', $outlet->assigned_to) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-themeBody mb-1">Address</label>
                        <textarea id="address" name="address" rows="2"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('address', $outlet->address) }}</textarea>
                    </div>
                    <div>
                        <label for="lat" class="block text-sm font-medium text-themeBody mb-1">Latitude</label>
                        <input type="number" id="lat" name="lat" value="{{ old('lat', $outlet->lat) }}" step="any"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label for="lng" class="block text-sm font-medium text-themeBody mb-1">Longitude</label>
                        <input type="number" id="lng" name="lng" value="{{ old('lng', $outlet->lng) }}" step="any"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-themeBody mb-1">Contact name</label>
                        <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $outlet->contact_name) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-themeBody mb-1">Contact phone</label>
                        <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $outlet->contact_phone) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-themeBody mb-1">Contact email</label>
                        <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $outlet->contact_email) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div class="md:col-span-2 border-t border-themeBorder pt-4" x-data="{ geoType: '{{ old('geo_fence_type', $outlet->geo_fence_type ?? '') }}' }">
                        <p class="text-sm font-medium text-themeBody mb-2">Geo-fence (optional)</p>
                        <div class="flex flex-wrap gap-4 items-center mb-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="geo_fence_type" value="" x-model="geoType" class="rounded border-themeBorder text-primary focus:ring-primary/20">
                                <span class="ml-2 text-sm text-themeBody">None</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="geo_fence_type" value="radius" x-model="geoType" class="rounded border-themeBorder text-primary focus:ring-primary/20">
                                <span class="ml-2 text-sm text-themeBody">Radius</span>
                            </label>
                            <template x-if="geoType === 'radius'">
                                <span class="flex items-center gap-2">
                                    <input type="number" name="geo_fence_radius_metres" value="{{ old('geo_fence_radius_metres', $outlet->geo_fence_radius_metres ?? 100) }}" min="10" max="5000" step="10"
                                        class="w-24 px-3 py-2 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20">
                                    <span class="text-sm text-themeMuted">metres</span>
                                </span>
                            </template>
                            <label class="inline-flex items-center">
                                <input type="radio" name="geo_fence_type" value="polygon" x-model="geoType" class="rounded border-themeBorder text-primary focus:ring-primary/20">
                                <span class="ml-2 text-sm text-themeBody">Polygon</span>
                            </label>
                        </div>
                        <div x-show="geoType === 'polygon'" x-cloak class="mt-2">
                            <label for="geo_fence_polygon" class="block text-sm font-medium text-themeBody mb-1">Polygon (JSON, 4+ points)</label>
                            <textarea id="geo_fence_polygon" name="geo_fence_polygon" rows="4" placeholder='[{"lat":-6.16,"lng":35.75},...]'
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading font-mono text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('geo_fence_polygon', $outlet->geo_fence_polygon ? json_encode($outlet->geo_fence_polygon) : '') }}</textarea>
                            @error('geo_fence_polygon')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $outlet->is_active) ? 'checked' : '' }}
                            class="rounded border-themeBorder text-primary focus:ring-primary/20">
                        <label for="is_active" class="ml-2 text-sm font-medium text-themeBody">Active</label>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Update Outlet
                    </button>
                    <a href="{{ route('outlets.show', $outlet) }}" class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
