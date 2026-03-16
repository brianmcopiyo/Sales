@extends('layouts.app')

@section('title', 'Merge Products')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('products.index'),
            'label' => 'Back to Products',
        ])
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Merge Products</h1>
            @if (!empty($recipientFixed))
                <p class="mt-1 text-themeBody">Select one or more products to merge into
                    <strong>{{ $recipient->name }}</strong>. Their devices and sales will be transferred to it, and those
                    products will be removed.
                </p>
            @else
                <p class="mt-1 text-themeBody">Choose which product to keep. All devices and sales from the other selected
                    products will be transferred to it. The other products will be removed.</p>
            @endif
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <p class="text-sm font-medium text-themeBody">This action cannot be undone. Make sure the selected recipient is
                the product you want to keep.</p>
        </div>

        <form method="POST" action="{{ route('products.merge') }}" id="merge-form"
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            @csrf
            @if (!empty($recipientFixed))
                <input type="hidden" name="target_id" value="{{ $recipient->id }}">
                <div class="space-y-4">
                    <p class="text-sm font-semibold text-themeHeading">Keeping: <span
                            class="text-primary">{{ $recipient->name }}</span> ({{ $recipient->sku }})@if ($recipient->brand)
                            — {{ $recipient->brand->name }}
                        @endif
                    </p>
                    <label class="block text-sm font-semibold text-themeHeading">Select products to merge into it (their
                        devices and sales will be transferred):</label>
                    @if ($sources->isNotEmpty())
                        <div class="space-y-2" x-data="{ filter: '' }">
                            <input type="text"
                                x-model="filter"
                                placeholder="Filter by name, SKU or brand..."
                                class="w-full rounded-xl border border-themeBorder bg-themeInput px-4 py-2.5 text-themeBody placeholder-themeMuted focus:border-primary focus:ring-1 focus:ring-primary"
                                aria-label="Filter product list">
                            <ul id="merge-source-list"
                                class="divide-y divide-themeBorder rounded-xl border border-themeBorder overflow-hidden max-h-[400px] overflow-y-auto">
                                @foreach ($sources as $source)
                                    @php
                                        $searchText = Str::lower($source->name . ' ' . $source->sku . ' ' . ($source->brand->name ?? ''));
                                    @endphp
                                    <li class="bg-themeCard hover:bg-themeHover/50 transition"
                                        data-search="{{ e($searchText) }}"
                                        x-show="!filter.trim() || $el.dataset.search.includes(filter.trim().toLowerCase())">
                                        <label class="flex items-center gap-4 px-4 py-3 cursor-pointer">
                                            <input type="checkbox" name="source_ids[]" value="{{ $source->id }}"
                                                class="source-cb rounded border-themeBorder text-primary focus:ring-primary">
                                            <div class="flex-1 min-w-0">
                                                <span class="font-medium text-themeHeading">{{ $source->name }}</span>
                                                <span class="text-themeMuted text-sm ml-2">({{ $source->sku }})</span>
                                                @if ($source->brand)
                                                    <span class="text-themeMuted text-sm"> — {{ $source->brand->name }}</span>
                                                @endif
                                            </div>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="text-sm text-themeMuted" x-show="filter.trim()" x-transition>Type to filter by name, SKU or brand. Clear the filter to see all products.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-themeBorder rounded-xl border border-themeBorder overflow-hidden max-h-[400px] overflow-y-auto">
                            <li class="px-4 py-6 text-center text-themeMuted">No other products to merge.</li>
                        </ul>
                    @endif
                    @if ($sources->isNotEmpty())
                        <p class="text-sm text-themeMuted">Select at least one product to merge.</p>
                    @endif
                </div>
            @else
                @foreach ($products as $p)
                    <input type="hidden" name="ids[]" value="{{ $p->id }}">
                @endforeach
                <div class="space-y-4">
                    <label class="block text-sm font-semibold text-themeHeading">Keep this product (recipient) — all others
                        will be merged into it:</label>
                    <ul class="divide-y divide-themeBorder rounded-xl border border-themeBorder overflow-hidden">
                        @foreach ($products as $product)
                            <li class="bg-themeCard hover:bg-themeHover/50 transition">
                                <label class="flex items-center gap-4 px-4 py-3 cursor-pointer">
                                    <input type="radio" name="target_id" value="{{ $product->id }}" required
                                        class="w-4 h-4 text-primary border-themeBorder focus:ring-primary">
                                    <div class="flex-1 min-w-0">
                                        <span class="font-medium text-themeHeading">{{ $product->name }}</span>
                                        <span class="text-themeMuted text-sm ml-2">({{ $product->sku }})</span>
                                        @if ($product->brand)
                                            <span class="text-themeMuted text-sm"> — {{ $product->brand->name }}</span>
                                        @endif
                                    </div>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="mt-6 flex gap-3">
                <button type="submit" id="merge-submit-btn"
                    class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    Merge products
                </button>
                <a href="{{ route('products.index') }}"
                    class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    @if (!empty($recipientFixed) && $sources->isNotEmpty())
        @push('scripts')
            <script>
                (function() {
                    var form = document.getElementById('merge-form');
                    var btn = document.getElementById('merge-submit-btn');
                    var checkboxes = document.querySelectorAll('.source-cb');

                    function updateBtn() {
                        var n = document.querySelectorAll('.source-cb:checked').length;
                        btn.disabled = n === 0;
                    }
                    form.addEventListener('submit', function(e) {
                        if (document.querySelectorAll('.source-cb:checked').length === 0) {
                            e.preventDefault();
                            return false;
                        }
                    });
                    checkboxes.forEach(function(cb) {
                        cb.addEventListener('change', updateBtn);
                    });
                    updateBtn();
                })();
            </script>
        @endpush
    @endif
@endsection
