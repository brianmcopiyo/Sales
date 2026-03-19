@extends('layouts.app')

@section('title', 'Field Agent Details')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Field Agent Details</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $fieldAgent->user?->name ?? 'Agent' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (auth()->user()?->hasPermission('field-agents.update'))
                    <a href="{{ route('field-agents.edit', $fieldAgent) }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        <span>Edit</span>
                    </a>
                @endif
                @if ($fieldAgent->user && auth()->user()?->hasPermission('users.manage-field-agents'))
                    <form action="{{ route('users.revoke-field-agent', $fieldAgent->user) }}" method="POST"
                        class="inline" onsubmit="return confirm('Convert this field agent to a normal user? They will keep their branch and role but lose field agent features.');">
                        @csrf
                        <button type="submit"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                </path>
                            </svg>
                            <span>Convert to normal user</span>
                        </button>
                    </form>
                @endif
                <a href="{{ route('field-agents.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Agent Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                            <div class="text-base font-semibold text-themeHeading">{{ $fieldAgent->user?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $fieldAgent->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                {{ $fieldAgent->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Branch</div>
                            <div class="text-base font-medium text-themeHeading">{{ $fieldAgent->user?->branch?->name ?? '-' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Email</div>
                            <div class="text-base font-medium text-themeHeading">{{ $fieldAgent->user?->email ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Phone</div>
                            <div class="text-base font-medium text-themeHeading">{{ $fieldAgent->user?->phone ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Recent Distributions</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-themeBorder">
                            <thead class="bg-themeInput/80">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Sale</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Customer</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Device</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Commission</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                        Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-themeCard divide-y divide-themeBorder">
                                @forelse($saleItems as $item)
                                    <tr class="hover:bg-themeInput/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">
                                            <a href="{{ route('sales.show', $item->sale) }}"
                                                class="font-medium text-primary hover:text-primary-dark">{{ $item->sale->sale_number }}</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-themeHeading">
                                                {{ $item->sale->customer->name ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-themeHeading">
                                                {{ $item->device?->imei ?? '-' }}</div>
                                            <div class="text-xs font-medium text-themeMuted">
                                                {{ $item->product?->name ?? '' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-amber-600">{{ $currencySymbol }}
                                                {{ number_format((float) $item->commission_amount, 2) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-themeBody">
                                                {{ $item->created_at->format('M d, Y') }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-themeMuted font-medium">No
                                            distributions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($saleItems->hasPages())
                        <div class="mt-4 pt-4 border-t border-themeBorder">
                            {{ $saleItems->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Devices Distributed</div>
                            <div class="text-2xl font-semibold text-primary">{{ (int) $totals['devices_distributed'] }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Earned</div>
                            <div class="text-2xl font-semibold text-amber-600">{{ $currencySymbol }}
                                {{ number_format((float) $totals['total_commission'], 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Available Balance</div>
                            <div class="text-2xl font-semibold text-primary">{{ $currencySymbol }}
                                {{ number_format((float) ($totals['available_balance'] ?? 0), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

