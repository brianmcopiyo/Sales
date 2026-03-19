@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Customers</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Customer records</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('customers.index') }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Customers</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $stats['customers_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['customers_active'] }} active</div>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if (auth()->user()?->hasPermission('customers.view'))
                @include('hubs._hub-card', [
                    'href' => route('customers.index'),
                    'title' => 'Customers',
                    'description' => 'Customer list and profiles',
                    'icon' =>
                        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                ])
            @endif
        </div>
    </div>

@endsection
