@extends('layouts.app')

@section('title', 'Tickets')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('support.index'), 'label' => 'Back to Support'])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Tickets</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Support and issue tracking</p>
            </div>
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasPermission('tickets.view'))
                    <a href="{{ route('tickets.export') . (request()->query() ? '?' . http_build_query(request()->query()) : '') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export to Excel</span>
                    </a>
                @endif
                @if (auth()->user()?->hasPermission('tickets.create'))
                    <a href="{{ route('tickets.create') }}"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>New Ticket</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <a href="{{ route('tickets.index') }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ !request('status') && !request('overdue') ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="tickets-all">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Tickets</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['total'] }}</div>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'open']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'open' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="tickets-open">
                <div class="text-sm font-medium text-themeMuted mb-1">Open</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $stats['open'] }}</div>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'in_progress']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'in_progress' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="tickets-in-progress">
                <div class="text-sm font-medium text-themeMuted mb-1">In Progress</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['in_progress'] }}</div>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'resolved']) }}"
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('status') === 'resolved' ? 'ring-2 ring-primary border-primary' : '' }}"
                data-filter="tickets-resolved">
                <div class="text-sm font-medium text-themeMuted mb-1">Resolved</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['resolved'] }}</div>
            </a>
            @if (!auth()->user()->isCustomer())
                <a href="{{ route('tickets.index', ['overdue' => '1']) }}"
                    class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30 {{ request('overdue') === '1' ? 'ring-2 ring-primary border-primary' : '' }}"
                    data-filter="tickets-overdue">
                    <div class="text-sm font-medium text-themeMuted mb-1">Overdue</div>
                    <div class="text-2xl font-semibold text-red-600">{{ $stats['overdue'] }}</div>
                </a>
            @endif
        </div>

        <!-- Filters Section -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="GET" action="{{ route('tickets.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-themeBody mb-2">Search</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                            placeholder="Search tickets..."
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>

                    <!-- Status Filter -->
                    <div class="w-40">
                        <label for="status" class="block text-sm font-medium text-themeBody mb-2">Status</label>
                        <select id="status" name="status"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">All Statuses</option>
                            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In
                                Progress</option>
                            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved
                            </option>
                            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div class="w-40">
                        <label for="priority" class="block text-sm font-medium text-themeBody mb-2">Priority</label>
                        <select id="priority" name="priority"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">All Priorities</option>
                            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="w-44">
                        <label for="category" class="block text-sm font-medium text-themeBody mb-2">Category</label>
                        <select id="category" name="category"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">All Categories</option>
                            <option value="technical" {{ request('category') === 'technical' ? 'selected' : '' }}>Technical</option>
                            <option value="billing" {{ request('category') === 'billing' ? 'selected' : '' }}>Billing</option>
                            <option value="sales" {{ request('category') === 'sales' ? 'selected' : '' }}>Sales</option>
                            <option value="general" {{ request('category') === 'general' ? 'selected' : '' }}>General</option>
                            <option value="order" {{ request('category') === 'order' ? 'selected' : '' }}>Order</option>
                            <option value="promise" {{ request('category') === 'promise' ? 'selected' : '' }}>Promise</option>
                            <option value="complaint" {{ request('category') === 'complaint' ? 'selected' : '' }}>Complaint</option>
                            <option value="unsuccessful" {{ request('category') === 'unsuccessful' ? 'selected' : '' }}>Unsuccessful</option>
                            <option value="credit" {{ request('category') === 'credit' ? 'selected' : '' }}>Credit</option>
                        </select>
                    </div>
                </div>

                @if (!auth()->user()->isCustomer())
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Assigned To Filter -->
                        <div class="w-48">
                            <label for="assigned_to" class="block text-sm font-medium text-themeBody mb-2">Assigned
                                To</label>
                            <select id="assigned_to" name="assigned_to"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <option value="">All Staff</option>
                                <option value="unassigned"
                                    {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>
                                    Unassigned</option>
                                @foreach ($staff as $user)
                                    <option value="{{ $user->id }}"
                                        {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Product Filter -->
                        <div class="w-48">
                            <label for="product_id" class="block text-sm font-medium text-themeBody mb-2">Product</label>
                            <select id="product_id" name="product_id"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <option value="">All Products</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}"
                                        {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Branch Filter -->
                        <div class="w-48">
                            <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                            <select id="branch_id" name="branch_id"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <option value="">All Branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                        <!-- Tag Filter -->
                        <div class="w-40">
                            <label for="tag" class="block text-sm font-medium text-themeBody mb-2">Tag</label>
                            <select id="tag" name="tag"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                <option value="">All Tags</option>
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->id }}"
                                        {{ request('tag') == $tag->id ? 'selected' : '' }}>
                                        {{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-themeBody mb-2">Date From</label>
                            <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-themeBody mb-2">Date To</label>
                            <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="overdue" value="1"
                                {{ request('overdue') ? 'checked' : '' }}
                                class="rounded border-themeBorder text-primary focus:ring-primary/20">
                            <span class="ml-2 text-sm font-medium text-themeBody">Show Overdue Only</span>
                        </label>
                    </div>
                @endif

                <div class="flex space-x-2">
                    <button type="submit"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Apply Filters
                    </button>
                    <a href="{{ route('tickets.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($tickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="block px-4 py-4 hover:bg-themeInput/50 transition-colors {{ $ticket->isOverdue() ? 'bg-red-50/50' : '' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-primary">{{ $ticket->ticket_number }} {{ $ticket->isOverdue() ? '· Overdue' : '' }}</div>
                                <div class="text-sm text-themeBody mt-0.5 truncate">{{ $ticket->subject }}</div>
                                <div class="text-xs text-themeMuted mt-1">{{ $ticket->customer->name }} · {{ $ticket->created_at->format('M d, Y') }}</div>
                            </div>
                            @php $tc = $ticket->status === 'open' ? 'bg-amber-100 text-amber-800' : ($ticket->status === 'resolved' || $ticket->status === 'closed' ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody'); @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium flex-shrink-0 {{ $tc }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted font-medium">No tickets found.</div>
                @endforelse
            </div>
            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Ticket #</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Customer</th>
                            @if (!auth()->user()->isCustomer())
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                    Assigned To</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Created</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder" id="tickets-table-body">
                        @forelse($tickets as $ticket)
                            <tr
                                class="hover:bg-themeInput/50 transition-colors {{ $ticket->isOverdue() ? 'bg-red-50/50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $ticket->ticket_number }}</div>
                                    @if ($ticket->isOverdue())
                                        <div class="text-xs font-medium text-red-600">Overdue</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-themeHeading">{{ $ticket->subject }}</div>
                                    <div class="text-xs font-medium text-themeMuted">{{ ucfirst($ticket->category) }}
                                    </div>
                                    @if ($ticket->device)
                                        <div class="text-xs font-medium text-themeMuted mt-1"><span
                                                class="text-primary">Device:</span> {{ $ticket->device->imei }}</div>
                                    @endif
                                    @if ($ticket->product)
                                        <div class="text-xs font-medium text-themeMuted mt-1"><span
                                                class="text-primary">Product:</span> {{ $ticket->product->name }}</div>
                                    @endif
                                    @if ($ticket->tags->count() > 0)
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach ($ticket->tags as $tag)
                                                <span class="px-2 py-0.5 text-xs rounded-lg font-medium"
                                                    style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">{{ $tag->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $ticket->customer->name }}</div>
                                </td>
                                @if (!auth()->user()->isCustomer())
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($ticket->assignedTo)
                                            <div class="flex items-center gap-2">
                                                <x-profile-picture :user="$ticket->assignedTo" size="xs" />
                                                <div class="text-sm font-medium text-themeBody">
                                                    {{ $ticket->assignedTo->name }}</div>
                                            </div>
                                        @else
                                            <div class="text-sm font-medium text-themeMuted">-</div>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $priorityColors = [
                                            'low' => 'bg-sky-100 text-sky-800',
                                            'medium' => 'bg-amber-100 text-amber-800',
                                            'high' => 'bg-orange-100 text-orange-800',
                                            'urgent' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $priorityColors[$ticket->priority] ?? 'bg-themeHover text-themeBody' }}">{{ ucfirst($ticket->priority) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'open' => 'bg-sky-100 text-sky-800',
                                            'in_progress' => 'bg-amber-100 text-amber-800',
                                            'resolved' => 'bg-emerald-100 text-emerald-800',
                                            'closed' => 'bg-themeHover text-themeBody',
                                        ];
                                    @endphp
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-themeHover text-themeBody' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">
                                        {{ $ticket->created_at->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if (auth()->user()?->hasPermission('tickets.view'))
                                        <a href="{{ route('tickets.show', $ticket) }}"
                                            class="font-medium text-primary hover:text-primary-dark">View</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isCustomer() ? '7' : '8' }}"
                                    class="px-6 py-12 text-center text-themeMuted font-medium">No tickets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
