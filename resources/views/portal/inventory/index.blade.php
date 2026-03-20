@extends('layouts.portal')

@section('title', 'Inventory')

@section('content')
<div class="py-6">
    <h1 class="text-xl font-bold mb-6" style="color:#111827;">Inventory</h1>

    @if (!empty($noBranch))
        <div class="rounded-xl border p-8 text-center" style="background:#fff; border-color:#e5e7eb;">
            <p class="text-sm font-medium" style="color:#374151;">No branch assigned to your account.</p>
            <p class="text-sm mt-1" style="color:#6b7280;">Please contact your account manager to link a branch for inventory visibility.</p>
        </div>
    @else
        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-3 mb-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product..."
                   class="text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2"
                   style="border-color:#e5e7eb;">
            <label class="flex items-center gap-2 text-sm" style="color:#374151;">
                <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }}
                       class="rounded">
                Low stock only
            </label>
            <button type="submit" class="text-sm px-4 py-2 rounded-lg font-medium text-white" style="background-color:#006F78;">
                Filter
            </button>
            @if (request()->hasAny(['search', 'low_stock']))
                <a href="{{ route('portal.inventory.index') }}" class="text-sm px-4 py-2 rounded-lg border font-medium"
                   style="border-color:#e5e7eb; color:#374151;">Clear</a>
            @endif
        </form>

        <div class="rounded-xl border shadow-sm overflow-hidden" style="background:#fff; border-color:#e5e7eb;">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Product</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Brand</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">In Stock</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Min. Required</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $stock)
                            @php
                                $isLow = $stock->quantity <= ($stock->minimum_quantity ?? 0);
                            @endphp
                            <tr class="border-t {{ $isLow ? 'bg-red-50/30' : '' }}" style="border-color:#f3f4f6;">
                                <td class="px-4 py-3 font-medium" style="color:#111827;">{{ $stock->product?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-xs" style="color:#6b7280;">{{ $stock->product?->brand?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $isLow ? 'text-red-600' : 'text-green-700' }}">
                                    {{ $stock->quantity }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs" style="color:#6b7280;">{{ $stock->minimum_quantity ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($isLow)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Low Stock</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm" style="color:#6b7280;">No inventory data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($stocks->hasPages())
                <div class="px-4 py-3 border-t" style="border-color:#e5e7eb;">
                    {{ $stocks->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
