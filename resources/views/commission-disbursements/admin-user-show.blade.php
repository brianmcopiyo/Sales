@extends('layouts.app')

@section('title', 'Commission details — ' . $user->name)

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('commission-disbursements.admin.index') }}"
                    class="text-sm font-medium text-primary hover:text-primary-dark inline-flex items-center gap-1 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Commissions
                </a>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $user->name }}</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Commission by sale</p>
            </div>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] max-w-md">
            <div class="text-sm font-medium text-themeMuted mb-1">Total commission (filtered)</div>
            <div class="text-2xl font-semibold text-primary">TSh {{ number_format($totalInPeriod, 2) }}</div>
        </div>

        <!-- Filters -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('commission-disbursements.admin.user.show', $user) }}" class="flex flex-wrap items-end gap-4">
                <div class="min-w-[140px]">
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">Date from</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="min-w-[140px]">
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">Date to</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                    @if (request()->hasAny(['date_from', 'date_to']))
                        <a href="{{ route('commission-disbursements.admin.user.show', $user) }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Sales table -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Sale #</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Commission</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $sale->sale_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $sale->customer->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $sale->branch->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $sale->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary">TSh {{ number_format((float) ($sale->total_commission ?? 0), 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('sales.show', $sale) }}" class="font-medium text-primary hover:text-primary-dark">View sale</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-themeMuted font-medium">No sales with commission found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($sales->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $sales->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
