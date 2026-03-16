@extends('layouts.app')

@section('title', 'Create Stock Take')

@section('content')
<div class="w-full space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Create Stock Take</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Physical inventory count for a branch</p>
        </div>
        <a href="{{ route('stock-takes.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Back</span>
        </a>
    </div>

    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('stock-takes.store') }}" id="stockTakeForm" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch *</label>
                    <select id="branch_id" name="branch_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                            {{ auth()->user()->branch_id && !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (old('branch_id') == $branch->id || (auth()->user()->branch_id == $branch->id && !auth()->user()->isAdmin())) ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @if(auth()->user()->branch_id && !auth()->user()->isAdmin())
                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                    @endif
                    @error('branch_id')
                    <p class="text-red-500 text-sm font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="stock_take_date" class="block text-sm font-medium text-themeBody mb-2">Stock Take Date *</label>
                    <input type="date" id="stock_take_date" name="stock_take_date" value="{{ old('stock_take_date', date('Y-m-d')) }}" required
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    @error('stock_take_date')
                    <p class="text-red-500 text-sm font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes (Optional)</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('notes') }}</textarea>
                @error('notes')
                <p class="text-red-500 text-sm font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-themeBorder pt-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary tracking-tight">Products</h2>
                    <button type="button" id="add-product-btn" class="bg-primary text-white px-4 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Product</span>
                    </button>
                </div>
                <p class="text-sm font-medium text-themeMuted mb-4">Add products with their opening stocks. Closing stocks can be updated later.</p>
                <div id="products-container" class="space-y-4">
                    <!-- Products will be added here dynamically -->
                </div>
            </div>

            <div class="flex space-x-3">
                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Create Stock Take</span>
                </button>
                <a href="{{ route('stock-takes.index') }}" class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span>Cancel</span>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    let productIndex = 0;
    const selectedProductIds = new Set();

    // Products data with current stock
    const products = @json($productsData);

    function addProductRow(productId = '', openingStock = '') {
        const container = document.getElementById('products-container');
        const row = document.createElement('div');
        row.className = 'product-row border border-themeBorder rounded p-4';
        row.dataset.index = productIndex;

        // Get selected branch
        const branchId = document.getElementById('branch_id').value;
        const branchIdInput = document.getElementById('branch_id').disabled ? 
            document.querySelector('input[name="branch_id"][type="hidden"]')?.value : branchId;

        row.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <div class="flex flex-col">
                    <label class="block text-sm font-medium text-themeBody mb-2">Product *</label>
                    <select name="items[${productIndex}][product_id]" class="product-select w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading" required>
                        <option value="">Select Product</option>
                        ${products.map(product => {
                            const isSelected = product.id === productId;
                            const isDisabled = selectedProductIds.has(product.id) && !isSelected;
                            return `<option value="${product.id}" 
                                    data-current-stock="${product.current_stock}"
                                    ${isSelected ? 'selected' : ''}
                                    ${isDisabled ? 'disabled' : ''}>
                                    ${product.name}${product.sku ? ' (' + product.sku + ')' : ''}${product.current_stock > 0 ? ' - Current Stock: ' + product.current_stock : ' - No Stock'}
                                </option>`;
                        }).join('')}
                    </select>
                    <div class="stock-status-${productIndex} mt-2 text-sm font-medium min-h-[20px]"></div>
                </div>
                <div class="flex flex-col">
                    <label class="block text-sm font-medium text-themeBody mb-2">Opening Stock *</label>
                    <input type="number" name="items[${productIndex}][opening_stock]" 
                           value="${openingStock}" 
                           min="0" 
                           step="1"
                           class="opening-stock-input w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading" 
                           required>
                    <p class="text-xs font-medium text-themeMuted mt-1 min-h-[16px]">System quantity at start of stock take</p>
                </div>
                <div class="flex flex-col justify-start">
                    <label class="block text-sm font-medium text-themeBody mb-2 opacity-0 pointer-events-none">Actions</label>
                    <button type="button" class="remove-product-btn bg-red-100 text-red-700 px-4 py-2.5 rounded-xl font-medium hover:bg-red-200 transition flex items-center justify-center space-x-2 w-full h-[42px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Remove</span>
                    </button>
                </div>
            </div>
        `;

        container.appendChild(row);

        // Setup event listeners for this row
        const productSelect = row.querySelector('.product-select');
        const openingStockInput = row.querySelector('.opening-stock-input');
        const stockStatus = row.querySelector(`.stock-status-${productIndex}`);
        const removeBtn = row.querySelector('.remove-product-btn');

        // Update opening stock when product is selected
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const currentStock = parseInt(selectedOption.dataset.currentStock) || 0;
            
            // Update opening stock to current stock (always set it)
            openingStockInput.value = currentStock;

            // Update status
            if (stockStatus) {
                if (currentStock > 0) {
                    stockStatus.innerHTML = `<span class="text-blue-600">Current system stock: ${currentStock}</span>`;
                } else {
                    stockStatus.innerHTML = `<span class="text-themeMuted">No current stock in system</span>`;
                }
            }

            // Update selected products set
            updateSelectedProducts();
        });

        // Remove product row
        removeBtn.addEventListener('click', function() {
            const productIdToRemove = productSelect.value;
            if (productIdToRemove) {
                selectedProductIds.delete(productIdToRemove);
            }
            row.remove();
            updateSelectedProducts();
        });

        // If product is pre-selected, trigger change event
        if (productId) {
            productSelect.value = productId;
            productSelect.dispatchEvent(new Event('change'));
            selectedProductIds.add(productId);
        }

        productIndex++;
    }

    function updateSelectedProducts() {
        selectedProductIds.clear();
        document.querySelectorAll('.product-select').forEach(select => {
            if (select.value) {
                selectedProductIds.add(select.value);
            }
        });

        // Update all product selects to disable already selected products
        document.querySelectorAll('.product-select').forEach(select => {
            Array.from(select.options).forEach(option => {
                if (option.value && selectedProductIds.has(option.value) && select.value !== option.value) {
                    option.disabled = true;
                } else if (option.value) {
                    option.disabled = false;
                }
            });
        });
    }

    // Add product button
    document.getElementById('add-product-btn').addEventListener('click', function() {
        addProductRow();
    });

    // Add initial product row if there are old inputs
    @if(old('items'))
        @foreach(old('items') as $index => $item)
            addProductRow('{{ $item['product_id'] ?? '' }}', '{{ $item['opening_stock'] ?? '' }}');
        @endforeach
    @else
        // Add one empty row by default
        addProductRow();
    @endif
</script>
@endsection

