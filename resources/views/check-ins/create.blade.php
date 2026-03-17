@extends('layouts.app')

@section('title', 'Check in')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('outlets.index'), 'label' => 'Back to Outlets'])
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Check in at outlet</h1>

        @if (session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <form method="POST" action="{{ route('check-ins.store') }}" enctype="multipart/form-data" id="checkin-form" class="space-y-6">
                @csrf

                <div>
                    <label for="outlet_id" class="block text-sm font-medium text-themeBody mb-1">Outlet *</label>
                    <select id="outlet_id" name="outlet_id" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Select outlet</option>
                        @foreach ($outlets as $o)
                            <option value="{{ $o->id }}" {{ old('outlet_id', request('outlet_id')) == $o->id ? 'selected' : '' }}>{{ $o->name }}{{ $o->code ? ' (' . $o->code . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="lat" class="block text-sm font-medium text-themeBody mb-1">Latitude *</label>
                        <div class="flex gap-2">
                            <input type="text" id="lat" name="lat" value="{{ old('lat') }}" required readonly
                                placeholder="Get location first"
                                class="flex-1 px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading bg-themeInput/50">
                            <button type="button" id="btn-get-location" class="px-4 py-2.5 bg-primary text-white rounded-xl font-medium hover:bg-primary-dark transition whitespace-nowrap">
                                Get my location
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-themeMuted">Your device location is used to verify you are at the outlet.</p>
                    </div>
                    <div>
                        <label for="lng" class="block text-sm font-medium text-themeBody mb-1">Longitude *</label>
                        <input type="text" id="lng" name="lng" value="{{ old('lng') }}" required readonly
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading bg-themeInput/50">
                    </div>
                </div>

                <div>
                    <label for="photo" class="block text-sm font-medium text-themeBody mb-1">Photo (optional)</label>
                    <input type="file" id="photo" name="photo" accept="image/*"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-primary/10 file:text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-themeBody mb-1">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="e.g. stock level, issues..."
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('notes') }}</textarea>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" id="btn-submit" class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Submit check-in
                    </button>
                    <a href="{{ route('outlets.index') }}" class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var latInput = document.getElementById('lat');
            var lngInput = document.getElementById('lng');
            var btn = document.getElementById('btn-get-location');
            var submitBtn = document.getElementById('btn-submit');

            function showError(msg) {
                if (typeof alert !== 'undefined') alert(msg);
            }

            btn.addEventListener('click', function () {
                btn.disabled = true;
                btn.textContent = 'Getting location…';
                if (!navigator.geolocation) {
                    showError('Geolocation is not supported by your browser.');
                    btn.disabled = false;
                    btn.textContent = 'Get my location';
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        latInput.value = pos.coords.latitude;
                        lngInput.value = pos.coords.longitude;
                        latInput.removeAttribute('readonly');
                        lngInput.removeAttribute('readonly');
                        btn.disabled = false;
                        btn.textContent = 'Get my location';
                    },
                    function (err) {
                        showError('Could not get location: ' + (err.message || 'Permission denied or unavailable.'));
                        btn.disabled = false;
                        btn.textContent = 'Get my location';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            });
        });
    </script>
@endsection
