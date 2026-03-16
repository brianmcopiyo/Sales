@extends('layouts.standalone')

@section('title', 'Create new stock')

@section('content')
    <div class="min-h-screen theme-form-context" x-data="restockWizard()">
        <div class="max-w-2xl mx-auto px-4 py-8 sm:px-6">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-themeMuted hover:text-primary mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>

            <h1 class="text-2xl font-semibold text-primary tracking-tight mb-2">Create new stock</h1>
            <p class="text-sm text-themeMuted mb-8">Step-by-step restock order. Fill each step and submit when ready.</p>

            <!-- Progress -->
            <div class="flex items-center gap-2 mb-10">
                <template x-for="s in 3" :key="s">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors"
                            :class="step >= s ? 'bg-primary text-white' : 'bg-themeInput text-themeMuted'"
                            x-text="s"></div>
                        <template x-if="s < 3">
                            <div class="w-8 h-0.5 mx-0.5 rounded"
                                :class="step > s ? 'bg-primary' : 'bg-themeBorder'"></div>
                        </template>
                    </div>
                </template>
            </div>

            <form method="POST" action="{{ route('stock-management.orders.store') }}" id="restock-wizard-form"
                class="space-y-8">
                @csrf

                <!-- Step 1: Order details -->
                <div x-show="step === 1" x-transition class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Step 1 — Order details</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="wizard_branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch *</label>
                            @if (auth()->user()->branch_id)
                                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                                <div class="w-full px-4 py-2.5 border border-themeBorder rounded-xl bg-themeInput font-medium text-themeBody">
                                    {{ auth()->user()->branch->name }} ({{ auth()->user()->branch->code }})
                                </div>
                            @else
                                <select name="branch_id" id="wizard_branch_id" required
                                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                                    <option value="">Select branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div>
                            <label for="wizard_reference_number" class="block text-sm font-medium text-themeBody mb-2">Reference (optional)</label>
                            <input type="text" name="reference_number" id="wizard_reference_number" maxlength="128"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                                placeholder="e.g. PO-12345">
                        </div>
                        <div>
                            <label for="wizard_dealership_name" class="block text-sm font-medium text-themeBody mb-2">Dealership (optional)</label>
                            <input type="text" name="dealership_name" id="wizard_dealership_name" maxlength="255"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                                placeholder="Dealership name">
                        </div>
                        <div>
                            <label for="wizard_expected_at" class="block text-sm font-medium text-themeBody mb-2">Expected date (optional)</label>
                            <input type="date" name="expected_at" id="wizard_expected_at"
                                class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Products -->
                <div x-show="step === 2" x-transition class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Step 2 — Add products</h2>
                    <p class="text-sm text-themeMuted mb-4">Add one or more products with quantity. Cost is optional per line.</p>
                    <div class="space-y-3" id="wizard-product-rows">
                        <div class="wizard-product-row flex flex-wrap items-end gap-3 p-3 border border-themeBorder rounded-xl bg-themeInput/50">
                            <div class="flex-1 min-w-[160px]">
                                <label class="block text-xs font-medium text-themeMuted mb-1">Product *</label>
                                <select name="product_id[]" required
                                    class="w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading">
                                    <option value="">Select product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-20">
                                <label class="block text-xs font-medium text-themeMuted mb-1">Qty *</label>
                                <input type="number" name="quantity[]" min="1" value="1" required
                                    class="w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading">
                            </div>
                            <div class="w-28">
                                <label class="block text-xs font-medium text-themeMuted mb-1">Cost (opt)</label>
                                <input type="number" name="total_acquisition_cost[]" min="0" step="0.01" placeholder="0.00"
                                    class="w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading">
                            </div>
                            <button type="button" class="wizard-row-remove px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium" title="Remove">Remove</button>
                        </div>
                    </div>
                    <button type="button" id="wizard-add-product" class="mt-3 text-sm font-medium text-primary hover:underline">
                        + Add another product
                    </button>
                </div>

                <!-- Step 3: Review -->
                <div x-show="step === 3" x-transition class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Step 3 — Review & submit</h2>
                    <p class="text-sm text-themeMuted mb-4">Check the summary below and submit to create the restock order.</p>
                    <div class="rounded-xl border border-themeBorder bg-themeInput/50 p-4 space-y-2 text-sm" id="wizard-review-summary">
                        <p class="font-medium text-themeHeading">Summary will appear here after you complete steps 1 and 2.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Navigation -->
                <div class="flex items-center justify-between gap-4 pt-4">
                    <button type="button" x-show="step > 1" @click="step--"
                        class="px-5 py-2.5 rounded-xl font-medium bg-themeHover text-themeBody hover:bg-themeBorder transition">
                        Back
                    </button>
                    <div class="flex-1"></div>
                    <button type="button" x-show="step < 3" @click="nextStep()" x-cloak
                        class="px-5 py-2.5 rounded-xl font-medium bg-primary text-white hover:bg-primary-dark transition">
                        Next
                    </button>
                    <button type="submit" x-show="step === 3" x-cloak
                        class="px-5 py-2.5 rounded-xl font-medium bg-primary text-white hover:bg-primary-dark transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Create order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function restockWizard() {
            return {
                step: 1,
                nextStep() {
                    if (this.step === 1) {
                        const branch = document.querySelector('[name="branch_id"]');
                        if (branch && (branch.tagName === 'SELECT' ? !branch.value : false)) return;
                    }
                    if (this.step === 2) {
                        const firstProduct = document.querySelector('.wizard-product-row select[name="product_id[]"]');
                        const firstQty = document.querySelector('.wizard-product-row input[name="quantity[]"]');
                        if (firstProduct && !firstProduct.value) return;
                        if (firstQty && (!firstQty.value || parseInt(firstQty.value, 10) < 1)) return;
                        this.updateReview();
                    }
                    if (this.step < 3) this.step++;
                },
                updateReview() {
                    const branchSel = document.querySelector('[name="branch_id"]');
                    const branchName = branchSel && branchSel.tagName === 'SELECT'
                        ? (branchSel.options[branchSel.selectedIndex] && branchSel.options[branchSel.selectedIndex].text) || '—'
                        : document.querySelector('.bg-themeInput.font-medium')?.textContent?.trim() || '—';
                    const ref = document.getElementById('wizard_reference_number')?.value || '—';
                    const dealership = document.getElementById('wizard_dealership_name')?.value || '—';
                    const expected = document.getElementById('wizard_expected_at')?.value || '—';
                    const rows = document.querySelectorAll('.wizard-product-row');
                    let lines = [];
                    rows.forEach((row, i) => {
                        const sel = row.querySelector('select[name="product_id[]"]');
                        const qty = row.querySelector('input[name="quantity[]"]');
                        const cost = row.querySelector('input[name="total_acquisition_cost[]"]');
                        if (sel && sel.value && qty && qty.value) {
                            const name = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';
                            const costVal = cost && cost.value ? parseFloat(cost.value).toFixed(2) : '—';
                            lines.push((name || 'Product') + ' × ' + qty.value + (costVal !== '—' ? ' (Cost: ' + costVal + ')' : ''));
                        }
                    });
                    const el = document.getElementById('wizard-review-summary');
                    if (el) {
                        el.innerHTML = '<p class="font-medium text-themeHeading">Branch: ' + branchName + '</p>' +
                            '<p class="text-themeBody">Reference: ' + ref + '</p>' +
                            '<p class="text-themeBody">Dealership: ' + dealership + '</p>' +
                            '<p class="text-themeBody">Expected: ' + (expected !== '—' ? expected : '—') + '</p>' +
                            '<p class="font-medium text-themeHeading mt-3">Products:</p>' +
                            (lines.length ? '<ul class="list-disc list-inside text-themeBody">' + lines.map(l => '<li>' + l + '</li>').join('') + '</ul>' : '<p class="text-themeMuted">None added.</p>');
                    }
                }
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('wizard-product-rows');
            const addBtn = document.getElementById('wizard-add-product');
            if (!container || !addBtn) return;

            const productOptions = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])->values());

            addBtn.addEventListener('click', function() {
                const row = document.createElement('div');
                row.className = 'wizard-product-row flex flex-wrap items-end gap-3 p-3 border border-themeBorder rounded-xl bg-themeInput/50';
                let opts = '<option value="">Select product</option>';
                productOptions.forEach(function(p) {
                    opts += '<option value="' + p.id + '">' + (p.name || '') + ' (' + (p.sku || '') + ')</option>';
                });
                row.innerHTML = '<div class="flex-1 min-w-[160px]"><label class="block text-xs font-medium text-themeMuted mb-1">Product *</label><select name="product_id[]" class="w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading">' + opts + '</select></div>' +
                    '<div class="w-20"><label class="block text-xs font-medium text-themeMuted mb-1">Qty *</label><input type="number" name="quantity[]" min="1" value="1" required class="w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading"></div>' +
                    '<div class="w-28"><label class="block text-xs font-medium text-themeMuted mb-1">Cost (opt)</label><input type="number" name="total_acquisition_cost[]" min="0" step="0.01" placeholder="0.00" class="w-full px-3 py-2 border border-themeBorder rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium text-themeHeading"></div>' +
                    '<button type="button" class="wizard-row-remove px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium" title="Remove">Remove</button>';
                container.appendChild(row);
                row.querySelector('.wizard-row-remove').addEventListener('click', function() {
                    if (container.querySelectorAll('.wizard-product-row').length > 1) row.remove();
                });
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('wizard-row-remove') && container.querySelectorAll('.wizard-product-row').length > 1) {
                    e.target.closest('.wizard-product-row').remove();
                }
            });
        });
    </script>
@endsection
