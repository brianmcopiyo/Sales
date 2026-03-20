@extends('layouts.portal')

@section('title', 'Order ' . ($sale->sale_number ?? $sale->id))

@section('content')
<div class="py-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('portal.orders.index') }}" class="text-sm hover:underline" style="color:#6b7280;">&larr; Back to Orders</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Order Details --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-lg font-bold" style="color:#111827;">
                            Order #{{ $sale->sale_number ?? $sale->id }}
                        </h1>
                        <p class="text-sm mt-0.5" style="color:#6b7280;">{{ $sale->created_at->format('F j, Y \a\t g:i A') }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        {{ $sale->status === 'completed' ? 'bg-green-100 text-green-800' : ($sale->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($sale->status) }}
                    </span>
                </div>

                {{-- Items --}}
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th class="text-left px-3 py-2 text-xs font-semibold uppercase" style="color:#6b7280;">Product</th>
                            <th class="text-right px-3 py-2 text-xs font-semibold uppercase" style="color:#6b7280;">Qty</th>
                            <th class="text-right px-3 py-2 text-xs font-semibold uppercase" style="color:#6b7280;">Unit Price</th>
                            <th class="text-right px-3 py-2 text-xs font-semibold uppercase" style="color:#6b7280;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr class="border-t" style="border-color:#f3f4f6;">
                                <td class="px-3 py-3">
                                    <p class="font-medium" style="color:#111827;">{{ $item->product?->name ?? 'N/A' }}</p>
                                    @if ($item->product?->brand)
                                        <p class="text-xs" style="color:#6b7280;">{{ $item->product->brand->name }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right" style="color:#374151;">{{ $item->quantity }}</td>
                                <td class="px-3 py-3 text-right" style="color:#374151;">{{ number_format($item->selling_price ?? 0, 2) }}</td>
                                <td class="px-3 py-3 text-right font-medium" style="color:#111827;">{{ number_format($item->subtotal ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Totals --}}
                <div class="mt-4 border-t pt-4" style="border-color:#e5e7eb;">
                    <div class="flex justify-between text-sm mb-1">
                        <span style="color:#6b7280;">Subtotal</span>
                        <span style="color:#374151;">{{ number_format($sale->subtotal, 2) }}</span>
                    </div>
                    @if ($sale->discount > 0)
                        <div class="flex justify-between text-sm mb-1">
                            <span style="color:#6b7280;">Discount</span>
                            <span class="text-green-600">-{{ number_format($sale->discount, 2) }}</span>
                        </div>
                    @endif
                    @if ($sale->tax > 0)
                        <div class="flex justify-between text-sm mb-1">
                            <span style="color:#6b7280;">Tax</span>
                            <span style="color:#374151;">{{ number_format($sale->tax, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-bold border-t pt-2 mt-2" style="border-color:#e5e7eb;">
                        <span style="color:#111827;">Total</span>
                        <span style="color:#111827;">{{ number_format($sale->total, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Applied Schemes --}}
            @if ($sale->schemes->isNotEmpty())
                <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                    <h2 class="text-sm font-semibold mb-3" style="color:#111827;">Applied Promotions</h2>
                    @foreach ($sale->schemes as $scheme)
                        <div class="flex items-center justify-between py-2 border-b last:border-0" style="border-color:#f3f4f6;">
                            <div>
                                <p class="text-sm font-medium" style="color:#111827;">{{ $scheme->name }}</p>
                                <p class="text-xs" style="color:#6b7280;">{{ ucfirst(str_replace('_', ' ', $scheme->type)) }}</p>
                            </div>
                            <span class="text-sm font-medium text-green-600">
                                -{{ number_format($scheme->pivot->discount_applied, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-5">
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <h2 class="text-sm font-semibold mb-3" style="color:#111827;">Order Info</h2>
                @if ($sale->outlet)
                    <div class="mb-2">
                        <p class="text-xs font-medium uppercase tracking-wide" style="color:#6b7280;">Outlet</p>
                        <p class="text-sm mt-0.5" style="color:#374151;">{{ $sale->outlet->name }}</p>
                    </div>
                @endif
                @if ($sale->branch)
                    <div class="mb-2">
                        <p class="text-xs font-medium uppercase tracking-wide" style="color:#6b7280;">Branch</p>
                        <p class="text-sm mt-0.5" style="color:#374151;">{{ $sale->branch->name }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide" style="color:#6b7280;">Sale Type</p>
                    <p class="text-sm mt-0.5" style="color:#374151;">{{ ucfirst($sale->sale_type) }}</p>
                </div>
            </div>

            <div class="rounded-xl border shadow-sm p-5" style="background:#f9fafb; border-color:#e5e7eb;">
                <p class="text-xs font-medium mb-2" style="color:#6b7280;">Have an issue with this order?</p>
                <a href="{{ route('portal.claims.create') }}?sale={{ $sale->id }}"
                   class="text-sm font-medium hover:underline" style="color:#006F78;">
                    Raise a Claim &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
