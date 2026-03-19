@extends('layouts.app')

@section('title', 'Bills')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('dashboard'),
            'label' => 'Back to Dashboard',
        ])
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Bills</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Accounts payable – record and pay vendor invoices</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if($canExport ?? false)
                    <a href="{{ route('bills.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export</span>
                    </a>
                @endif
                @if(auth()->user()?->hasPermission('bills.manage-vendors'))
                    <a href="{{ route('bills.vendors.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Vendors</span>
                    </a>
                @endif
                @if($canCreate ?? false)
                    <a href="{{ route('bills.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>New Bill</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Summary cards (clickable) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('bills.index', ['filter' => 'unpaid'] + request()->only(['branch_id', 'vendor_id', 'category_id'])) }}"
                class="block bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-primary/40 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2 rounded-2xl">
                <div class="text-sm font-medium text-themeMuted mb-1">Total unpaid</div>
                <div class="text-2xl font-semibold text-primary">{{ $currencySymbol }} {{ number_format($stats['total_unpaid'] ?? 0, 2) }}</div>
            </a>
            <a href="{{ route('bills.index', ['filter' => 'due_this_week'] + request()->only(['branch_id', 'vendor_id', 'category_id'])) }}"
                class="block bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-amber-400/50 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-amber-400/30 focus:ring-offset-2 rounded-2xl">
                <div class="text-sm font-medium text-themeMuted mb-1">Due this week</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $stats['due_this_week'] ?? 0 }}</div>
            </a>
            <a href="{{ route('bills.index', ['filter' => 'overdue'] + request()->only(['branch_id', 'vendor_id', 'category_id'])) }}"
                class="block bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-red-400/50 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-red-400/30 focus:ring-offset-2 rounded-2xl">
                <div class="text-sm font-medium text-themeMuted mb-1">Overdue</div>
                <div class="text-2xl font-semibold text-red-600">{{ $stats['overdue'] ?? 0 }}</div>
            </a>
            <a href="{{ route('bills.index', ['filter' => 'paid_this_month'] + request()->only(['branch_id', 'vendor_id', 'category_id'])) }}"
                class="block bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-emerald-400/50 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-emerald-400/30 focus:ring-offset-2 rounded-2xl">
                <div class="text-sm font-medium text-themeMuted mb-1">Paid this month</div>
                <div class="text-2xl font-semibold text-emerald-600">{{ $currencySymbol }} {{ number_format($stats['paid_this_month'] ?? 0, 2) }}</div>
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('bills.index') }}" class="flex flex-wrap items-end gap-4">
                @if(request('filter'))
                    <input type="hidden" name="filter" value="{{ request('filter') }}">
                @endif
                <div class="w-48">
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <select id="branch_id" name="branch_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-48">
                    <label for="vendor_id" class="block text-sm font-medium text-themeBody mb-2">Vendor</label>
                    <select id="vendor_id" name="vendor_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <label for="category_id" class="block text-sm font-medium text-themeBody mb-2">Category</label>
                    <select id="category_id" name="category_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                    <select id="status" name="status"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending approval</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                @if(request()->hasAny(['branch_id','vendor_id','category_id','status','date_from','date_to']))
                    <a href="{{ route('bills.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <!-- Bills table -->
        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Invoice #</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Due date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($bills as $bill)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $bill->vendor?->name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody">{{ $bill->invoice_number ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody">{{ $bill->invoice_date?->format('M d, Y') ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm {{ $bill->isOverdue() ? 'text-red-600 font-medium' : 'text-themeBody' }}">{{ $bill->due_date?->format('M d, Y') ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-primary">{{ $bill->currency }} {{ number_format($bill->amount, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody">{{ $bill->category?->name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-themeBody">{{ $bill->branch?->name ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($bill->status === 'draft')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-themeHover text-themeBody">Draft</span>
                                    @elseif($bill->status === 'pending_approval')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">Pending approval</span>
                                    @elseif($bill->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-sky-100 text-sky-800">Approved</span>
                                    @elseif($bill->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                    @elseif($bill->status === 'paid')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Paid</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-themeHover text-themeBody">{{ $bill->status }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('bills.show', $bill) }}" class="font-medium text-primary hover:text-primary-dark">View</a>
                                    @if(($canEdit ?? false) && in_array($bill->status, ['draft', 'pending_approval']))
                                        <span class="text-themeBorder mx-1">|</span>
                                        <a href="{{ route('bills.edit', $bill) }}" class="font-medium text-primary hover:text-primary-dark">Edit</a>
                                    @endif
                                    @if(($canPay ?? false) && $bill->status === 'approved')
                                        <span class="text-themeBorder mx-1">|</span>
                                        <a href="{{ route('bills.show', [$bill, 'mark_paid' => 1]) }}" class="font-medium text-primary hover:text-primary-dark">Mark as paid</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-themeMuted font-medium">No bills found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($bills->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $bills->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
