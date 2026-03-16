@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
    <div class="w-full">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Activity Logs</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Audit trail of system actions</p>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Total Logs</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['total'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">Today</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['today'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">This Week</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['this_week'] }}</div>
            </div>
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <div class="text-sm font-medium text-themeMuted mb-1">This Month</div>
                <div class="text-2xl font-semibold text-primary">{{ $stats['this_month'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6 mb-6">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="user_id" class="block text-themeBody font-medium mb-2">User</label>
                    <select id="user_id" name="user_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="action" class="block text-themeBody font-medium mb-2">Action</label>
                    <select id="action" name="action"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">All Actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $action)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-themeBody font-medium mb-2">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>

                <div>
                    <label for="date_to" class="block text-themeBody font-medium mb-2">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>

                <div class="md:col-span-4 flex space-x-4">
                    <button type="submit"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if (request()->hasAny(['user_id', 'action', 'date_from', 'date_to']))
                        <a href="{{ route('activity-logs.index') }}"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12">
                                </path>
                            </svg>
                            <span>Clear</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Activity Logs Table -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] overflow-hidden">
            {{-- Mobile: list --}}
            <div class="md:hidden divide-y divide-themeBorder">
                @forelse($activityLogs as $log)
                    <div class="px-4 py-4 hover:bg-themeInput/50"><div class="text-sm font-semibold text-primary">{{ $log->user?->name ?? 'System' }}</div><div class="text-xs text-themeBody">{{ $log->action ?? '—' }}</div><div class="text-xs text-themeMuted">{{ $log->created_at?->format('M d, Y H:i') ?? '—' }}</div></div>
                @empty
                    <div class="px-4 py-12 text-center text-themeMuted">No activity logs found.</div>
                @endforelse
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Action</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Description</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Time</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                Link</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($activityLogs as $log)
                            <tr class="hover:bg-themeHover/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($log->user)
                                        <div class="flex items-center gap-3">
                                            <x-profile-picture :user="$log->user" size="sm" />
                                            <div>
                                                <div class="text-sm font-medium text-themeHeading">{{ $log->user->name }}
                                                </div>
                                                <div class="text-xs font-medium text-themeMuted">{{ $log->user->email }}
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-sm font-medium text-themeMuted">N/A</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-sky-100 text-sky-800">
                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-themeHeading">{{ $log->description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeBody">{{ $log->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs font-medium text-themeMuted">{{ $log->created_at->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($log->model)
                                        @php
                                            $routeName = match ($log->model_type) {
                                                'App\Models\Sale' => 'sales.show',
                                                'App\Models\StockTransfer' => 'stock-transfers.show',
                                                'App\Models\Ticket' => 'tickets.show',
                                                'App\Models\User' => 'users.show',
                                                default => null,
                                            };
                                        @endphp
                                        @if ($routeName)
                                            <a href="{{ route($routeName, $log->model_id) }}"
                                                class="text-primary hover:text-primary-dark font-medium flex items-center justify-end space-x-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>
                                                </svg>
                                                <span>View</span>
                                            </a>
                                        @else
                                            <span class="text-themeMuted">-</span>
                                        @endif
                                    @else
                                        <span class="text-themeMuted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-themeMuted font-medium">
                                    No activity logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($activityLogs->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $activityLogs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

