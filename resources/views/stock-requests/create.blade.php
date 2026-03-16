@extends('layouts.app')

@section('title', 'Request stock from another branch')

@section('content')
    <div class="w-full space-y-6">
        @include('partials.back-button', [
            'href' => route('stock-requests.index'),
            'label' => 'Back to Stock Requests',
        ])
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Request stock from another branch</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Ask another branch for stock when you're running low. They can
                approve or reject.</p>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('stock-requests.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-themeBody mb-2">Your branch (requesting)</label>
                        <div
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput font-medium text-themeBody">
                            {{ auth()->user()->branch->name }}
                        </div>
                    </div>

                    <div>
                        <label for="requested_from_branch_id" class="block text-sm font-medium text-themeBody mb-2">Request
                            from branch *</label>
                        <select id="requested_from_branch_id" name="requested_from_branch_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading @error('requested_from_branch_id') border-red-300 @enderror">
                            <option value="">Select branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ old('requested_from_branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }} ({{ $branch->code ?? '' }})
                                </option>
                            @endforeach
                        </select>
                        @error('requested_from_branch_id')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="product_id" class="block text-sm font-medium text-themeBody mb-2">Product *</label>
                        <select id="product_id" name="product_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading @error('product_id') border-red-300 @enderror">
                            <option value="">Select product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->sku ?? '' }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="quantity_requested" class="block text-sm font-medium text-themeBody mb-2">Quantity
                            requested *</label>
                        <input type="number" id="quantity_requested" name="quantity_requested"
                            value="{{ old('quantity_requested') }}" required min="1"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading @error('quantity_requested') border-red-300 @enderror">
                        @error('quantity_requested')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes (optional)</label>
                        <textarea id="notes" name="notes" rows="3" maxlength="1000"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading @error('notes') border-red-300 @enderror"
                            placeholder="e.g. Running low, need by end of week">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Submit request</span>
                    </button>
                    <a href="{{ route('stock-requests.index') }}"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

