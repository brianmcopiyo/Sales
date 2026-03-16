@extends('layouts.app')

@section('title', 'Support')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Support</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Tickets and requests</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="{{ route('tickets.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Tickets</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $stats['tickets_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['tickets_open'] }} open</div>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'open']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Open Tickets</div>
                <div class="text-2xl font-semibold text-amber-600 mt-1">{{ $stats['tickets_open'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">Requires attention</div>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if (auth()->user()?->hasPermission('tickets.view'))
                @include('hubs._hub-card', [
                    'href' => route('tickets.index'),
                    'title' => 'Tickets',
                    'description' => 'Support tickets and requests',
                    'icon' =>
                        'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ])
            @endif
        </div>

        @if (auth()->user()?->hasPermission('tickets.view') && $recentTickets->isNotEmpty())
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Tickets</h2>
                    <a href="{{ route('tickets.index') }}"
                        class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                </div>
                <ul class="divide-y divide-themeBorder" id="tickets-list">
                    @foreach ($recentTickets as $ticket)
                        <li class="px-6 py-3 hover:bg-themeInput/50 transition">
                            <a href="{{ route('tickets.show', $ticket) }}" class="flex justify-between items-center gap-2">
                                <span
                                    class="text-sm font-medium text-themeHeading truncate">{{ $ticket->ticket_number ?? '#' . $ticket->id }}</span>
                                <span
                                    class="shrink-0 px-2.5 py-0.5 rounded-lg text-xs font-medium {{ in_array($ticket->status, ['open', 'in_progress']) ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status ?? 'open')) }}</span>
                            </a>
                            <div class="text-xs font-medium text-themeMuted mt-0.5">
                                {{ \Illuminate\Support\Str::limit($ticket->subject ?? 'No subject', 50) }} ·
                                {{ $ticket->created_at->format('M d, Y') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

@endsection
