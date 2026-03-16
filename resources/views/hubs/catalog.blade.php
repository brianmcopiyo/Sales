@extends('layouts.app')

@section('title', 'Catalog')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Catalog</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Products, brands, and pricing</p>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <a href="{{ route('products.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Products</div>
                <div class="text-2xl font-semibold text-primary mt-1">{{ $stats['products_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['products_active'] }} active</div>
            </a>
            <a href="{{ route('products.index', ['status' => 'active']) }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Active Products</div>
                <div class="text-2xl font-semibold text-emerald-600 mt-1">{{ $stats['products_active'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">of {{ $stats['products_total'] }} total</div>
            </a>
            <a href="{{ route('brands.index') }}" 
                class="filter-card bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] cursor-pointer transition-all hover:shadow-lg hover:border-primary/30">
                <div class="text-xs font-medium text-themeMuted uppercase tracking-wider">Brands</div>
                <div class="text-2xl font-semibold text-violet-600 mt-1">{{ $stats['brands_total'] }}</div>
                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $stats['brands_active'] }} active</div>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (auth()->user()?->hasPermission('products.view'))
                @include('hubs._hub-card', [
                    'href' => route('products.index'),
                    'title' => 'Products',
                    'description' => 'Product catalog and SKUs',
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                ])
            @endif
            @if (auth()->user()?->hasPermission('brands.view'))
                @include('hubs._hub-card', [
                    'href' => route('brands.index'),
                    'title' => 'Brands',
                    'description' => 'Product brands and categories',
                    'icon' =>
                        'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                ])
            @endif
            @if (auth()->user()?->hasPermission('products.pricing'))
                @include('hubs._hub-card', [
                    'href' => route('product-pricing.index'),
                    'title' => 'Product Pricing',
                    'description' => 'Regional pricing by product',
                    'icon' =>
                        'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 12v2m-7-6a7 7 0 1114 0 7 7 0 01-14 0z',
                ])
            @endif
        </div>

        {{-- Recent activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @if (auth()->user()?->hasPermission('products.view') && $recentProducts->isNotEmpty())
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Products</h2>
                        <a href="{{ route('products.index') }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                    </div>
                    <ul class="divide-y divide-themeBorder" id="products-list">
                        @foreach ($recentProducts as $product)
                            <li class="px-6 py-3 hover:bg-themeHover/50 transition">
                                <a href="{{ route('products.show', $product) }}"
                                    class="flex justify-between items-center gap-2">
                                    <span
                                        class="text-sm font-medium text-themeHeading truncate">{{ $product->name }}</span>
                                    <span
                                        class="shrink-0 px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                                </a>
                                <div class="text-xs font-medium text-themeMuted mt-0.5">{{ $product->sku }} ·
                                    {{ $product->brand?->name ?? '-' }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (auth()->user()?->hasPermission('brands.view') && $recentBrands->isNotEmpty())
                <div
                    class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <div class="px-6 py-4 border-b border-themeBorder flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-primary tracking-tight">Recent Brands</h2>
                        <a href="{{ route('brands.index') }}"
                            class="text-sm font-medium text-primary hover:text-primary-dark">View all</a>
                    </div>
                    <ul class="divide-y divide-themeBorder" id="brands-list">
                        @foreach ($recentBrands as $brand)
                            <li class="px-6 py-3 hover:bg-themeHover/50 transition">
                                <a href="{{ route('brands.show', $brand) }}"
                                    class="flex justify-between items-center gap-2">
                                    <span class="text-sm font-medium text-themeHeading truncate">{{ $brand->name }}</span>
                                    <span
                                        class="shrink-0 px-2.5 py-0.5 rounded-lg text-xs font-medium {{ $brand->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

@endsection
