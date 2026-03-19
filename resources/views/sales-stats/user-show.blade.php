@extends('layouts.app')

@section('title', 'Sales Stats — ' . $user->name)

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('sales-stats.index'),
            'label' => 'Back to Sales Stats',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Sales performance</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $user->name }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total sales</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['total_sales'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Completed</div>
                <div class="text-2xl font-semibold text-emerald-600">{{ $stats['completed_sales'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Total in sales</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $currencySymbol }} {{ number_format($stats['total_revenue'], 2) }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Cost to sell</div>
                <div class="text-2xl font-semibold text-themeHeading">{{ $currencySymbol }} {{ number_format($stats['total_cost_to_sell'] ?? 0, 2) }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-xs font-medium text-themeMuted mb-1">Gross profit</div>
                <div class="text-2xl font-semibold text-emerald-600">{{ $currencySymbol }} {{ number_format($stats['total_profit'] ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="px-6 py-4 border-b border-themeBorder">
                <h2 class="text-lg font-semibold text-primary tracking-tight">Sales (initiated by this user)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Sale</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($sales as $sale)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $sale->sale_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">{{ $sale->customer?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">{{ $sale->branch?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($sale->status) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeBody">{{ $currencySymbol }} {{ number_format($sale->total ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">{{ $sale->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('sales.show', $sale) }}" class="text-primary hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-themeMuted font-medium">No sales found.</td>
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
