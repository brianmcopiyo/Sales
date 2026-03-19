@extends('layouts.app')

@section('title', 'Schemes & Promotions')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Schemes &amp; Promotions</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Manage trade promotions and discounts applied at sale time</p>
            </div>
            @if (auth()->user()?->hasPermission('schemes.manage'))
                <a href="{{ route('schemes.create') }}"
                    class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    New Scheme
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3">{{ session('error') }}</div>
        @endif

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm">
                <div class="text-sm font-medium text-themeMuted mb-1">Active schemes</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['active_count'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm">
                <div class="text-sm font-medium text-themeMuted mb-1">Applied today</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $stats['applied_today'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-sm">
            <form method="GET" action="{{ route('schemes.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-48">
                    <label for="type" class="block text-sm font-medium text-themeBody mb-1">Type</label>
                    <select id="type" name="type"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All types</option>
                        @foreach ($types as $val => $label)
                            <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-36">
                    <label for="is_active" class="block text-sm font-medium text-themeBody mb-1">Status</label>
                    <select id="is_active" name="is_active"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="w-48">
                    <label for="region_id" class="block text-sm font-medium text-themeBody mb-1">Region</label>
                    <select id="region_id" name="region_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All regions</option>
                        @foreach ($regions as $r)
                            <option value="{{ $r->id }}" {{ request('region_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                @if (request()->hasAny(['type', 'is_active', 'region_id']))
                    <a href="{{ route('schemes.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Value</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Date range</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Region</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-themeBorder">
                        @forelse ($schemes as $scheme)
                            <tr class="hover:bg-themeInput/30 transition">
                                <td class="px-4 py-3">
                                    <a href="{{ route('schemes.show', $scheme) }}" class="font-medium text-primary hover:underline">{{ $scheme->name }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $types[$scheme->type] ?? $scheme->type }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">
                                    @if ($scheme->type === 'percentage_discount')
                                        {{ $scheme->value }}%
                                    @elseif ($scheme->type === 'buy_x_get_y')
                                        Buy {{ $scheme->buy_quantity }} Get {{ $scheme->get_quantity }}
                                    @else
                                        {{ $currencySymbol ?? '' }} {{ number_format($scheme->value, 2) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-themeBody">
                                    {{ $scheme->start_date->format('d M Y') }} – {{ $scheme->end_date->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $scheme->region?->name ?? 'All regions' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $scheme->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                        {{ $scheme->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm flex items-center gap-3">
                                    <a href="{{ route('schemes.show', $scheme) }}" class="text-primary hover:underline">View</a>
                                    @if (auth()->user()?->hasPermission('schemes.manage'))
                                        <a href="{{ route('schemes.edit', $scheme) }}" class="text-primary hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('schemes.destroy', $scheme) }}" class="inline" onsubmit="return confirm('Delete this scheme?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-themeMuted">No schemes found. Create one to start applying promotions to sales.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($schemes->hasPages())
                <div class="px-4 py-3 border-t border-themeBorder">{{ $schemes->links() }}</div>
            @endif
        </div>
    </div>
@endsection
