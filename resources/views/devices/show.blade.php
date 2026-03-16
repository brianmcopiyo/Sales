@extends('layouts.app')

@section('title', 'Device ' . $device->imei)

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Device: {{ $device->imei }}</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $device->product->name ?? 'Device' }}</p>
            </div>
            <a href="{{ route('devices.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information Card -->
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Basic Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">IMEI</div>
                            <div class="text-base font-semibold text-themeHeading">{{ $device->imei }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Product</div>
                            <div class="text-base font-medium text-themeHeading">{{ $device->product->name }} ({{ $device->product->sku }})</div>
                        </div>
                        @if ($device->branch)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Branch</div>
                                <div class="text-base font-medium text-themeHeading">
                                    <a href="{{ route('branches.show', $device->branch) }}" class="text-primary hover:text-primary-dark">{{ $device->branch->name }}</a>
                                </div>
                            </div>
                        @endif
                        <div>
                            <div class="text-sm font-medium text-themeMuted mb-1">Date added</div>
                            <div class="text-base font-medium text-themeHeading">{{ $device->created_at?->format('M d, Y') ?? '—' }}</div>
                            @if ($device->created_at)
                                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $device->created_at->format('h:i A') }}</div>
                            @endif
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-sm font-medium text-themeMuted mb-2">Status</div>
                            @php
                                $statusClass = $device->status === 'sold' ? 'bg-emerald-100 text-emerald-800' : ($device->status === 'assigned' ? 'bg-sky-100 text-sky-800' : 'bg-themeHover text-themeBody');
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $statusClass }} mb-3">
                                {{ ucfirst($device->status) }}
                            </span>
                            @if ($device->isSold())
                                <p class="text-xs font-medium text-themeMuted mt-2">Status cannot be changed once sold.</p>
                                @if ($device->soldBy)
                                    <p class="text-xs font-medium text-themeMuted mt-1">Recorded as sold by: {{ $device->soldBy->name }}</p>
                                @endif
                            @else
                                <p class="text-xs font-medium text-themeMuted mb-2">Change status (records who performed the action):</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (['available' => 'Available', 'assigned' => 'Assigned'] as $statusValue => $statusLabel)
                                        @if ($device->status === $statusValue)
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-medium bg-themeHover text-themeMuted cursor-default">{{ $statusLabel }} (current)</span>
                                        @else
                                            <form method="POST" action="{{ route('devices.status.update', $device) }}" class="inline" onsubmit="return confirm('Mark this device as {{ $statusLabel }}?');">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $statusValue }}">
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-medium {{ $statusValue === 'assigned' ? 'bg-sky-100 text-sky-800 hover:bg-sky-200' : 'bg-themeHover text-themeBody hover:bg-themeBorder' }} transition">
                                                    Mark as {{ $statusLabel }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach
                                    <a href="{{ route('devices.mark-sold.form', $device) }}" class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-medium bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition">
                                        Mark as sold (create sale)
                                    </a>
                                    @if (auth()->user()?->branch_id && (int) $device->branch_id === (int) auth()->user()->branch_id)
                                        <a href="{{ route('stock-transfers.create', ['product_id' => $device->product_id, 'quantity' => 1, 'imei' => $device->imei]) }}" class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-medium bg-sky-100 text-sky-800 hover:bg-sky-200 transition">
                                            Transfer to another branch
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if ($device->customer)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Customer</div>
                                <div class="text-base font-medium text-themeHeading">
                                    <a href="{{ route('customers.show', $device->customer) }}" class="font-medium text-primary hover:text-primary-dark">
                                        {{ $device->customer->name }}
                                    </a>
                                </div>
                            </div>
                        @endif
                        @if ($device->sale)
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Sale</div>
                                <div class="text-base font-medium text-themeHeading">
                                    <a href="{{ route('sales.show', $device->sale) }}" class="font-medium text-primary hover:text-primary-dark">
                                        {{ $device->sale->sale_number }}
                                    </a>
                                </div>
                            </div>
                        @endif
                        @if ($device->saleItem && $device->isSold())
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Sale Price</div>
                                <div class="text-base font-semibold text-primary">TSh {{ number_format($device->saleItem->unit_price, 2) }}</div>
                            </div>
                        @endif
                        @if ($device->notes)
                            <div class="md:col-span-2">
                                <div class="text-sm font-medium text-themeMuted mb-1">Notes</div>
                                <div class="text-base font-medium text-themeBody whitespace-pre-wrap">{{ $device->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Payment Information Card (only for sold devices) -->
                @if ($device->isSold() && $device->sale && $device->saleItem)
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Payment Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">Sale Amount</div>
                                <div class="text-2xl font-semibold text-primary">TSh {{ number_format($device->saleItem->unit_price, 2) }}</div>
                            </div>
                            @if ($device->sale)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Sale Date</div>
                                    <div class="text-base font-medium text-themeHeading">{{ $device->sale->created_at->format('M d, Y') }}</div>
                                    <div class="text-sm font-medium text-themeMuted">{{ $device->sale->created_at->format('h:i A') }}</div>
                                </div>
                            @endif
                            @if ($device->sale)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Total Sale Amount</div>
                                    <div class="text-base font-medium text-themeHeading">TSh {{ number_format($device->sale->total, 2) }}</div>
                                    <div class="text-xs font-medium text-themeMuted mt-1">(Including tax and discount)</div>
                                </div>
                            @endif
                            @if ($device->sale && $device->sale->soldBy)
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Sold By</div>
                                    <div class="text-base font-medium text-themeHeading">{{ $device->sale->soldBy->name }}</div>
                                </div>
                            @endif
                            @if ($device->sale)
                                @php
                                    $deviceDisbursements = $device->sale->customerDisbursements->where('device_id', $device->id);
                                    $disbursedAmount = $deviceDisbursements->where('status', \App\Models\CustomerDisbursement::STATUS_APPROVED)->sum('amount');
                                    $hasApproved = $deviceDisbursements->contains('status', \App\Models\CustomerDisbursement::STATUS_APPROVED);
                                    $hasPending = $deviceDisbursements->contains('status', \App\Models\CustomerDisbursement::STATUS_PENDING);
                                    $hasRejected = $deviceDisbursements->contains('status', \App\Models\CustomerDisbursement::STATUS_REJECTED);
                                    $disbursementStatus = $deviceDisbursements->isEmpty() ? null : ($hasApproved ? 'approved' : ($hasPending ? 'pending' : 'rejected'));
                                @endphp
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Disbursed Amount</div>
                                    @if ($disbursedAmount > 0)
                                        <div class="text-base font-medium text-themeHeading">TSh {{ number_format($disbursedAmount, 2) }}</div>
                                    @else
                                        <div class="text-base font-medium text-themeMuted">None</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-themeMuted mb-1">Disbursement Status</div>
                                    @if ($disbursementStatus === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Approved</span>
                                    @elseif ($disbursementStatus === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">Pending</span>
                                    @elseif ($disbursementStatus === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                    @else
                                        <div class="text-base font-medium text-themeMuted">None</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Links Card -->
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                    <div class="space-y-2">
                        @if (!$device->isSold())
                            <a href="{{ route('devices.edit', $device) }}"
                                class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit Device</span>
                            </a>
                        @endif
                        @if ($device->product)
                            <a href="{{ route('products.show', $device->product) }}"
                                class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeHover transition flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <span>View Product</span>
                            </a>
                        @endif
                    </div>
                </div>

                @if ($device->statusLogs->isNotEmpty())
                    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                        <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Status history</h2>
                        <ul class="space-y-2 text-sm">
                            @foreach ($device->statusLogs->take(10) as $log)
                                <li class="py-2 border-b border-themeBorder last:border-0 text-themeBody">
                                    <span class="font-medium">{{ ucfirst($log->status) }}</span>
                                    <span class="text-themeMuted"> · {{ $log->created_at->format('M d, Y H:i') }} · by {{ $log->performedBy?->name ?? '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

