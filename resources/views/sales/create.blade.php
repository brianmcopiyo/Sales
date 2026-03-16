@extends('layouts.app')

@section('title', 'Create Sale')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Create Sale</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Record a new sale with product and customer details</p>
            </div>
            <a href="{{ route('sales.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('sales.store') }}" id="saleForm" class="space-y-6" enctype="multipart/form-data">
                @csrf

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-6">
                    <div>
                        <label for="customer_search" class="block text-sm font-medium text-themeBody mb-2">Customer
                            <span class="text-red-500">*</span></label>
                        <p class="text-xs text-themeMuted mb-2">Select an existing customer or enter name and phone below to create one. Required for every sale.</p>
                        @php
                            $customersForSearch = $customers->map(function ($c) {
                                $label = $c->name . ($c->phone ? ' (' . $c->phone . ')' : ($c->email ? ' (' . $c->email . ')' : ''));
                                return ['id' => $c->id, 'name' => $c->name, 'phone' => $c->phone ?? '', 'email' => $c->email ?? '', 'label' => $label];
                            })->values()->all();
                        @endphp
                        <script>
                            window.__saleCustomerConfig = { customers: @json($customersForSearch), selectedId: @json(old('customer_id')) };
                        </script>
                        <div class="relative" x-data="customerSearchable(window.__saleCustomerConfig)" x-init="init()" @click.away="open = false">
                            <input type="text" id="customer_search" x-ref="searchInput" x-model="search" @focus="open = true" @keydown.arrow-down.prevent="focusNext()" @keydown.arrow-up.prevent="focusPrev()" @keydown.enter.prevent="selectFocused()"
                                placeholder="Search by name, phone, or email..."
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                                autocomplete="off">
                            <input type="hidden" name="customer_id" id="customer_id" :value="selectedId || ''">
                            <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-themeCard border border-themeBorder rounded-xl shadow-lg max-h-60 overflow-y-auto"
                                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                <div @click="selectId(null); open = false" class="px-4 py-2.5 cursor-pointer hover:bg-themeHover text-themeBody border-b border-themeBorder"
                                    :class="{ 'bg-primary/10 text-primary': !selectedId }">
                                    -- No customer / Select or add below --
                                </div>
                                <template x-for="(c, idx) in filtered" :key="c.id">
                                    <div @click="selectId(c.id); open = false" @mouseenter="focusedIndex = idx"
                                        class="px-4 py-2.5 cursor-pointer border-b border-themeBorder last:border-0"
                                        :class="focusedIndex === idx ? 'bg-primary text-white' : 'hover:bg-themeHover text-themeBody'"
                                        x-text="c.label"></div>
                                </template>
                                <div x-show="filtered.length === 0 && search.length" class="px-4 py-3 text-themeMuted text-sm">No customers match.</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-themeBorder pt-6">
                        <h3 class="text-lg font-semibold text-primary tracking-tight mb-4">Or add new customer (optional)
                        </h3>
                        <div id="new-customer-fields" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="new_customer_name"
                                    class="block text-sm font-medium text-themeBody mb-2">Name</label>
                                <input type="text" id="new_customer_name" name="new_customer_name"
                                    value="{{ old('new_customer_name') }}"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            </div>
                            <div>
                                <label for="new_customer_email" class="block text-sm font-medium text-themeBody mb-2">Email
                                    (optional)</label>
                                <input type="email" id="new_customer_email" name="new_customer_email"
                                    value="{{ old('new_customer_email') }}"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            </div>
                            <div>
                                <label for="new_customer_phone"
                                    class="block text-sm font-medium text-themeBody mb-2">Phone</label>
                                <input type="text" id="new_customer_phone" name="new_customer_phone"
                                    value="{{ old('new_customer_phone') }}"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            </div>
                            <div>
                                <label for="new_customer_id_number" class="block text-sm font-medium text-themeBody mb-2">ID
                                    Number</label>
                                <input type="text" id="new_customer_id_number" name="new_customer_id_number"
                                    value="{{ old('new_customer_id_number') }}"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            </div>
                            <div class="md:col-span-2">
                                <label for="new_customer_address"
                                    class="block text-sm font-medium text-themeBody mb-2">Address</label>
                                <textarea id="new_customer_address" name="new_customer_address" rows="2"
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('new_customer_address') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Items</h2>
                    <div id="items-container" class="space-y-4">
                        <div class="item-row border border-themeBorder rounded-xl p-4 bg-themeInput/50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-themeBody mb-2">Product *</label>
                                    @php
                                        $productsForSearch = $products->map(function ($product) use ($branchStocks) {
                                            $stock = $branchStocks->get($product->id);
                                            $available = $stock ? $stock->available_quantity : 0;
                                            $isInStock = $available > 0;
                                            $regionalPrice = $product->regionPrices->first()?->selling_price;
                                            $hasPrice = $regionalPrice !== null;
                                            $disabled = !$isInStock || !$hasPrice;
                                            $label = $product->name . ' (' . $product->sku . ') - ' . (!$isInStock ? 'Out of Stock' : 'In Stock: ' . $available) . (!$hasPrice ? ' - Price not set for your region' : '');
                                            return ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'price' => $hasPrice ? (float)$regionalPrice : null, 'stock' => $available, 'disabled' => $disabled, 'label' => $label];
                                        })->values()->all();
                                    @endphp
                                    <select name="items[0][product_id]" id="product-select-0" required class="sr-only product-select" aria-hidden="true" tabindex="-1">
                                        <option value="">Select Product</option>
                                        @foreach ($products as $product)
                                            @php
                                                $stock = $branchStocks->get($product->id);
                                                $available = $stock ? $stock->available_quantity : 0;
                                                $isInStock = $available > 0;
                                                $regionalPrice = $product->regionPrices->first()?->selling_price;
                                                $hasPrice = $regionalPrice !== null;
                                            @endphp
                                            <option value="{{ $product->id }}"
                                                data-price="{{ $hasPrice ? $regionalPrice : '' }}"
                                                data-stock="{{ $available }}"
                                                {{ !$isInStock || !$hasPrice ? 'disabled' : '' }}>
                                                {{ $product->name }} ({{ $product->sku }}) -
                                                {{ !$isInStock ? 'Out of Stock' : 'In Stock: ' . $available }}
                                                {{ !$hasPrice ? ' - Price not set for your region' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <script>
                                        window.__saleProductConfig = { products: @json($productsForSearch), selectId: 'product-select-0' };
                                    </script>
                                    <div class="relative" x-data="productSearchable(window.__saleProductConfig)" x-init="init()" @click.away="open = false">
                                        <input type="text" x-ref="searchInput" x-model="search" @focus="open = true" @keydown.arrow-down.prevent="focusNext()" @keydown.arrow-up.prevent="focusPrev()" @keydown.enter.prevent="selectFocused()"
                                            placeholder="Search by product name or SKU..."
                                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading product-search-input"
                                            autocomplete="off">
                                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-themeCard border border-themeBorder rounded-xl shadow-lg max-h-60 overflow-y-auto"
                                            x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                            <div @click="selectProduct(null); open = false" class="px-4 py-2.5 cursor-pointer hover:bg-themeHover text-themeBody border-b border-themeBorder"
                                                :class="{ 'bg-primary/10 text-primary': !selectedId }">
                                                Select Product
                                            </div>
                                            <template x-for="(p, idx) in filtered" :key="p.id">
                                                <div @click="!p.disabled && selectProduct(p); if (!p.disabled) open = false" @mouseenter="focusedIndex = idx"
                                                    class="px-4 py-2.5 border-b border-themeBorder last:border-0"
                                                    :class="{ 'bg-primary text-white': focusedIndex === idx && !p.disabled, 'hover:bg-themeHover text-themeBody': !p.disabled, 'opacity-60 cursor-not-allowed text-themeMuted': p.disabled }"
                                                    x-text="p.label"></div>
                                            </template>
                                            <div x-show="filtered.length === 0 && search.length" class="px-4 py-3 text-themeMuted text-sm">No products match.</div>
                                        </div>
                                    </div>
                                    <div id="stock-status-0" class="mt-2 text-sm font-medium"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-themeBody mb-2">Quantity *</label>
                                    <input type="number" name="items[0][quantity]" value="{{ old('items.0.quantity', 1) }}" min="1" required
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading quantity-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-themeBody mb-2">Unit Price *</label>
                                    <input type="number" step="0.01" name="items[0][unit_price]" required
                                        min="0"
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading price-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="discount" value="0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="tax" class="block text-sm font-medium text-themeBody mb-2">Tax</label>
                        <input type="number" step="0.01" id="tax" name="tax" value="{{ old('tax', 0) }}"
                            min="0"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>
                    <div>
                        <label for="customer_support_amount" class="block text-sm font-medium text-themeBody mb-2">Customer
                            Support Amount</label>
                        <input type="number" step="0.01" id="customer_support_amount" name="customer_support_amount"
                            value="{{ old('customer_support_amount', 0) }}" min="0"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <p class="text-xs font-medium text-themeMuted mt-1">Amount to support this customer for this sale</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-themeBody mb-2">Total</label>
                        <div id="total-display" class="text-2xl font-semibold text-primary">TSh 0.00</div>
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('notes') }}</textarea>
                </div>

                <div>
                    <label for="evidence" class="block text-sm font-medium text-themeBody mb-2">Evidence (optional)</label>
                    <p class="text-xs text-themeMuted mb-2">Attach up to 10 files (images or PDF), max 5 MB each. Visible on sale details.</p>
                    <input type="file" id="evidence" name="evidence[]" multiple
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-primary/10 file:text-primary">
                </div>

                <div class="flex space-x-3">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span>Complete Sale</span>
                    </button>
                    <a href="{{ route('sales.index') }}"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>
    </div>


    <script>
        // Expose searchable dropdown components on window so x-data="customerSearchable({...})" resolves and receives config
        (function() {
            const config = (c) => c || {};
            window.customerSearchable = function(initialConfig) {
                const cfg = config(initialConfig);
                return {
                    customers: cfg.customers || [],
                    selectedId: cfg.selectedId || '',
                    search: '',
                    open: false,
                    focusedIndex: -1,
                    get filtered() {
                        const q = (this.search || '').toLowerCase().trim();
                        if (!q) return this.customers;
                        return this.customers.filter(c =>
                            (c.name && c.name.toLowerCase().includes(q)) ||
                            (c.phone && c.phone.toString().includes(q)) ||
                            (c.email && c.email.toLowerCase().includes(q)) ||
                            (c.label && c.label.toLowerCase().includes(q))
                        );
                    },
                    init() {
                        const sel = this.customers.find(c => c.id == this.selectedId);
                        if (sel) this.search = sel.label;
                    },
                    selectId(id) {
                        this.selectedId = id || '';
                        const sel = this.customers.find(c => c.id == id);
                        this.search = sel ? sel.label : '';
                        this.focusedIndex = -1;
                        const hidden = document.getElementById('customer_id');
                        if (hidden) {
                            hidden.value = this.selectedId || '';
                            hidden.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    },
                    focusNext() {
                        if (!this.open) return;
                        this.focusedIndex = Math.min(this.focusedIndex + 1, this.filtered.length);
                    },
                    focusPrev() {
                        this.focusedIndex = Math.max(this.focusedIndex - 1, -1);
                    },
                    selectFocused() {
                        if (this.focusedIndex < 0) this.selectId(null);
                        else if (this.filtered[this.focusedIndex]) this.selectId(this.filtered[this.focusedIndex].id);
                    }
                };
            };
            window.productSearchable = function(initialConfig) {
                const cfg = config(initialConfig);
                return {
                    products: cfg.products || [],
                    selectId: cfg.selectId || 'product-select-0',
                    selectedId: '',
                    selectedLabel: '',
                    search: '',
                    open: false,
                    focusedIndex: -1,
                    get filtered() {
                        const q = (this.search || '').toLowerCase().trim();
                        if (!q) return this.products;
                        return this.products.filter(p =>
                            (p.name && p.name.toLowerCase().includes(q)) ||
                            (p.sku && p.sku.toLowerCase().includes(q)) ||
                            (p.label && p.label.toLowerCase().includes(q))
                        );
                    },
                    init() {
                        const sel = document.getElementById(this.selectId);
                        if (sel && sel.value) {
                            this.selectedId = sel.value;
                            const p = this.products.find(x => x.id == sel.value);
                            if (p) {
                                this.selectedLabel = p.label;
                                this.search = p.label;
                            }
                        }
                    },
                    selectProduct(p) {
                        this.selectedId = p ? p.id : '';
                        this.selectedLabel = p ? p.label : '';
                        this.search = this.selectedLabel;
                        this.focusedIndex = -1;
                        const sel = document.getElementById(this.selectId);
                        if (sel) {
                            sel.value = this.selectedId || '';
                            sel.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    },
                    focusNext() {
                        if (!this.open) return;
                        this.focusedIndex = Math.min(this.focusedIndex + 1, this.filtered.length);
                    },
                    focusPrev() {
                        this.focusedIndex = Math.max(this.focusedIndex - 1, -1);
                    },
                    selectFocused() {
                        if (this.focusedIndex < 0) this.selectProduct(null);
                        else if (this.filtered[this.focusedIndex] && !this.filtered[this.focusedIndex].disabled) this.selectProduct(this.filtered[this.focusedIndex]);
                    }
                };
            };
        })();

        let currentProductId = null;

        function attachEventListeners() {
            const productSelect = document.getElementById('product-select-0');
            const priceInput = document.querySelector('.price-input');
            const stockStatus = document.getElementById('stock-status-0');
            const customerSelect = document.getElementById('customer_id');
            const newCustomerFields = document.getElementById('new-customer-fields');

            // Toggle new customer fields based on customer selection
            if (customerSelect && newCustomerFields) {
                const newCustomerName = document.getElementById('new_customer_name');
                const newCustomerPhone = document.getElementById('new_customer_phone');

                customerSelect.addEventListener('change', function() {
                    if (this.value) {
                        // Existing customer selected - hide new customer fields
                        newCustomerFields.style.display = 'none';
                        // Clear new customer fields
                        if (newCustomerName) newCustomerName.value = '';
                        document.getElementById('new_customer_email').value = '';
                        if (newCustomerPhone) newCustomerPhone.value = '';
                        document.getElementById('new_customer_id_number').value = '';
                        document.getElementById('new_customer_address').value = '';
                    } else {
                        // No customer selected - show new customer fields (all optional)
                        newCustomerFields.style.display = 'grid';
                    }
                });

                // Initialize on page load - check if customer is selected
                function initializeCustomerFields() {
                    if (customerSelect.value) {
                        newCustomerFields.style.display = 'none';
                    } else {
                        newCustomerFields.style.display = 'grid';
                    }
                }

                // Initialize on page load - run immediately and on DOM ready
                initializeCustomerFields();

                // Also initialize after DOM is fully loaded (for validation errors)
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initializeCustomerFields);
                } else {
                    // DOM is already loaded, run after a short delay to ensure everything is ready
                    setTimeout(initializeCustomerFields, 100);
                }
            }

            productSelect.addEventListener('change', function() {
                const productId = this.value;
                currentProductId = productId;
                const price = this.options[this.selectedIndex].dataset.price;
                const stock = parseInt(this.options[this.selectedIndex].dataset.stock) || 0;

                // Update price
                if (price) {
                    priceInput.value = price;
                }

                // Update stock status display
                if (stockStatus) {
                    if (stock > 0) {
                        stockStatus.innerHTML = '<span class="text-green-600">✓ In Stock (' + stock +
                            ' available)</span>';
                    } else {
                        stockStatus.innerHTML = '<span class="text-red-600">✗ Out of Stock</span>';
                    }
                }

                calculateTotal();
            });

            const quantityInput = document.querySelector('.quantity-input');
            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
                    calculateTotal();
                });
            }

            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    calculateTotal();
                });
            }
        }

        function calculateTotal() {
            let subtotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qtyInput = row.querySelector('.quantity-input');
                const quantity = qtyInput ? (parseInt(qtyInput.value, 10) || 0) : 1;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                subtotal += quantity * price;
            });

            const tax = parseFloat(document.getElementById('tax').value) || 0;
            const total = subtotal + tax;

            const totalEl = document.getElementById('total-display');
            if (totalEl) totalEl.textContent = 'TSh ' + total.toFixed(2);
        }

        const taxEl = document.getElementById('tax');
        if (taxEl) taxEl.addEventListener('input', calculateTotal);

        attachEventListeners();
    </script>
@endsection

