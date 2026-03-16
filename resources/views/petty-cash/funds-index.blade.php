@extends('layouts.app')

@section('title', 'Manage Petty Cash Funds')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Manage Funds</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Create and edit petty cash funds by branch</p>
            </div>
            <a href="{{ route('petty-cash.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to Petty Cash</span>
            </a>
        </div>

        @php
            $branchIdsWithFund = $funds->pluck('branch_id')->all();
            $branchesWithoutFund = $branches->filter(function ($b) use ($branchIdsWithFund) { return !in_array($b->id, $branchIdsWithFund); });
        @endphp
        @if($branchesWithoutFund->isNotEmpty())
            <div class="flex justify-end">
                <a href="{{ route('petty-cash.funds.create') }}"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    Create New Fund
                </a>
            </div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <h2 class="text-lg font-semibold text-primary tracking-tight p-6 pb-2">Existing Funds</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Balance / Limit</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Custodian</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($funds as $fund)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('petty-cash.funds.show', $fund) }}" class="text-sm font-medium text-primary hover:text-primary-dark hover:underline">{{ $fund->branch->name }}</a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-amber-600">{{ $fund->currency }} {{ number_format($fund->current_balance, 2) }}</div>
                                    <div class="text-xs font-medium text-themeMuted">Limit: {{ number_format($fund->fund_limit, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $fund->custodian?->name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($fund->is_active)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-themeInput text-themeMuted">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('petty-cash.funds.edit', $fund) }}"
                                        class="font-medium text-primary hover:text-primary-dark mr-2">Edit</a>
                                    @if($canReplenish ?? false)
                                        <a href="{{ route('petty-cash.replenish.form', $fund) }}"
                                            class="font-medium text-primary hover:text-primary-dark mr-2">Replenish</a>
                                    @endif
                                    <a href="{{ route('petty-cash.reconciliation', $fund) }}"
                                        class="font-medium text-primary hover:text-primary-dark">Reconcile</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-themeMuted font-medium">No funds yet. Create one using the button above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
