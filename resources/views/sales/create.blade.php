@extends('layouts.app')

@section('title', 'Create Sale')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Create Sale</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Record a new sale with device and customer details</p>
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
            {{-- Device request is outside the sale form so "Request device" submits only the request, not the sale (no nested forms). --}}
            @if (session('device_request'))
                @php $dr = session('device_request'); @endphp
                <div class="rounded-xl border border-primary/30 bg-primary/5 px-4 py-4 mb-6">
                    <p class="text-sm font-medium text-themeBody mb-2">Request this device from the host branch</p>
                    <p class="text-sm text-themeMuted mb-3">IMEI <strong>{{ $dr['imei'] ?? '' }}</strong> is at <strong>{{ $dr['host_branch_name'] ?? 'another branch' }}</strong>. Request it and a user there can approve; the device will then move to your branch.</p>
                    <form action="{{ route('device-requests.store') }}" method="post" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <input type="hidden" name="device_id" value="{{ $dr['device_id'] ?? '' }}">
                        <div class="flex-1 min-w-[200px]">
                            <label for="device_request_notes" class="block text-xs font-medium text-themeMuted mb-1">Notes (optional)</label>
                            <input type="text" id="device_request_notes" name="notes" placeholder="e.g. Needed for sale"
                                class="w-full px-3 py-2 border border-themeBorder rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-xl font-medium hover:bg-primary-dark transition text-sm">
                            Request device from {{ $dr['host_branch_name'] ?? 'host branch' }}
                        </button>
                    </form>
                </div>
            @endif

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
                                <div class="relative">
                                    <label class="block text-sm font-medium text-themeBody mb-2">Device IMEI *</label>
                                    <input type="text" id="device-imei-input-0"
                                        placeholder="Type IMEI number (15 digits)..." autocomplete="off" maxlength="15"
                                        pattern="[0-9]{15}"
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading device-imei-input"
                                        disabled>
                                    <input type="hidden" name="items[0][device_id]" id="device-id-input-0">
                                    <input type="hidden" name="items[0][device_imei]" id="device-imei-hidden-0">
                                    <!-- Suggestions dropdown -->
                                    <div id="device-suggestions-0"
                                        class="absolute z-50 w-full mt-1 bg-themeCard border border-themeBorder rounded-xl shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] max-h-60 overflow-y-auto hidden"
                                        style="display: none; top: 100%;">
                                        <!-- Suggestions will be populated here -->
                                    </div>
                                    <div id="device-status-0" class="mt-2 text-sm font-medium"></div>
                                    @error('device')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-themeBody mb-2">Quantity</label>
                                    <input type="hidden" name="items[0][quantity]" value="1">
                                    <input type="number" value="1" disabled
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput text-themeMuted font-medium">
                                    <p class="text-xs font-medium text-themeMuted mt-1">One device per customer per sale</p>
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
        window.__canCreateDevice = @json($canCreateDevice ?? false);
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

        // Device data from backend
        const availableDevices = @json($availableDevices);
        const allAvailableDevices = @json($allAvailableDevices);

        let currentProductId = null;
        let filteredDevices = [];

        function attachEventListeners() {
            const productSelect = document.getElementById('product-select-0');
            const deviceImeiInput = document.getElementById('device-imei-input-0');
            const deviceIdInput = document.getElementById('device-id-input-0');
            const deviceSuggestions = document.getElementById('device-suggestions-0');
            const priceInput = document.querySelector('.price-input');
            const stockStatus = document.getElementById('stock-status-0');
            const deviceStatus = document.getElementById('device-status-0');
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

                // Filter devices by product
                if (productId) {
                    filteredDevices = allAvailableDevices.filter(device => device.product_id == productId);
                    deviceImeiInput.disabled = false;

                    if (deviceStatus) {
                        if (filteredDevices.length > 0) {
                            deviceStatus.innerHTML = '<span class="text-green-600">✓ ' + filteredDevices.length +
                                ' device(s) available</span>';
                        } else {
                            deviceStatus.innerHTML = '<span class="text-red-600">✗ No devices available</span>';
                        }
                    }
                } else {
                    filteredDevices = [];
                    deviceImeiInput.disabled = true;
                    deviceImeiInput.value = '';
                    deviceIdInput.value = '';
                    deviceSuggestions.classList.add('hidden');
                    if (deviceStatus) {
                        deviceStatus.innerHTML = '';
                    }
                }

                calculateTotal();
            });

            // IMEI input - only allow numbers with autocomplete
            deviceImeiInput.addEventListener('input', function(e) {
                // Remove any non-numeric characters
                let value = this.value.replace(/[^0-9]/g, '');

                // Limit to 15 digits (IMEI standard length)
                if (value.length > 15) {
                    value = value.substring(0, 15);
                }

                this.value = value;

                const query = value.trim();
                const previousDeviceId = deviceIdInput.value;

                // Clear device ID when IMEI changes (user is typing/editing)
                deviceIdInput.value = '';
                selectedSuggestionIndex = -1; // Reset selection when typing

                // Clear status message initially
                if (deviceStatus) {
                    deviceStatus.innerHTML = '';
                }

                // If user had a device selected and is now typing, hide suggestions until they type enough
                if (previousDeviceId && query.length < 2) {
                    deviceSuggestions.classList.add('hidden');
                    return;
                }

                // Show suggestions as user types (minimum 2 digits)
                if (!query || !currentProductId || query.length < 2) {
                    deviceSuggestions.classList.add('hidden');
                    // Show validation message only if user has typed something
                    if (query.length > 0 && query.length < 15) {
                        if (deviceStatus) {
                            deviceStatus.innerHTML =
                                '<span class="text-themeMuted">Type at least 2 digits to see suggestions</span>';
                        }
                    }
                    return;
                }

                // Don't show suggestions if a device is already selected
                if (deviceIdInput.value) {
                    deviceSuggestions.classList.add('hidden');
                    deviceSuggestions.style.display = 'none';
                    return;
                }

                // Filter devices by IMEI query (starts with or contains)
                const matches = filteredDevices.filter(device =>
                    device.imei.startsWith(query)
                ).slice(0, 10); // Limit to 10 suggestions

                // Store matches globally for keyboard navigation
                window.currentDeviceMatches = matches;

                if (matches.length > 0) {
                    deviceSuggestions.innerHTML = '';
                    matches.forEach((device, index) => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.className =
                            'px-4 py-2 hover:bg-primary hover:text-white cursor-pointer font-light border-b border-themeBorder last:border-0 transition-colors';

                        // Highlight matching part of IMEI
                        const matchedPart = device.imei.substring(0, query.length);
                        const remainingPart = device.imei.substring(query.length);

                        const branchPart = device.branch_name ? ` • ${device.branch_name}` : '';
                        suggestionItem.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div class="text-themeHeading hover:text-white">
                                    <span class="font-semibold text-primary hover:text-white">${matchedPart}</span><span class="text-themeBody hover:text-white">${remainingPart}</span>
                                </div>
                                <div class="text-xs text-themeMuted hover:text-themeMuted ml-2">${device.product_name}${branchPart}</div>
                            </div>
                        `;
                        // Store device data on the element for easy access
                        suggestionItem.dataset.deviceImei = device.imei;
                        suggestionItem.dataset.deviceId = device.id;
                        suggestionItem.addEventListener('mousedown', function(e) {
                            e.preventDefault(); // Prevent input from losing focus
                            const selectedImei = this.dataset.deviceImei;
                            const selectedId = this.dataset.deviceId;
                            if (selectedImei && selectedId) {
                                deviceImeiInput.value = selectedImei;
                                deviceIdInput.value = selectedId;
                                deviceSuggestions.classList.add('hidden');
                                deviceSuggestions.style.display = 'none';
                                selectedSuggestionIndex = -1;
                                if (deviceStatus) {
                                    deviceStatus.innerHTML =
                                        '<span class="text-green-600">✓ Device selected: ' +
                                        selectedImei + '</span>';
                                }
                                calculateTotal();
                                // Keep focus on input to prevent blur issues
                                deviceImeiInput.focus();
                            }
                        });
                        deviceSuggestions.appendChild(suggestionItem);
                    });
                    deviceSuggestions.classList.remove('hidden');
                    deviceSuggestions.style.display = 'block';

                    // Update status message
                    if (deviceStatus) {
                        if (query.length === 15) {
                            if (matches.length === 1 && matches[0].imei === query) {
                                deviceStatus.innerHTML = '<span class="text-green-600">✓ Exact match found</span>';
                            } else {
                                deviceStatus.innerHTML = '<span class="text-blue-600">ℹ ' + matches.length +
                                    ' matching device(s) found</span>';
                            }
                        } else {
                            deviceStatus.innerHTML = '<span class="text-blue-600">ℹ ' + matches.length +
                                ' matching device(s) - ' + (15 - query.length) + ' digits remaining</span>';
                        }
                    }
                } else {
                    deviceSuggestions.classList.add('hidden');
                    if (deviceStatus) {
                        if (query.length === 15) {
                            deviceIdInput.value = '';
                            document.getElementById('device-imei-hidden-0').value = query;
                            if (window.__canCreateDevice) {
                                deviceStatus.innerHTML =
                                    '<span class="text-amber-700 font-medium">This IMEI is not in the system. </span>' +
                                    '<button type="button" id="confirm-create-device-0" class="ml-1 text-primary font-semibold underline hover:no-underline focus:outline-none">Create new device with this IMEI</button>' +
                                    '<span class="text-amber-700 font-medium"> when you complete the sale below.</span>';
                                const confirmBtn = document.getElementById('confirm-create-device-0');
                                if (confirmBtn) {
                                    confirmBtn.addEventListener('click', function() {
                                        deviceStatus.innerHTML =
                                            '<span class="text-green-600">✓ New device will be created for IMEI ' +
                                            query + ' when you complete the sale.</span>';
                                    });
                                }
                            } else {
                                deviceStatus.innerHTML =
                                    '<span class="text-amber-700 font-medium">This IMEI is not in the system. You do not have permission to create devices.</span>';
                            }
                        } else {
                            deviceStatus.innerHTML =
                                '<span class="text-orange-600">⚠ No matching device found. Enter full 15-digit IMEI to create a new device.</span>';
                        }
                    }
                }
            });

            // Keyboard navigation for autocomplete
            let selectedSuggestionIndex = -1;
            deviceImeiInput.addEventListener('keydown', function(e) {
                // Don't show suggestions if a device is already selected (unless user is editing)
                if (deviceIdInput.value && (e.key !== 'Backspace' && e.key !== 'Delete' && !e.key.startsWith(
                        'Arrow'))) {
                    return;
                }

                const suggestions = deviceSuggestions.querySelectorAll('div');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (deviceSuggestions.classList.contains('hidden')) return;
                    selectedSuggestionIndex = Math.min(selectedSuggestionIndex + 1, suggestions.length - 1);
                    updateSuggestionSelection(suggestions);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (deviceSuggestions.classList.contains('hidden')) return;
                    selectedSuggestionIndex = Math.max(selectedSuggestionIndex - 1, -1);
                    updateSuggestionSelection(suggestions);
                } else if (e.key === 'Enter' && selectedSuggestionIndex >= 0 && suggestions[
                        selectedSuggestionIndex]) {
                    e.preventDefault();
                    const selectedSuggestion = suggestions[selectedSuggestionIndex];
                    const selectedImei = selectedSuggestion.dataset.deviceImei;
                    const selectedId = selectedSuggestion.dataset.deviceId;
                    if (selectedImei && selectedId) {
                        deviceImeiInput.value = selectedImei;
                        deviceIdInput.value = selectedId;
                        deviceSuggestions.classList.add('hidden');
                        deviceSuggestions.style.display = 'none';
                        selectedSuggestionIndex = -1;
                        if (deviceStatus) {
                            deviceStatus.innerHTML =
                                '<span class="text-green-600">✓ Device selected: ' + selectedImei + '</span>';
                        }
                        calculateTotal();
                    }
                } else if (e.key === 'Escape') {
                    deviceSuggestions.classList.add('hidden');
                    deviceSuggestions.style.display = 'none';
                    selectedSuggestionIndex = -1;
                } else if (e.key === 'Backspace' || e.key === 'Delete') {
                    // Clear device selection when user deletes
                    deviceIdInput.value = '';
                } else {
                    selectedSuggestionIndex = -1;
                }
            });

            function updateSuggestionSelection(suggestions) {
                suggestions.forEach((item, index) => {
                    if (index === selectedSuggestionIndex) {
                        item.classList.add('bg-primary', 'text-white');
                        item.classList.remove('hover:bg-primary');
                    } else {
                        item.classList.remove('bg-primary', 'text-white');
                        item.classList.add('hover:bg-primary');
                    }
                });
            }

            // Validate IMEI on blur
            deviceImeiInput.addEventListener('blur', function() {
                // Delay to allow click events on suggestions to complete first
                setTimeout(() => {
                    // Only hide if a device wasn't just selected
                    if (!deviceIdInput.value) {
                        deviceSuggestions.classList.add('hidden');
                        deviceSuggestions.style.display = 'none';
                    }
                    selectedSuggestionIndex = -1;
                }, 200);

                const imei = this.value.trim();
                const deviceImeiHidden = document.getElementById('device-imei-hidden-0');

                // Validate IMEI format
                if (imei && imei.length !== 15) {
                    deviceIdInput.value = '';
                    deviceImeiHidden.value = '';
                    if (deviceStatus) {
                        deviceStatus.innerHTML =
                            '<span class="text-red-600">✗ IMEI must be exactly 15 digits</span>';
                    }
                    return;
                }

                if (imei && currentProductId) {
                    const device = filteredDevices.find(d => d.imei === imei);
                    if (device) {
                        deviceIdInput.value = device.id;
                        deviceImeiHidden.value = ''; // Clear IMEI if device exists
                        if (deviceStatus) {
                            deviceStatus.innerHTML = '<span class="text-green-600">✓ Device selected</span>';
                        }
                    } else {
                        deviceIdInput.value = ''; // Clear device ID
                        deviceImeiHidden.value = imei; // Store IMEI for creation
                        if (deviceStatus) {
                            if (window.__canCreateDevice) {
                                deviceStatus.innerHTML =
                                    '<span class="text-amber-700 font-medium">IMEI not in system. </span>' +
                                    '<button type="button" class="text-primary font-semibold underline hover:no-underline focus:outline-none create-device-prompt-btn">Create new device with this IMEI</button>' +
                                    '<span class="text-amber-700 font-medium"> when you complete the sale.</span>';
                                const btn = deviceStatus.querySelector('.create-device-prompt-btn');
                                if (btn) btn.addEventListener('click', function() {
                                    deviceStatus.innerHTML =
                                        '<span class="text-green-600">✓ New device will be created for IMEI ' +
                                        imei + ' when you complete the sale.</span>';
                                });
                            } else {
                                deviceStatus.innerHTML =
                                    '<span class="text-amber-700 font-medium">This IMEI is not in the system. You do not have permission to create devices.</span>';
                            }
                        }
                    }
                } else if (imei && !currentProductId) {
                    deviceIdInput.value = '';
                    deviceImeiHidden.value = '';
                    if (deviceStatus) {
                        deviceStatus.innerHTML =
                            '<span class="text-red-600">✗ Please select a product first</span>';
                    }
                } else {
                    deviceIdInput.value = '';
                    deviceImeiHidden.value = '';
                }
            });

            // Prevent paste of non-numeric characters
            deviceImeiInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const numbersOnly = paste.replace(/[^0-9]/g, '').substring(0, 15);
                this.value = numbersOnly;
                this.dispatchEvent(new Event('input'));
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                const deviceContainer = deviceImeiInput.closest('.relative');
                if (deviceContainer && !deviceContainer.contains(e.target)) {
                    deviceSuggestions.classList.add('hidden');
                }
            });

            document.querySelectorAll('.price-input').forEach(input => {
                input.addEventListener('input', calculateTotal);
            });
        }

        function calculateTotal() {
            let subtotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const quantity = 1; // Always 1 device per customer
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                subtotal += quantity * price;
            });

            const tax = parseFloat(document.getElementById('tax').value) || 0;
            const total = subtotal + tax;

            document.getElementById('total-display').textContent = 'TSh ' + total.toFixed(2);
        }

        document.getElementById('tax').addEventListener('input', calculateTotal);

        // Prompt to create new device on submit when IMEI is not in system
        document.getElementById('saleForm').addEventListener('submit', function(e) {
            const deviceImeiHidden = document.getElementById('device-imei-hidden-0');
            const deviceIdInput = document.getElementById('device-id-input-0');
            const imei = (deviceImeiHidden && deviceImeiHidden.value) ? deviceImeiHidden.value.trim() : '';
            if (imei && imei.length === 15 && (!deviceIdInput || !deviceIdInput.value)) {
                if (!confirm('IMEI ' + imei +
                        ' is not in the system. A new device will be created when you complete this sale. Continue?'
                    )) {
                    e.preventDefault();
                }
            }
        });

        attachEventListeners();
    </script>
@endsection

