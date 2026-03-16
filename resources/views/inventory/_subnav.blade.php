@php
    $current = $current ?? 'dashboard';
@endphp
<nav class="flex flex-wrap gap-2 p-1 bg-themeInput/80 rounded-xl border border-themeBorder w-fit">
    @if (auth()->user()?->hasPermission('inventory.view'))
        <a href="{{ route('inventory.dashboard') }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $current === 'dashboard' ? 'bg-themeCard text-primary shadow-sm border border-themeBorder' : 'text-themeBody hover:bg-themeCard hover:text-primary' }}">Dashboard</a>
    @endif
    @if (auth()->user()?->hasPermission('inventory.movements.view'))
        <a href="{{ route('inventory.movements') }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $current === 'movements' ? 'bg-themeCard text-primary shadow-sm border border-themeBorder' : 'text-themeBody hover:bg-themeCard hover:text-primary' }}">Movements</a>
    @endif
    @if (auth()->user()?->hasPermission('inventory.view'))
        <a href="{{ route('inventory.stock-history') }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $current === 'history' ? 'bg-themeCard text-primary shadow-sm border border-themeBorder' : 'text-themeBody hover:bg-themeCard hover:text-primary' }}">History</a>
    @endif
    @if (auth()->user()?->hasPermission('inventory.alerts.view'))
        <a href="{{ route('inventory.alerts') }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $current === 'alerts' ? 'bg-themeCard text-primary shadow-sm border border-themeBorder' : 'text-themeBody hover:bg-themeCard hover:text-primary' }}">Alerts</a>
    @endif
</nav>
