@extends('layouts.app')

@section('title', 'Create Ticket')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Create Ticket</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Open a new support ticket</p>
            </div>
            <a href="{{ route('tickets.index') }}"
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
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                @if ($customer)
                    <div class="mb-6 p-4 bg-sky-50 border border-sky-100 rounded-xl">
                        <div class="text-sm font-medium text-sky-600 mb-1">Creating ticket for:</div>
                        <div class="text-lg font-semibold text-sky-800">{{ $customer->name }}</div>
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    </div>
                @endif

                @php
                    $ticketCustomerSelectedId = old('customer_id');
                    if ($ticketCustomerSelectedId === 'new' || old('create_new_customer') === 'true') {
                        $ticketCustomerSelectedId = 'new';
                    }
                    $customersForSearch = $customers->map(function ($c) {
                        $label = $c->name . ($c->phone ? ' (' . $c->phone . ')' : ($c->email ? ' (' . $c->email . ')' : ''));
                        return ['id' => $c->id, 'name' => $c->name, 'phone' => $c->phone ?? '', 'email' => $c->email ?? '', 'label' => $label];
                    })->values()->all();
                    $customersForSearch[] = ['id' => 'new', 'name' => 'New Customer', 'phone' => '', 'email' => '', 'label' => 'New Customer'];
                    $assignToForSearch = $assignableUsers->map(fn($u) => ['id' => (string) $u->id, 'name' => $u->name, 'phone' => '', 'email' => '', 'label' => $u->name])->values()->all();
                @endphp
                <script>
                    window.__ticketCustomerConfig = { customers: @json($customersForSearch), selectedId: @json($ticketCustomerSelectedId) };
                    window.__ticketAssignToConfig = { customers: @json($assignToForSearch), selectedId: @json(old('assigned_to', auth()->id())) };
                </script>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{ createNewCustomer: {{ old('create_new_customer') === 'true' || old('customer_id') === 'new' ? 'true' : 'false' }} }" @change="if ($event.target.id === 'customer_id') createNewCustomer = ($event.target.value === 'new')">
                    @if (!auth()->user()->isCustomer() && !$customer)
                        <div class="md:col-span-2">
                            <label for="customer_search" class="block text-sm font-medium text-themeBody mb-2">Customer
                                <span class="text-red-500">*</span></label>
                            <p class="text-xs text-themeMuted mb-2">Select an existing customer or enter name and phone below to create one. Required for every sale.</p>
                            <div class="relative" x-data="customerSearchable(window.__ticketCustomerConfig)" x-init="init()" @click.away="open = false">
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
                            @error('customer_id')
                                <p class="text-xs text-red-600 font-light mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- New Customer Fields -->
                        <div x-show="createNewCustomer" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100" x-cloak
                            class="md:col-span-2 space-y-4 p-4 bg-themeInput border border-themeBorder rounded">
                            <h3 class="text-lg text-primary font-light mb-4">New Customer Information</h3>
                            <input type="hidden" name="create_new_customer" :value="createNewCustomer ? 'true' : 'false'">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="new_customer_name" class="block text-themeBody font-light mb-2">Name
                                        *</label>
                                    <input type="text" id="new_customer_name" name="new_customer_name"
                                        value="{{ old('new_customer_name') }}" :required="createNewCustomer"
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    @error('new_customer_name')
                                        <p class="text-xs text-red-600 font-light mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="new_customer_email" class="block text-themeBody font-light mb-2">Email
                                        (optional)</label>
                                    <input type="email" id="new_customer_email" name="new_customer_email"
                                        value="{{ old('new_customer_email') }}"
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    @error('new_customer_email')
                                        <p class="text-xs text-red-600 font-light mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="new_customer_phone"
                                        class="block text-themeBody font-light mb-2">Phone</label>
                                    <input type="text" id="new_customer_phone" name="new_customer_phone"
                                        value="{{ old('new_customer_phone') }}"
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    @error('new_customer_phone')
                                        <p class="text-xs text-red-600 font-light mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="new_customer_id_number" class="block text-themeBody font-light mb-2">ID
                                        Number</label>
                                    <input type="text" id="new_customer_id_number" name="new_customer_id_number"
                                        value="{{ old('new_customer_id_number') }}"
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    @error('new_customer_id_number')
                                        <p class="text-xs text-red-600 font-light mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="new_customer_address"
                                        class="block text-themeBody font-light mb-2">Address</label>
                                    <textarea id="new_customer_address" name="new_customer_address" rows="2"
                                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('new_customer_address') }}</textarea>
                                    @error('new_customer_address')
                                        <p class="text-xs text-red-600 font-light mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="subject" class="block text-themeBody font-light mb-2">Subject *</label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-themeBody mb-2">Category *</label>
                        <select id="category" name="category" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">Select Category</option>
                            <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technical</option>
                            <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Billing</option>
                            <option value="sales" {{ old('category') === 'sales' ? 'selected' : '' }}>Sales</option>
                            <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General</option>
                            <option value="order" {{ old('category') === 'order' ? 'selected' : '' }}>Order</option>
                            <option value="promise" {{ old('category') === 'promise' ? 'selected' : '' }}>Promise</option>
                            <option value="complaint" {{ old('category') === 'complaint' ? 'selected' : '' }}>Complaint</option>
                            <option value="unsuccessful" {{ old('category') === 'unsuccessful' ? 'selected' : '' }}>Unsuccessful</option>
                            <option value="credit" {{ old('category') === 'credit' ? 'selected' : '' }}>Credit</option>
                        </select>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-medium text-themeBody mb-2">Priority *</label>
                        <select id="priority" name="priority" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">Select Priority</option>
                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>

                    <div>
                        <label for="sale_id" class="block text-sm font-medium text-themeBody mb-2">Related Sale</label>
                        <select id="sale_id" name="sale_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">None</option>
                            @foreach ($sales as $sale)
                                <option value="{{ $sale->id }}" {{ old('sale_id') == $sale->id ? 'selected' : '' }}>
                                    Sale #{{ $sale->sale_number ?? $sale->id }} - {{ $currencySymbol }} {{ number_format($sale->total, 2) }}
                                    - {{ $sale->created_at->format('M d, Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (!auth()->user()->isCustomer() && $products->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-themeBody mb-2">Related Product</label>
                            @php
                                $productsForSearch = $products->map(function ($product) {
                                    $label = $product->name . ' (' . $product->sku . ')';
                                    return ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'disabled' => false, 'label' => $label];
                                })->values()->all();
                            @endphp
                            <select name="product_id" id="product-select-ticket" class="sr-only" aria-hidden="true" tabindex="-1">
                                <option value="">Select Product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                            <script>
                                window.__ticketProductConfig = { products: @json($productsForSearch), selectId: 'product-select-ticket' };
                            </script>
                            <div class="relative" x-data="productSearchable(window.__ticketProductConfig)" x-init="init()" @click.away="open = false">
                                <input type="text" x-ref="searchInput" x-model="search" @focus="open = true" @keydown.arrow-down.prevent="focusNext()" @keydown.arrow-up.prevent="focusPrev()" @keydown.enter.prevent="selectFocused()"
                                    placeholder="Search by product name or SKU..."
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
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
                            <p class="text-xs text-themeMuted font-light mt-1">Select if this ticket is about a product</p>
                        </div>
                    @endif


                    @if (!auth()->user()->isCustomer() && $assignableUsers->count() > 0)
                        <div>
                            <label for="assigned_to_search" class="block text-sm font-medium text-themeBody mb-2">Assign To</label>
                            <p class="text-xs text-themeMuted mb-2">Search by name to assign a staff member.</p>
                            <div class="relative" x-data="assignToSearchable(window.__ticketAssignToConfig)" x-init="init()" @click.away="open = false">
                                <input type="text" id="assigned_to_search" x-ref="searchInput" x-model="search" @focus="open = true" @keydown.arrow-down.prevent="focusNext()" @keydown.arrow-up.prevent="focusPrev()" @keydown.enter.prevent="selectFocused()"
                                    placeholder="Search by name, phone, or email..."
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                                    autocomplete="off">
                                <input type="hidden" name="assigned_to" id="assigned_to" :value="selectedId || ''">
                                <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-themeCard border border-themeBorder rounded-xl shadow-lg max-h-60 overflow-y-auto"
                                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                    <div @click="selectId(null); open = false" class="px-4 py-2.5 cursor-pointer hover:bg-themeHover text-themeBody border-b border-themeBorder"
                                        :class="{ 'bg-primary/10 text-primary': !selectedId }">
                                        Unassigned
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
                            <p class="text-xs text-themeMuted font-light mt-1">Staff member who will handle this ticket</p>
                        </div>
                    @endif

                    <div class="md:col-span-2">
                        <label for="description" class="block text-themeBody font-light mb-2">Description *</label>
                        <textarea id="description" name="description" rows="5" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('description') }}</textarea>
                    </div>

                    @if (!auth()->user()->isCustomer())
                        <div>
                            <label for="due_date" class="block text-themeBody font-light mb-2">Due Date</label>
                            <input type="datetime-local" id="due_date" name="due_date" value="{{ old('due_date') }}"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <p class="text-xs text-themeMuted font-light mt-1">Optional: Set a deadline for this ticket</p>
                        </div>
                    @endif

                    @if (!auth()->user()->isCustomer() && $tags->count() > 0)
                        <div>
                            <label for="tags" class="block text-sm font-medium text-themeBody mb-2">Tags</label>
                            <select id="tags" name="tags[]" multiple
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->id }}"
                                        {{ in_array($tag->id, old('tags', [])) ? 'selected' : '' }}>
                                        {{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-themeMuted font-light mt-1">Hold Ctrl/Cmd to select multiple tags</p>
                        </div>
                    @endif

                    <div class="md:col-span-2">
                        <label for="attachments" class="block text-themeBody font-light mb-2">Attachments</label>
                        <input type="file" id="attachments" name="attachments[]" multiple
                            accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <p class="text-xs text-themeMuted font-light mt-1">Max 10MB per file. Supported: Images, PDF, Word,
                            Excel, Text</p>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span>Create Ticket</span>
                    </button>
                    <a href="{{ route('tickets.index') }}"
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
            window.assignToSearchable = function(initialConfig) {
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
                        const hidden = document.getElementById('assigned_to');
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
    </script>
@endsection

