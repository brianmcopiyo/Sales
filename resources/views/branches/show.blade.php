@extends('layouts.app')

@section('title', $branch->name)

@section('content')
    <div class="w-full">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $branch->name }}</h1>
            <a href="{{ route('branches.index') }}"
                class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Basic Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Code</div>
                            <div class="text-base font-semibold text-themeHeading">{{ $branch->code }}</div>
                        </div>
                        @if ($branch->region)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Region</div>
                                <div class="text-base font-semibold text-themeHeading">{{ $branch->region->name }}</div>
                            </div>
                        @endif
                        @if ($branch->headBranch)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Head Branch</div>
                                <div class="text-base font-semibold text-themeHeading">{{ $branch->headBranch->name }}</div>
                            </div>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            <span
                                class="px-3 py-1 text-sm font-medium rounded-lg {{ $branch->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                {{ $branch->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>

                @if ($branch->address || $branch->phone || $branch->email)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Contact Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if ($branch->address)
                                <div class="md:col-span-2">
                                    <div class="text-sm font-medium text-themeMuted mb-1">Address</div>
                                    <div class="text-themeBody font-medium">{{ $branch->address }}</div>
                                </div>
                            @endif
                            @if ($branch->phone)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Phone</div>
                                    <div class="text-base font-semibold text-themeHeading">{{ $branch->phone }}</div>
                                </div>
                            @endif
                            @if ($branch->email)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Email</div>
                                    <div class="text-base font-semibold text-themeHeading">{{ $branch->email }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($branch->regionalBranches->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Regional Branches</h2>
                        <div class="space-y-2">
                            @foreach ($branch->regionalBranches as $regionalBranch)
                                <div class="flex justify-between items-center py-2 border-b border-themeBorder last:border-0">
                                    <div class="text-themeHeading font-medium">{{ $regionalBranch->name }}</div>
                                    <a href="{{ route('branches.show', $regionalBranch) }}"
                                        class="text-sm font-medium text-primary hover:text-primary-dark transition">View</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Users
                            ({{ $branch->users->count() }})</h2>
                        <div class="flex items-center gap-3">
                            @if (isset($availableUsers) && $availableUsers->count() > 0)
                                <a href="{{ route('branches.add-users', $branch) }}"
                                    class="text-sm font-medium bg-primary text-white px-3 py-2 rounded-xl hover:bg-primary-dark transition shadow-sm inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Add Users</span>
                                </a>
                            @endif
                            <a href="{{ route('users.index') }}?branch={{ $branch->id }}"
                                class="text-sm font-medium text-primary hover:text-primary-dark transition">View All</a>
                        </div>
                    </div>
                    @if ($branch->users->count() > 0)
                        <div class="overflow-x-auto rounded-xl border border-themeBorder">
                            <table class="min-w-full divide-y divide-themeBorder">
                                <thead class="bg-themeInput/80">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Name</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Email</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Role</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Phone</th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-themeCard divide-y divide-themeBorder">
                                    @foreach ($branch->users as $user)
                                        <tr class="hover:bg-themeInput/50">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <x-profile-picture :user="$user" size="sm" />
                                                    <div class="text-sm font-medium text-themeHeading">{{ $user->name }}</div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm font-medium text-themeBody">{{ $user->email }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @php
                                                    $roleSlug = $user->roleModel?->slug ?? $user->role;
                                                    $roleName = $user->roleModel?->name ?? ucfirst(str_replace('_', ' ', $user->role));
                                                    $roleClass = $roleSlug === 'admin' || $roleSlug === 'super_admin' ? 'bg-violet-100 text-violet-800' : (in_array($roleSlug, ['head_branch_manager', 'regional_branch_manager'], true) ? 'bg-sky-100 text-sky-800' : 'bg-themeHover text-themeBody');
                                                @endphp
                                                <span class="px-2.5 py-1 text-xs font-medium rounded-lg {{ $roleClass }}">
                                                    {{ $roleName }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm font-medium text-themeBody">{{ $user->phone ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                                <a href="{{ route('users.show', $user) }}"
                                                    class="font-medium text-primary hover:text-primary-dark transition">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-themeMuted" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                            <p class="mt-2 text-sm font-medium text-themeMuted">No users assigned to this branch</p>
                        </div>
                    @endif
                </div>

                {{-- Products at this branch (from branch stock) --}}
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Products at this branch</h2>
                        <a href="{{ route('branch-stocks.index') }}?branch_id={{ $branch->id }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark transition">View all stock</a>
                    </div>
                    @if (isset($branchStocks) && $branchStocks->isNotEmpty())
                        <div class="overflow-x-auto rounded-xl border border-themeBorder">
                            <table class="min-w-full divide-y divide-themeBorder">
                                <thead class="bg-themeInput/80">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Product</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">SKU</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Quantity</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Reserved</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Available</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-themeCard divide-y divide-themeBorder">
                                    @foreach ($branchStocks as $bs)
                                        <tr class="hover:bg-themeInput/50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $bs->product?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-themeBody">{{ $bs->product?->sku ?? '-' }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-themeBody text-right">{{ $bs->display_quantity }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-themeBody text-right">{{ $bs->reserved_quantity }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-themeHeading text-right">{{ $bs->available_quantity }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                                @if ($bs->product)
                                                    <a href="{{ route('products.show', $bs->product) }}" class="font-medium text-primary hover:text-primary-dark transition">View</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-sm font-medium text-themeMuted">No product stock at this branch</p>
                            <a href="{{ route('branch-stocks.index') }}?branch_id={{ $branch->id }}" class="mt-2 inline-block text-sm font-medium text-primary hover:text-primary-dark">View stock</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        <a href="{{ route('branches.edit', $branch) }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            <span>Edit Branch</span>
                        </a>
                        <a href="{{ route('branch-stocks.index') }}?branch_id={{ $branch->id }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span>View Stock</span>
                        </a>
                        <a href="{{ route('sales.index') }}?branch={{ $branch->id }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>View Sales</span>
                        </a>
                        <a href="{{ route('stock-transfers.index') }}?branch={{ $branch->id }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            <span>View Transfers</span>
                        </a>
                    </div>
                </div>

                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Users</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $branch->users->count() }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Stock Items</div>
                            <div class="text-2xl font-semibold text-themeHeading tracking-tight">
                                {{ \App\Models\BranchStock::where('branch_id', $branch->id)->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Sales</div>
                            <div class="text-2xl font-semibold text-amber-600 tracking-tight">
                                {{ \App\Models\Sale::where('branch_id', $branch->id)->where('status', 'completed')->count() }}
                            </div>
                        </div>
                        @if ($branch->regionalBranches->count() > 0)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Regional Branches</div>
                                <div class="text-2xl font-semibold text-amber-600 tracking-tight">
                                    {{ $branch->regionalBranches->count() }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

