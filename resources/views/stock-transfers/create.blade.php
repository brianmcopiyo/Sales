@extends('layouts.app')

@section('title', 'Create Stock Transfer')

@section('content')
@php
    $initialItems = [['product_id' => '', 'quantity' => '', 'imeis' => '']];
    if (request()->has('product_id') || request()->has('imei')) {
        $initialItems = [['product_id' => (string) request('product_id'), 'quantity' => (string) request('quantity', 1), 'imeis' => (string) request('imei', '')]];
    } elseif (old('items')) {
        $initialItems = array_values(array_map(fn($i) => ['product_id' => (string) ($i['product_id'] ?? ''), 'quantity' => (string) ($i['quantity'] ?? ''), 'imeis' => (string) ($i['imeis'] ?? '')], old('items')));
        if (empty($initialItems)) {
            $initialItems = [['product_id' => '', 'quantity' => '', 'imeis' => '']];
        }
    }
    $productsJson = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])->values();
@endphp
<script type="application/json" id="transfer-initial-items">@json($initialItems)</script>
<script type="application/json" id="transfer-products">@json($productsJson)</script>

<div class="w-full space-y-6" x-data="transferCreate()">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Create Stock Transfer</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Move stock from one branch to another</p>
        </div>
        <a href="{{ route('stock-transfers.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Back</span>
        </a>
    </div>

    <!-- Progress -->
    <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors"
            :class="step >= 1 ? 'bg-primary text-white' : 'bg-themeInput text-themeMuted'">1</div>
        <div class="w-12 h-0.5 rounded" :class="step > 1 ? 'bg-primary' : 'bg-themeBorder'"></div>
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors"
            :class="step >= 2 ? 'bg-primary text-white' : 'bg-themeInput text-themeMuted'">2</div>
    </div>

    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('stock-transfers.store') }}" id="transfer-create-form" class="space-y-6" enctype="multipart/form-data">
            @csrf

            <!-- Step 1: Transfer details and items -->
            <div x-show="step === 1" x-transition class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-themeBody mb-2">From Branch</label>
                        <div class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput font-medium text-themeBody">
                            {{ $userBranch->name }}
                        </div>
                        <input type="hidden" name="from_branch_id" value="{{ $userBranch->id }}">
                    </div>

                    <div>
                        <label for="to_branch_id" class="block text-sm font-medium text-themeBody mb-2">To Branch *</label>
                        <select id="to_branch_id" name="to_branch_id" required
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('to_branch_id', request('to_branch_id')) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('to_branch_id')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-themeBody">Transfer items *</label>
                        <button type="button" @click="addRow()"
                                class="text-sm font-medium text-primary hover:text-primary-dark flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add product / IMEIs
                        </button>
                    </div>
                    <p class="text-xs text-themeMuted mb-4">For each line: choose a product and either enter quantity or paste/upload IMEIs for that product.</p>

                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/30 space-y-3">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-sm font-medium text-themeMuted" x-text="'Item ' + (index + 1)"></span>
                                    <button type="button" x-show="items.length > 1" @click="removeRow(index)"
                                            class="text-themeMuted hover:text-red-600 text-sm font-medium">Remove</button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label :for="'product_id_' + index" class="block text-xs font-medium text-themeBody mb-1">Product *</label>
                                        <select :name="'items[' + index + '][product_id]'" :id="'product_id_' + index" required
                                                x-model="item.product_id"
                                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                            <option value="">Select Product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label :for="'quantity_' + index" class="block text-xs font-medium text-themeBody mb-1">Quantity (if not using IMEIs)</label>
                                        <input type="number" :name="'items[' + index + '][quantity]'" :id="'quantity_' + index" min="0"
                                               x-model="item.quantity"
                                               placeholder="e.g. 5"
                                               class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    </div>
                                </div>
                                <div>
                                    <label :for="'imeis_' + index" class="block text-xs font-medium text-themeBody mb-1">Or IMEIs (one per line or comma-separated)</label>
                                    <textarea :name="'items[' + index + '][imeis]'" :id="'imeis_' + index" rows="2"
                                              x-model="item.imeis"
                                              placeholder="Paste IMEIs here or leave blank and use quantity above"
                                              class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading text-sm"></textarea>
                                    <label :for="'imei_file_' + index" class="block text-xs font-medium text-themeBody mt-1">Or upload CSV/Excel</label>
                                    <input type="file" :name="'items[' + index + '][imei_file]'" :id="'imei_file_' + index"
                                           accept=".csv,.xlsx,.xls,.txt"
                                           class="w-full px-4 py-2 border border-themeBorder rounded-xl text-sm file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-themeHover file:text-themeBody mt-1">
                                </div>
                            </div>
                        </template>
                    </div>
                    @error('items')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Step 2: Review & confirm -->
            <div x-show="step === 2" x-transition class="space-y-6">
                <h2 class="text-lg font-semibold text-primary tracking-tight">Review & confirm</h2>
                <p class="text-sm text-themeMuted">Check the summary below and submit to create the transfer.</p>
                <div class="rounded-xl border border-themeBorder bg-themeInput/50 p-4 space-y-2 text-sm" id="transfer-review-summary">
                    <p class="font-medium text-themeHeading">Summary will appear here after you complete step 1.</p>
                </div>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="button" x-show="step > 1" @click="step = 1"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <span>Back</span>
                </button>
                <div class="flex-1"></div>
                <button type="button" x-show="step < 2" @click="nextStep()"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <span>Next</span>
                </button>
                <button type="submit" x-show="step === 2"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Create Transfer</span>
                </button>
                <a href="{{ route('stock-transfers.index') }}" x-show="step === 1" class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
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
    function transferCreate() {
        const initialEl = document.getElementById('transfer-initial-items');
        const initialItems = initialEl ? (JSON.parse(initialEl.textContent || '[]') || []) : [{}];
        const products = (function() {
            const el = document.getElementById('transfer-products');
            return el ? (JSON.parse(el.textContent || '[]') || []) : [];
        })();
        const safeItems = Array.isArray(initialItems) && initialItems.length ? initialItems : [{ product_id: '', quantity: '', imeis: '' }];

        return {
            items: safeItems.map(i => ({ product_id: i.product_id || '', quantity: i.quantity || '', imeis: i.imeis || '' })),
            step: 1,
            addRow() {
                this.items.push({ product_id: '', quantity: '', imeis: '' });
            },
            removeRow(index) {
                if (this.items.length > 1) this.items.splice(index, 1);
            },
            nextStep() {
                const toBranch = document.getElementById('to_branch_id');
                if (toBranch && !toBranch.value) return;
                const firstProduct = document.querySelector('[name^="items[0]"][name*="[product_id]"]');
                if (firstProduct && !firstProduct.value) return;
                this.updateReview();
                this.step = 2;
            },
            updateReview() {
                const toSel = document.getElementById('to_branch_id');
                const toName = toSel && toSel.options[toSel.selectedIndex] ? toSel.options[toSel.selectedIndex].text : '—';
                const notes = (document.getElementById('notes') && document.getElementById('notes').value) || '—';
                const itemSelects = document.querySelectorAll('select[name^="items["][name*="[product_id]"]');
                let lines = [];
                itemSelects.forEach(function(sel, i) {
                    if (!sel || !sel.value) return;
                    const opt = sel.options[sel.selectedIndex];
                    const name = opt ? opt.text : 'Product';
                    const qtyInput = document.querySelector('input[name="items[' + i + '][quantity]"]');
                    const imeiArea = document.querySelector('textarea[name="items[' + i + '][imeis]"]');
                    const qty = (qtyInput && qtyInput.value) ? qtyInput.value : '0';
                    const imeiCount = (imeiArea && imeiArea.value) ? imeiArea.value.split(/[\r\n,;]+/).filter(function(s) { return s.trim().length >= 15; }).length : 0;
                    const displayQty = imeiCount > 0 ? imeiCount + ' (IMEIs)' : qty;
                    lines.push(name + ' × ' + displayQty);
                });
                const el = document.getElementById('transfer-review-summary');
                if (el) {
                    el.innerHTML = '<p class="font-medium text-themeHeading">From: {{ $userBranch->name }}</p>' +
                        '<p class="font-medium text-themeHeading">To: ' + (toName || '—') + '</p>' +
                        '<p class="text-themeBody">Notes: ' + (notes !== '—' ? notes : '—') + '</p>' +
                        '<p class="font-medium text-themeHeading mt-3">Items:</p>' +
                        (lines.length ? '<ul class="list-disc list-inside text-themeBody">' + lines.map(function(l) { return '<li>' + l + '</li>'; }).join('') + '</ul>' : '<p class="text-themeMuted">None added.</p>');
                }
            }
        };
    }
</script>
@endsection
