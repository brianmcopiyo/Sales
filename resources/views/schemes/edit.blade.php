@extends('layouts.app')

@section('title', 'Edit Scheme')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('schemes.show', $scheme), 'label' => 'Back to Scheme'])
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit: {{ $scheme->name }}</h1>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">{{ session('success') }}</div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm max-w-2xl">
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 mb-6">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('schemes.update', $scheme) }}" class="space-y-5"
                x-data="{ type: '{{ old('type', $scheme->type) }}' }">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-themeBody mb-1">Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $scheme->name) }}" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-themeBody mb-1">Description</label>
                    <textarea id="description" name="description" rows="2"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('description', $scheme->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-themeBody mb-1">Type *</label>
                        <select id="type" name="type" x-model="type" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @foreach ($types as $val => $label)
                                <option value="{{ $val }}" {{ old('type', $scheme->type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="value" class="block text-sm font-medium text-themeBody mb-1">Value *</label>
                        <input type="number" step="0.01" id="value" name="value" value="{{ old('value', $scheme->value) }}" min="0" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="type === 'buy_x_get_y'">
                    <div>
                        <label for="buy_quantity" class="block text-sm font-medium text-themeBody mb-1">Buy quantity (X)</label>
                        <input type="number" id="buy_quantity" name="buy_quantity" value="{{ old('buy_quantity', $scheme->buy_quantity) }}" min="1"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label for="get_quantity" class="block text-sm font-medium text-themeBody mb-1">Get quantity (Y) free</label>
                        <input type="number" id="get_quantity" name="get_quantity" value="{{ old('get_quantity', $scheme->get_quantity) }}" min="1"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="min_order_amount" class="block text-sm font-medium text-themeBody mb-1">Min. order amount</label>
                        <input type="number" step="0.01" id="min_order_amount" name="min_order_amount" value="{{ old('min_order_amount', $scheme->min_order_amount) }}" min="0"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label for="min_quantity" class="block text-sm font-medium text-themeBody mb-1">Min. total quantity</label>
                        <input type="number" id="min_quantity" name="min_quantity" value="{{ old('min_quantity', $scheme->min_quantity) }}" min="1"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-themeBody mb-1">Start date *</label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $scheme->start_date?->format('Y-m-d')) }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-themeBody mb-1">End date *</label>
                        <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $scheme->end_date?->format('Y-m-d')) }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>

                <div>
                    <label for="region_id" class="block text-sm font-medium text-themeBody mb-1">Region restriction</label>
                    <select id="region_id" name="region_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All regions</option>
                        @foreach ($regions as $r)
                            <option value="{{ $r->id }}" {{ old('region_id', $scheme->region_id) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-themeBody mb-1">Outlet type restriction</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($outletTypes as $val => $label)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="applies_to_outlet_types[]" value="{{ $val }}"
                                    {{ in_array($val, old('applies_to_outlet_types', $scheme->applies_to_outlet_types ?? [])) ? 'checked' : '' }}
                                    class="rounded border-themeBorder text-primary focus:ring-primary/20">
                                <span class="text-sm text-themeBody">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $scheme->is_active) ? 'checked' : '' }}
                        class="rounded border-themeBorder text-primary focus:ring-primary/20">
                    <label for="is_active" class="text-sm font-medium text-themeBody">Active</label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Save changes</button>
                    <a href="{{ route('schemes.show', $scheme) }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
