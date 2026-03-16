@extends('layouts.app')

@section('title', $brand->name)

@section('content')
<div class="w-full">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $brand->name }}</h1>
        <a href="{{ route('brands.index') }}" class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Back</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Name</div>
                        <div class="text-base font-semibold text-themeHeading">{{ $brand->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Status</div>
                        <span class="px-3 py-1 text-sm font-medium rounded-lg {{ $brand->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                            {{ $brand->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @if($brand->description)
                    <div class="md:col-span-2">
                        <div class="text-sm font-medium text-themeMuted mb-1">Description</div>
                        <div class="text-themeBody font-medium whitespace-pre-wrap">{{ $brand->description }}</div>
                    </div>
                    @endif
                </div>
            </div>

            @if($brand->products->count() > 0)
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Products ({{ $brand->products->count() }})</h2>
                <div class="space-y-3">
                    @foreach($brand->products as $product)
                    <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50 hover:bg-themeInput transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-sm font-medium text-themeMuted mb-1">SKU</div>
                                <div class="text-base font-semibold text-themeHeading">{{ $product->name }}</div>
                                <div class="text-sm font-medium text-themeBody mt-1">{{ $product->sku }}</div>
                            </div>
                            <a href="{{ route('products.show', $product) }}" class="text-sm font-medium text-primary hover:text-primary-dark transition">View</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Quick Links</h2>
                <div class="space-y-2">
                    <a href="{{ route('brands.edit', $brand) }}" class="block w-full bg-themeInput text-themeBody px-4 py-2.5 rounded-xl hover:bg-themeHover hover:text-primary transition font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Edit Brand</span>
                    </a>
                </div>
            </div>

            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Statistics</h2>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-themeMuted mb-1">Total Products</div>
                        <div class="text-2xl font-semibold text-themeHeading tracking-tight">{{ $brand->products->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

