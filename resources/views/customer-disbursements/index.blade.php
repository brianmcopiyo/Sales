@extends('layouts.app')

@section('title', 'Customer Disbursements')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('customers.hub'),
            'label' => 'Back to Customers',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Customer Disbursements</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Customer support payments by device</p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()?->hasPermission('customer-disbursements.view'))
                <a href="{{ route('customer-disbursements.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export to Excel</span>
                </a>
                @endif
                @if(auth()->user()?->hasPermission('customer-disbursements.create'))
                <a href="{{ route('customer-disbursements.create') }}"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>New Disbursement</span>
                </a>
                @endif
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Disbursements</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Pending Approval</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $stats['pending'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Amount (Approved)</div>
                <div class="text-2xl font-semibold text-amber-600">TSh {{ number_format($stats['total_amount'], 2) }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">This Month</div>
                <div class="text-2xl font-semibold text-themeBody">TSh {{ number_format($stats['this_month'], 2) }}</div>
            </div>
        </div>

        <!-- Filter -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('customer-disbursements.index') }}"
                class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[180px]">
                    <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search (customer)</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        placeholder="Name, email, phone"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="customer_id" class="block text-sm font-medium text-themeBody mb-2">Customer</label>
                    <select id="customer_id" name="customer_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Customers</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(isset($branches) && $branches->isNotEmpty())
                    <div class="min-w-[160px]">
                        <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                        <select id="branch_id" name="branch_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">All branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="min-w-[140px]">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="min-w-[140px]">
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'customer_id', 'branch_id', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('customer-disbursements.index') }}"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Disbursements Table -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($disbursements as $disbursement)
                    <a href="{{ auth()->user()?->hasPermission('customer-disbursements.view') ? route('customer-disbursements.show', $disbursement) : '#' }}"
                        class="block px-4 py-4 hover:bg-themeInput/50 transition-colors {{ !auth()->user()?->hasPermission('customer-disbursements.view') ? 'pointer-events-none' : '' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-primary">{{ $disbursement->customer->name }}</div>
                                <div class="text-xs text-themeBody mt-0.5">{{ $disbursement->device?->imei ?? '—' }} · {{ $disbursement->sale?->sale_number ?? '—' }}</div>
                                <div class="text-xs text-themeMuted mt-1">{{ $disbursement->branch_for_display?->name ?? '—' }} · {{ $disbursement->created_at->format('M d, Y') }}</div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-sm font-semibold text-amber-600">TSh {{ number_format($disbursement->amount, 2) }}</div>
                                @php $dc = $disbursement->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($disbursement->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'); @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium mt-1 {{ $dc }}">{{ ucfirst($disbursement->status) }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No disbursements found.</div>
                @endforelse
            </div>
            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Device (IMEI)</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Sale</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Disbursed By</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($disbursements as $disbursement)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $disbursement->customer->name }}
                                    </div>
                                    <div class="text-sm font-medium text-themeMuted">
                                        {{ $disbursement->customer->email ?? $disbursement->customer->phone }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $disbursement->branch_for_display?->name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($disbursement->device)
                                        <div class="text-sm font-medium text-themeHeading">{{ $disbursement->device->imei }}
                                        </div>
                                        @if ($disbursement->device->product)
                                            <div class="text-sm font-medium text-themeMuted">
                                                {{ $disbursement->device->product->name }}</div>
                                        @endif
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-amber-600">TSh
                                        {{ number_format($disbursement->amount, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">
                                        {{ $disbursement->disbursement_phone ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($disbursement->sale)
                                        <a href="{{ route('sales.show', $disbursement->sale) }}"
                                            class="text-sm font-medium text-primary hover:text-primary-dark">
                                            {{ $disbursement->sale->sale_number }}
                                        </a>
                                    @else
                                        <span class="text-sm font-medium text-themeMuted">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($disbursement->status === 'pending')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">Pending</span>
                                    @elseif($disbursement->status === 'approved')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Approved</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $disbursement->disbursedBy->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">
                                        {{ $disbursement->created_at->format('M d, Y') }}</div>
                                    <div class="text-sm font-medium text-themeMuted">
                                        {{ $disbursement->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if(auth()->user()?->hasPermission('customer-disbursements.view'))
                                    <a href="{{ route('customer-disbursements.show', $disbursement) }}"
                                        class="font-medium text-primary hover:text-primary-dark">View</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-themeMuted font-medium">No
                                    disbursements
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($disbursements->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $disbursements->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

