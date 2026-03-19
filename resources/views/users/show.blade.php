@extends('layouts.app')

@section('title', $user->name)

@section('content')
    <div class="w-full">
        <div class="mb-6 flex justify-between items-center flex-wrap gap-3">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $user->name }}</h1>
                @if ($user->isSuspended())
                    <span class="px-3 py-1.5 text-sm rounded-lg font-medium bg-amber-100 text-amber-800">Suspended</span>
                @endif
            </div>
            <a href="{{ route('users.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Picture & Basic Information Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                    <div class="flex items-center gap-6 mb-6">
                        <x-profile-picture :user="$user" size="xl" />
                        <div>
                            <h2 class="text-2xl font-semibold text-primary mb-1">{{ $user->name }}</h2>
                            <p class="text-sm text-themeMuted">{{ $user->email }}</p>
                        </div>
                    </div>
                    <h2 class="text-xl font-semibold text-primary mb-4">Basic Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                            <div class="text-lg font-medium text-themeHeading">{{ $user->name }}</div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Email</div>
                            <div class="font-medium text-themeHeading">{{ $user->email }}</div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Role</div>
                            <div class="flex items-center gap-2 flex-wrap">
                                @php
                                    $roleSlug = $user->roleModel?->slug ?? $user->role;
                                    $roleDisplayName = $user->roleModel?->name ?? ucfirst(str_replace('_', ' ', $user->role));
                                    $roleClass = $roleSlug === 'admin' || $roleSlug === 'super_admin' ? 'bg-violet-100 text-violet-800' : ($roleSlug === 'head_branch_manager' ? 'bg-sky-100 text-sky-800' : ($roleSlug === 'regional_branch_manager' ? 'bg-indigo-100 text-indigo-800' : ($roleSlug === 'staff' ? 'bg-themeHover text-themeBody' : 'bg-emerald-100 text-emerald-800')));
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $roleClass }}">{{ $roleDisplayName }}</span>
                                @if ($user->isSuspended())
                                    <span class="px-2.5 py-1 text-sm rounded-lg font-medium bg-amber-100 text-amber-800">Suspended</span>
                                    @if ($user->suspended_at)
                                        <span class="text-xs text-themeMuted">Since {{ $user->suspended_at->format('M d, Y') }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if ($user->branch)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Branch</div>
                                <div class="font-medium text-themeHeading">{{ $user->branch->name }}</div>
                            </div>
                        @endif

                        @if ($user->phone)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Phone</div>
                                <div class="font-medium text-themeHeading">{{ $user->phone }}</div>
                            </div>
                        @endif

                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Member Since</div>
                            <div class="font-medium text-themeHeading">{{ $user->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Sales History Card -->
                @if ($user->sales->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                        <h2 class="text-xl text-primary font-light mb-4">Sales Made</h2>
                        <div class="space-y-4">
                            @foreach ($user->sales->take(10) as $sale)
                                <div class="border border-themeBorder rounded p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm text-themeMuted font-light mb-1">Sale
                                                #{{ $sale->sale_number }}</div>
                                            <div class="text-lg text-themeHeading font-light">{{ $currencySymbol }}
                                                {{ number_format($sale->total, 2) }}</div>
                                            <div class="text-sm text-themeBody font-light mt-1">
                                                {{ $sale->created_at->format('M d, Y') }}</div>
                                        </div>
                                        <a href="{{ route('sales.show', $sale) }}"
                                            class="text-primary hover:text-[#005a61] font-light text-sm">View</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Assigned Tickets Card -->
                @if ($user->assignedTickets->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                        <h2 class="text-xl font-semibold text-primary mb-4">Assigned Tickets</h2>
                        <div class="space-y-4">
                            @foreach ($user->assignedTickets->take(10) as $ticket)
                                <div class="border border-themeBorder rounded-xl p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm font-medium text-themeMuted mb-1">Ticket
                                                #{{ $ticket->ticket_number }}</div>
                                            <div class="text-lg font-medium text-themeHeading">{{ $ticket->subject }}</div>
                                            <div class="text-sm font-medium text-themeBody mt-1">
                                                {{ $ticket->created_at->format('M d, Y') }}</div>
                                        </div>
                                        <a href="{{ route('tickets.show', $ticket) }}"
                                            class="font-medium text-primary hover:text-primary-dark text-sm">View</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Activity Logs Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                    <h2 class="text-xl font-semibold text-primary mb-4">Activity Logs</h2>
                    @if ($activityLogs->count() > 0)
                        <div class="space-y-3">
                            @foreach ($activityLogs as $log)
                                <div class="border-l-4 border-[#006F78] pl-4 py-2">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-themeHeading">{{ $log->description }}</div>
                                            <div class="text-xs font-medium text-themeMuted mt-1">
                                                {{ $log->created_at->format('M d, Y h:i A') }}</div>
                                        </div>
                                        @if ($log->model)
                                            <div class="ml-4">
                                                @php
                                                    $routeName = match ($log->model_type) {
                                                        'App\Models\Sale' => 'sales.show',
                                                        'App\Models\StockTransfer' => 'stock-transfers.show',
                                                        'App\Models\Ticket' => 'tickets.show',
                                                        default => null,
                                                    };
                                                @endphp
                                                @if ($routeName)
                                                    <a href="{{ route($routeName, $log->model_id) }}"
                                                        class="font-medium text-primary hover:text-primary-dark text-xs">
                                                        View
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            {{ $activityLogs->links() }}
                        </div>
                    @else
                        <div class="text-center py-8 text-themeMuted font-medium">
                            No activity logs found.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Links Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                    <h2 class="text-xl font-semibold text-primary mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        <a href="{{ route('users.edit', $user) }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            <span>Edit User</span>
                        </a>

                        @if (auth()->user()?->hasPermission('users.update') && auth()->id() !== $user->id)
                            <form method="POST" action="{{ route('users.change-branch', $user) }}" class="space-y-2">
                                @csrf
                                <label for="change-branch-select" class="block text-sm font-medium text-themeMuted mb-1">Change branch</label>
                                <div class="flex gap-2">
                                    <select name="branch_id" id="change-branch-select"
                                        class="flex-1 bg-themeInput text-themeBody border border-themeBorder rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                                        <option value="">No branch</option>
                                        @foreach ($branchesForBranchChange as $branch)
                                            <option value="{{ $branch->id }}" {{ (int) $user->branch_id === (int) $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                        class="bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition text-sm whitespace-nowrap">
                                        Update
                                    </button>
                                </div>
                                @error('branch_id')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </form>
                        @endif

                        @if (auth()->user()?->hasPermission('users.update'))
                            <button type="button"
                                onclick="document.getElementById('set-password-modal').classList.remove('hidden')"
                                class="w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                                    </path>
                                </svg>
                                <span>Set password</span>
                            </button>
                        @endif

                        @if (auth()->user()?->hasPermission('users.update') && auth()->id() !== $user->id)
                            @if ($user->isSuspended())
                                <form method="POST" action="{{ route('users.unsuspend', $user) }}"
                                    onsubmit="return confirm('Unsuspend this user? They will be able to log in again.');">
                                    @csrf
                                    <button type="submit"
                                        class="w-full bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-emerald-700 transition shadow-sm flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Unsuspend user</span>
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('users.suspend', $user) }}"
                                    onsubmit="return confirm('Suspend this user? They will not be able to log in until unsuspended.');">
                                    @csrf
                                    <button type="submit"
                                        class="w-full bg-amber-500 text-white px-4 py-2.5 rounded-xl font-medium hover:bg-amber-600 transition shadow-sm flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                        <span>Suspend user</span>
                                    </button>
                                </form>
                            @endif
                        @endif

                        @if (auth()->user()?->hasPermission('users.manage-field-agents') && !$isFieldAgent && $user->role !== 'customer')
                            <form method="POST" action="{{ route('users.make-field-agent', $user) }}"
                                onsubmit="return confirm('Convert this user into a field agent?')">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Convert to field agent</span>
                                </button>
                            </form>
                        @elseif($isFieldAgent)
                            <a href="{{ route('field-agents.show', $user->id) }}"
                                class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                                <span>View Field Agent Profile</span>
                            </a>

                        @endif
                    </div>
                </div>

                <!-- Stats Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] p-6">
                    <h2 class="text-xl font-semibold text-primary mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Sales</div>
                            <div class="text-2xl font-semibold text-primary">{{ $user->sales->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total In Sales</div>
                            <div class="text-2xl font-semibold text-amber-600">{{ $currencySymbol }}
                                {{ number_format($salesStats['revenue'] ?? $user->sales->sum('total'), 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Cost to sell</div>
                            <div class="text-2xl font-semibold text-themeHeading">{{ $currencySymbol }}
                                {{ number_format($salesStats['cost_to_sell'] ?? 0, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Gross profit</div>
                            <div class="text-2xl font-semibold text-emerald-600">{{ $currencySymbol }}
                                {{ number_format($salesStats['profit'] ?? 0, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Assigned Tickets</div>
                            <div class="text-2xl font-semibold text-themeHeading">{{ $user->assignedTickets->count() }}</div>
                        </div>

                        @if ($isFieldAgent && auth()->id() === $user->id && $commissionStats)
                            <div class="pt-4 border-t border-themeBorder">
                                <div class="text-sm font-medium text-themeMuted mb-2">Commission</div>
                                <div class="space-y-2">
                                    <div>
                                        <div class="text-xs font-medium text-themeMuted">Total Earned</div>
                                        <div class="text-lg font-semibold text-primary">{{ $currencySymbol }}
                                            {{ number_format($commissionStats['total_earned'], 2) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium text-themeMuted">Available Balance</div>
                                        <div class="text-lg font-semibold text-amber-600">{{ $currencySymbol }}
                                            {{ number_format($commissionStats['available_balance'], 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Set password modal -->
        @if (auth()->user()?->hasPermission('users.update'))
            <div id="set-password-modal"
                class="{{ $errors->has('password') ? '' : 'hidden' }} fixed inset-0 overflow-y-auto h-full w-full z-50 bg-black/50 flex items-center justify-center p-4"
                onclick="if(event.target === this) document.getElementById('set-password-modal').classList.add('hidden')">
                <div class="relative w-full max-w-md rounded-2xl border border-themeBorder bg-themeCard p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]"
                    onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between mb-4 pb-4 border-b border-themeBorder">
                        <h3 class="text-lg font-semibold text-primary tracking-tight">Set password</h3>
                        <button type="button"
                            onclick="document.getElementById('set-password-modal').classList.add('hidden')"
                            class="text-themeMuted hover:text-themeBody focus:outline-none rounded-lg p-1">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <form method="POST" action="{{ route('users.reset-password', $user) }}"
                            onsubmit="return confirm('Generate a new password and send it to this user\'s email and/or phone?');">
                            @csrf
                            <button type="submit"
                                class="w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Generate new password</span>
                            </button>
                        </form>
                        <p class="text-xs text-themeMuted text-center">Or set a custom password:</p>
                        <form method="POST" action="{{ route('users.set-password', $user) }}" class="space-y-3">
                            @csrf
                            <div>
                                <input type="password" name="password" required minlength="8" autocomplete="new-password"
                                    placeholder="New password (min 8 chars)"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                @error('password')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                                placeholder="Confirm password"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <p class="text-xs text-themeMuted">Credentials will be sent to the user's email and SMS when available.</p>
                            <div class="flex gap-2 pt-2">
                                <button type="submit"
                                    class="flex-1 bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition flex items-center justify-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                    <span>Set password</span>
                                </button>
                                <button type="button"
                                    onclick="document.getElementById('set-password-modal').classList.add('hidden')"
                                    class="bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        var modal = document.getElementById('set-password-modal');
                        if (modal && !modal.classList.contains('hidden')) modal.classList.add('hidden');
                    }
                });
            </script>
        @endif
    </div>
@endsection

