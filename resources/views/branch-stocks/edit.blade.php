@extends('layouts.app')

@section('title', 'Edit Branch Stock')

@section('content')
<div class="w-full space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Branch Stock</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">{{ $branchStock->product->name }} at {{ $branchStock->branch->name }}</p>
        </div>
        <a href="{{ route('branch-stocks.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Back</span>
        </a>
    </div>

    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="text-sm font-medium text-themeMuted mb-1">Branch</div>
                <div class="text-lg font-semibold text-themeHeading">{{ $branchStock->branch->name }}</div>
            </div>

            <div>
                <div class="text-sm font-medium text-themeMuted mb-1">Product</div>
                <div class="text-lg font-semibold text-themeHeading">{{ $branchStock->product->name }}</div>
                <div class="text-sm font-medium text-themeMuted">{{ $branchStock->product->sku }}</div>
            </div>
        </div>
    </div>

    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('branch-stocks.update', $branchStock) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="quantity" class="block text-sm font-medium text-themeBody mb-2">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $branchStock->display_quantity) }}" required min="0"
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                </div>

                <div>
                    <label class="block text-sm font-medium text-themeBody mb-2">Minimum Stock Level</label>
                    <div class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput/80 text-themeBody font-medium">
                        {{ $branchStock->product->minimum_stock_level ?? 10 }} (Product Setting)
                    </div>
                </div>
            </div>

            <div class="flex space-x-3">
                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Update Stock</span>
                </button>
                <a href="{{ route('branch-stocks.index') }}" class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span>Cancel</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

