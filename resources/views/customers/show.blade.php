@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $customer->name }}</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Customer profile</p>
                @if(isset($userBranch) && $userBranch)
                    <p class="text-sm font-medium text-themeBody mt-1">Your branch: <span class="font-semibold text-primary">{{ $userBranch->name }}</span></p>
                @endif
            </div>
            <a href="{{ route('customers.index') }}"
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
                <!-- Basic Information Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Basic Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                            <div class="text-base font-medium text-themeHeading">{{ $customer->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Email</div>
                            <div class="text-base font-medium text-themeHeading">{{ $customer->email ?? '—' }}</div>
                        </div>
                        @if ($customer->phone)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Phone</div>
                                <div class="text-base font-medium text-themeHeading">{{ $customer->phone }}</div>
                            </div>
                        @endif
                        @if ($customer->id_number)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">ID Number</div>
                                <div class="text-base font-medium text-themeHeading">{{ $customer->id_number }}</div>
                            </div>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $customer->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                {{ $customer->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @if ($customer->address)
                            <div class="md:col-span-2">
                                <div class="text-sm font-medium text-themeMuted mb-1">Address</div>
                                <div class="text-base font-medium text-themeHeading">{{ $customer->address }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Devices Card -->
                @if ($customer->devices->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Assigned Devices</h2>
                        <div class="space-y-4">
                            @foreach ($customer->devices as $device)
                                <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm font-medium text-themeMuted mb-1">IMEI</div>
                                            <div class="text-base font-semibold text-themeHeading">{{ $device->imei }}</div>
                                            <div class="text-sm font-medium text-themeBody mt-1">
                                                {{ $device->product->name }}</div>
                                        </div>
                                        @php
                                            $statusClass =
                                                $device->status === 'sold'
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : ($device->status === 'assigned'
                                                        ? 'bg-sky-100 text-sky-800'
                                                        : 'bg-themeHover text-themeBody');
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium {{ $statusClass }}">
                                            {{ ucfirst($device->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Sales History Card -->
                @if ($customer->sales->count() > 0)
                    <div
                        class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Sales History</h2>
                        <div class="space-y-4">
                            @foreach ($customer->sales->take(10) as $sale)
                                <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm font-medium text-themeMuted mb-1">Sale
                                                #{{ $sale->sale_number }}</div>
                                            <div class="text-base font-semibold text-primary">{{ $currencySymbol }}
                                                {{ number_format($sale->total, 2) }}</div>
                                            <div class="text-sm font-medium text-themeBody mt-1">
                                                {{ $sale->created_at->format('M d, Y') }}</div>
                                        </div>
                                        <a href="{{ route('sales.show', $sale) }}"
                                            class="font-medium text-primary hover:text-primary-dark text-sm">View</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Links Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        <a href="{{ route('customers.edit', $customer) }}"
                            class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            <span>Edit Customer</span>
                        </a>
                        <a href="{{ route('tickets.create', ['customer_id' => $customer->id]) }}"
                            class="block w-full bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                </path>
                            </svg>
                            <span>Create Ticket</span>
                        </a>
                    </div>
                </div>

                <!-- Stats Card -->
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Devices</div>
                            <div class="text-2xl font-semibold text-primary">{{ $customer->devices->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total Sales</div>
                            <div class="text-2xl font-semibold text-amber-600">{{ $customer->sales->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Total In Sales</div>
                            <div class="text-2xl font-semibold text-amber-600">{{ $currencySymbol }}
                                {{ number_format($salesStats['revenue'] ?? $customer->sales->sum('total'), 2) }}</div>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

