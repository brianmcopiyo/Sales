@extends('layouts.app')

@section('title', $scheme->name)

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('schemes.index'), 'label' => 'Back to Schemes'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $scheme->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $scheme->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                        {{ $scheme->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="text-sm text-themeMuted">{{ \App\Models\Scheme::types()[$scheme->type] ?? $scheme->type }}</span>
                </div>
            </div>
            @if (auth()->user()?->hasPermission('schemes.manage'))
                <a href="{{ route('schemes.edit', $scheme) }}"
                    class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Edit</a>
            @endif
        </div>

        <!-- Details Card -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-themeHeading mb-4">Scheme details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if ($scheme->description)
                    <div class="md:col-span-2">
                        <div class="text-sm font-medium text-themeMuted mb-1">Description</div>
                        <div class="text-base text-themeBody">{{ $scheme->description }}</div>
                    </div>
                @endif
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Type</div>
                    <div class="text-base font-medium text-themeHeading">{{ \App\Models\Scheme::types()[$scheme->type] ?? $scheme->type }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Value</div>
                    <div class="text-base font-medium text-themeHeading">
                        @if ($scheme->type === 'percentage_discount')
                            {{ $scheme->value }}%
                        @elseif ($scheme->type === 'buy_x_get_y')
                            Buy {{ $scheme->buy_quantity }} Get {{ $scheme->get_quantity }} free
                        @else
                            {{ $currencySymbol ?? '' }} {{ number_format($scheme->value, 2) }}
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Date range</div>
                    <div class="text-base font-medium text-themeHeading">{{ $scheme->start_date->format('d M Y') }} – {{ $scheme->end_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Region</div>
                    <div class="text-base font-medium text-themeHeading">{{ $scheme->region?->name ?? 'All regions' }}</div>
                </div>
                @if ($scheme->min_order_amount)
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Min. order amount</div>
                        <div class="text-base font-medium text-themeHeading">{{ $currencySymbol ?? '' }} {{ number_format($scheme->min_order_amount, 2) }}</div>
                    </div>
                @endif
                @if ($scheme->min_quantity)
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Min. quantity</div>
                        <div class="text-base font-medium text-themeHeading">{{ $scheme->min_quantity }}</div>
                    </div>
                @endif
                @if (!empty($scheme->applies_to_outlet_types))
                    <div class="md:col-span-2">
                        <div class="text-sm font-medium text-themeMuted mb-1">Applies to outlet types</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($scheme->applies_to_outlet_types as $type)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-themeHover text-themeBody">{{ ucfirst($type) }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sales that used this scheme -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-themeBorder">
                <h2 class="text-lg font-semibold text-themeHeading">Sales using this scheme</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Sale #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Sold By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Discount applied</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-themeBorder">
                        @forelse ($sales as $sale)
                            <tr class="hover:bg-themeInput/30 transition">
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('sales.show', $sale) }}" class="text-primary hover:underline font-medium">{{ $sale->sale_number }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $sale->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $sale->outlet?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $sale->soldBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-emerald-600">{{ $currencySymbol ?? '' }} {{ number_format($sale->pivot->discount_applied, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-themeMuted">No sales have used this scheme yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($sales->hasPages())
                <div class="px-4 py-3 border-t border-themeBorder">{{ $sales->links() }}</div>
            @endif
        </div>
    </div>
@endsection
