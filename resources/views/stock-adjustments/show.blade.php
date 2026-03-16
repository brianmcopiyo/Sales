@extends('layouts.app')

@section('title', 'Stock Adjustment Details')

@section('content')
<div class="w-full space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Stock Adjustment #{{ $stockAdjustment->adjustment_number }}</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">{{ $stockAdjustment->product->name }} • {{ $stockAdjustment->branch->name }}</p>
        </div>
        <a href="{{ route('stock-adjustments.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Back</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Adjustment Information -->
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Adjustment Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Adjustment Amount</div>
                        <div class="text-2xl font-semibold {{ $stockAdjustment->adjustment_amount >= 0 ? 'text-emerald-600' : 'text-red-600' }} tracking-tight">
                            {{ $stockAdjustment->adjustment_amount >= 0 ? '+' : '' }}{{ $stockAdjustment->adjustment_amount }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Adjustment Type</div>
                        <span class="px-3 py-1.5 text-sm rounded-lg font-medium bg-themeHover text-themeHeading">
                            {{ ucfirst(str_replace('_', ' ', $stockAdjustment->adjustment_type)) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Quantity Before</div>
                        <div class="text-lg font-semibold text-themeHeading">{{ $stockAdjustment->quantity_before }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Quantity After</div>
                        <div class="text-lg font-semibold text-themeHeading">{{ $stockAdjustment->quantity_after }}</div>
                    </div>
                    @if($stockAdjustment->reason)
                    <div class="md:col-span-2">
                        <div class="text-sm font-medium text-themeMuted mb-1">Reason</div>
                        <div class="font-medium text-themeHeading whitespace-pre-wrap">{{ $stockAdjustment->reason }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Product Information -->
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Product</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                        <div class="font-medium text-themeHeading">{{ $stockAdjustment->product->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">SKU</div>
                        <div class="font-medium text-themeHeading">{{ $stockAdjustment->product->sku }}</div>
                    </div>
                </div>
            </div>

            <!-- Branch Information -->
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Branch</h2>
                <div>
                    <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                    <div class="font-medium text-themeHeading">{{ $stockAdjustment->branch->name }}</div>
                </div>
            </div>

            @if($stockAdjustment->stockTake)
            <!-- Related Stock Take -->
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Related Stock Take</h2>
                <div>
                    <a href="{{ route('stock-takes.show', $stockAdjustment->stockTake) }}" class="font-medium text-primary hover:text-primary-dark transition">
                        {{ $stockAdjustment->stockTake->stock_take_number }}
                    </a>
                    <div class="text-sm font-medium text-themeMuted mt-1">{{ $stockAdjustment->stockTake->stock_take_date->format('M d, Y') }}</div>
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            <!-- People & Dates -->
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">People & Dates</h2>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Adjusted By</div>
                        <div class="font-medium text-themeHeading">{{ $stockAdjustment->adjustedBy->name }}</div>
                        <div class="text-xs font-medium text-themeMuted">{{ $stockAdjustment->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @if($stockAdjustment->approved_by)
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Approved By</div>
                        <div class="font-medium text-themeHeading">{{ $stockAdjustment->approvedBy->name }}</div>
                        <div class="text-xs font-medium text-themeMuted">{{ $stockAdjustment->approved_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

