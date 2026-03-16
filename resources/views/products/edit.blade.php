@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
<div class="w-full">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Product</h1>
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-4 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Back</span>
        </a>
    </div>

    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-themeBody mb-1">Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('name') border-red-300 @enderror">
                    @error('name')<p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sku" class="block text-sm font-medium text-themeBody mb-1">SKU</label>
                    <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}"
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition @error('sku') border-red-300 @enderror">
                    @error('sku')<p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-themeBody mb-1">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Optional"
                              class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition">{{ old('description', $product->description) }}</textarea>
                </div>
                <div>
                    <label for="brand_id" class="block text-sm font-medium text-themeBody mb-1">Brand *</label>
                    <select id="brand_id" name="brand_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                        <option value="">Select a brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="model" class="block text-sm font-medium text-themeBody mb-1">Model</label>
                    <input type="text" id="model" name="model" value="{{ old('model', $product->model) }}"
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading placeholder-themeMuted focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                </div>
                <div class="md:col-span-2 bg-themeInput/80 border border-themeBorder rounded-xl p-4 text-sm font-medium text-themeBody">
                    Cost and selling prices are managed per region under <span class="text-themeHeading">Inventory Management → Pricing</span>.
                    <a href="{{ route('product-pricing.edit', $product) }}" class="text-primary hover:text-primary-dark transition font-medium">Edit regional pricing for this product</a>.
                </div>
                <div>
                    <label for="license_cost" class="block text-sm font-medium text-themeBody mb-1">License cost (cost to sell per unit)</label>
                    <input type="number" id="license_cost" name="license_cost" value="{{ old('license_cost', $product->license_cost) }}" min="0" step="0.01" placeholder="0.00"
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                    <p class="mt-1 text-xs text-themeMuted">Applied per unit when this product is sold.</p>
                    @error('license_cost')<p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="minimum_stock_level" class="block text-sm font-medium text-themeBody mb-1">Minimum Stock Level</label>
                    <input type="number" id="minimum_stock_level" name="minimum_stock_level" value="{{ old('minimum_stock_level', $product->minimum_stock_level ?? 10) }}" min="0"
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                </div>
                <div>
                    <label for="image" class="block text-sm font-medium text-themeBody mb-1">Image</label>
                    @if($product->image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-32 h-32 object-cover rounded-xl border border-themeBorder">
                        </div>
                    @endif
                    <input type="file" id="image" name="image" accept="image/*"
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-primary/10 file:text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                           class="rounded border-themeBorder text-primary focus:ring-primary/20">
                    <label for="is_active" class="ml-2 text-sm font-medium text-themeBody">Active</label>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Update Product</span>
                </button>
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    <span>Cancel</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

