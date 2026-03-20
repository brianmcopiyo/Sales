@extends('layouts.portal')

@section('title', 'Schemes & Promotions')

@section('content')
<div class="py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-xl font-bold" style="color:#111827;">Schemes & Promotions</h1>
        <div class="flex gap-2">
            @foreach (['active' => 'Active', 'upcoming' => 'Upcoming', 'expired' => 'Expired', '' => 'All'] as $val => $label)
                <a href="{{ route('portal.schemes.index', ['filter' => $val]) }}"
                   class="text-xs px-3 py-1.5 rounded-lg font-medium border transition-colors
                          {{ $filter === $val ? 'text-white border-transparent' : 'hover:bg-gray-50' }}"
                   style="{{ $filter === $val ? 'background-color:#006F78; border-color:#006F78;' : 'border-color:#e5e7eb; color:#374151;' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($schemes as $scheme)
            @php
                $isActive = $scheme->is_active && now()->between($scheme->start_date, $scheme->end_date);
                $isUpcoming = $scheme->is_active && now()->lt($scheme->start_date);
                $daysLeft = now()->diffInDays($scheme->end_date, false);
            @endphp
            <div class="rounded-xl border shadow-sm p-5" style="background:#fff; border-color:#e5e7eb;">
                <div class="flex items-start justify-between mb-3">
                    <h2 class="text-sm font-semibold" style="color:#111827;">{{ $scheme->name }}</h2>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $isActive ? 'bg-green-100 text-green-800' : ($isUpcoming ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600') }}">
                        {{ $isActive ? 'Active' : ($isUpcoming ? 'Upcoming' : 'Expired') }}
                    </span>
                </div>

                @if ($scheme->description)
                    <p class="text-xs mb-3" style="color:#6b7280;">{{ $scheme->description }}</p>
                @endif

                <div class="grid grid-cols-2 gap-2 mb-4 text-xs">
                    <div>
                        <p style="color:#6b7280;">Type</p>
                        <p class="font-medium" style="color:#374151;">{{ ucfirst(str_replace('_', ' ', $scheme->type)) }}</p>
                    </div>
                    <div>
                        <p style="color:#6b7280;">Value</p>
                        <p class="font-medium" style="color:#374151;">
                            @if ($scheme->type === 'flat_discount')
                                {{ number_format($scheme->value, 2) }} off
                            @elseif ($scheme->type === 'percentage_discount')
                                {{ $scheme->value }}% off
                            @else
                                Buy {{ $scheme->buy_quantity }}, Get {{ $scheme->get_quantity }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <p style="color:#6b7280;">Start Date</p>
                        <p class="font-medium" style="color:#374151;">{{ \Carbon\Carbon::parse($scheme->start_date)->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p style="color:#6b7280;">End Date</p>
                        <p class="font-medium" style="color:#374151;">{{ \Carbon\Carbon::parse($scheme->end_date)->format('M d, Y') }}</p>
                    </div>
                </div>

                @if ($scheme->min_order_amount)
                    <p class="text-xs mb-2" style="color:#6b7280;">
                        Min. order: <strong>{{ number_format($scheme->min_order_amount, 2) }}</strong>
                    </p>
                @endif

                {{-- Achievement stats --}}
                <div class="mt-3 pt-3 border-t" style="border-color:#f3f4f6;">
                    <div class="flex justify-between text-xs mb-1">
                        <span style="color:#6b7280;">Orders Applied</span>
                        <span class="font-medium" style="color:#374151;">{{ $scheme->achieved_count }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span style="color:#6b7280;">Total Discount Received</span>
                        <span class="font-medium text-green-600">{{ number_format($scheme->total_discount, 2) }}</span>
                    </div>
                </div>

                @if ($isActive && $daysLeft >= 0)
                    <p class="mt-3 text-xs font-medium" style="color:#d97706;">
                        ⏰ {{ $daysLeft }} day{{ $daysLeft !== 1 ? 's' : '' }} remaining
                    </p>
                @endif
            </div>
        @empty
            <div class="col-span-3 py-12 text-center">
                <p class="text-sm" style="color:#6b7280;">No {{ $filter }} schemes found.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
