@extends('layouts.app')

@section('title', 'Create Customer Disbursement')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Create Customer Disbursement</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Record a support payment for a customer</p>
            </div>
            <a href="{{ route('customer-disbursements.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-2xl shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('customer-disbursements.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="customer_id" class="block text-sm font-medium text-themeBody mb-2">Customer *</label>
                    <select id="customer_id" name="customer_id" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">Select Customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ old('customer_id', $customerId) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}@if ($customer->phone)
                                    ({{ $customer->phone }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="device_field_wrapper" class="{{ $sale ? 'hidden' : '' }}">
                    <label for="device_id" class="block text-sm font-medium text-themeBody mb-2">Device (IMEI) <span id="device_required_star" class="{{ $sale ? 'hidden' : '' }}">*</span></label>
                    <select id="device_id" name="device_id"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                        {{ !$customerId && !$sale ? 'disabled' : '' }}
                        {{ $sale ? 'disabled' : '' }}
                        {{ !$sale ? 'required' : '' }}>
                        <option value="">Select Device</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}" {{ old('device_id', $defaultDeviceId ?? null) == $device->id ? 'selected' : '' }}>
                                {{ $device->imei }} - {{ $device->product->name ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-themeMuted font-light mt-1" id="device_field_hint">
                        {{ !$customerId && !$sale ? 'Select a customer above to see their devices.' : 'Select the device (IMEI) that will receive this disbursement.' }}
                    </p>
                    @error('device_id')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if ($sale)
                    <div class="p-4 bg-sky-50 border border-sky-100 rounded-xl">
                        <div class="text-sm font-medium text-sky-800">
                            <strong>Linked Sale:</strong> <a href="{{ route('sales.show', $sale) }}"
                                class="underline">{{ $sale->sale_number }}</a> - TSh {{ number_format($sale->total, 2) }}
                        </div>
                    </div>
                    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                @else
                    <div>
                        <label for="sale_id" class="block text-sm font-medium text-themeBody mb-2">Sale *</label>
                        <select id="sale_id" name="sale_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                            {{ !$customerId ? 'disabled' : '' }}>
                            <option value="">Select a sale</option>
                            @if ($customerId)
                                @php
                                    $customerSales = \App\Models\Sale::where('customer_id', $customerId)
                                        ->whereDoesntHave('customerDisbursements')
                                        ->latest()
                                        ->get();
                                @endphp
                                @foreach ($customerSales as $customerSale)
                                    <option value="{{ $customerSale->id }}"
                                        {{ old('sale_id', $saleId) == $customerSale->id ? 'selected' : '' }}>
                                        {{ $customerSale->sale_number }} - TSh
                                        {{ number_format($customerSale->total, 2) }}
                                        ({{ $customerSale->created_at->format('M d, Y') }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <p class="text-xs text-themeMuted font-light mt-1">
                            {{ !$customerId ? 'Select a customer above to see their sales.' : 'Every disbursement must be linked to a sale. Select the sale for this device.' }}
                        </p>
                        @error('sale_id')
                            <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label for="amount" class="block text-sm font-medium text-themeBody mb-2">Amount *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required
                        value="{{ old('amount') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    @error('amount')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="disbursement_phone" class="block text-sm font-medium text-themeBody mb-2">Disbursement Phone
                        Number *</label>
                    <input type="text" id="disbursement_phone" name="disbursement_phone" required
                        value="{{ old('disbursement_phone', $defaultPhone ?? '') }}"
                        placeholder="Enter phone number where funds will be sent"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    <p class="text-xs text-themeMuted font-light mt-1">Phone number where the funds will be disbursed.</p>
                    @error('disbursement_phone')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="4" maxlength="1000"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('notes') }}</textarea>
                    <p class="text-xs text-themeMuted font-light mt-1">Add any additional information about this disbursement.
                    </p>
                    @error('notes')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex space-x-4">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2 rounded hover:bg-[#005a61] transition font-light flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Create Disbursement</span>
                    </button>
                    <a href="{{ route('customer-disbursements.index') }}"
                        class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if (!$sale)
        <script>
            // Customer data for phone lookup
            const customers = @json(
                $customers->mapWithKeys(function ($customer) {
                    return [$customer->id => $customer->phone];
                }));

            function loadDevices(customerId, saleId = null) {
                const deviceSelect = document.getElementById('device_id');
                const url = saleId ?
                    `/api/sales/${saleId}/devices` :
                    `/api/customers/${customerId}/devices`;

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Failed to load devices');
                        return response.json();
                    })
                    .then(data => {
                        const list = Array.isArray(data) ? data : [];
                        const hintEl = document.getElementById('device_field_hint');
                        deviceSelect.innerHTML = '<option value="">Select Device</option>';
                        list.forEach(device => {
                            const option = document.createElement('option');
                            option.value = device.id;
                            option.textContent = `${device.imei} - ${device.product?.name || 'N/A'}`;
                            deviceSelect.appendChild(option);
                        });
                        if (list.length > 0) {
                            deviceSelect.value = list[0].id;
                            if (hintEl) hintEl.textContent = 'Select the device (IMEI) that will receive this disbursement.';
                        } else if (saleId && hintEl) {
                            hintEl.textContent = 'No devices from this sale are eligible (they may have already received a disbursement).';
                        } else if (hintEl) {
                            hintEl.textContent = list.length === 0 && !saleId ? 'Select a customer (and optionally a sale) to see devices.' : 'Select the device (IMEI) that will receive this disbursement.';
                        }
                        deviceSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error fetching devices:', error);
                        deviceSelect.innerHTML = '<option value="">Error loading devices</option>';
                        deviceSelect.disabled = false;
                    });
            }

            document.getElementById('customer_id').addEventListener('change', function() {
                const customerId = this.value;
                const saleSelect = document.getElementById('sale_id');
                const deviceSelect = document.getElementById('device_id');
                const phoneInput = document.getElementById('disbursement_phone');

                toggleDeviceField(false);

                // Update phone field with customer's phone
                if (customerId && customers[customerId]) {
                    phoneInput.value = customers[customerId];
                }

                if (!customerId) {
                    saleSelect.innerHTML = '<option value="">Select customer first</option>';
                    saleSelect.disabled = true;
                    deviceSelect.innerHTML = '<option value="">Select customer first</option>';
                    deviceSelect.disabled = true;
                    return;
                }

                // Load devices for the customer
                loadDevices(customerId);

                // Fetch sales for the selected customer
                fetch(`/api/customers/${customerId}/sales`)
                    .then(response => response.json())
                    .then(data => {
                        saleSelect.innerHTML = '<option value="">Select a sale</option>';
                        data.forEach(sale => {
                            const option = document.createElement('option');
                            option.value = sale.id;
                            option.textContent =
                                `${sale.sale_number} - TSh ${parseFloat(sale.total).toFixed(2)} (${new Date(sale.created_at).toLocaleDateString()})`;
                            saleSelect.appendChild(option);
                        });
                        saleSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error fetching sales:', error);
                        saleSelect.innerHTML = '<option value="">Error loading sales</option>';
                    });
            });

            // When a sale is linked, hide the device field (backend will resolve device from sale)
            function toggleDeviceField(saleSelected) {
                const wrapper = document.getElementById('device_field_wrapper');
                const deviceSelect = document.getElementById('device_id');
                const star = document.getElementById('device_required_star');
                if (!wrapper || !deviceSelect) return;
                if (saleSelected) {
                    wrapper.classList.add('hidden');
                    deviceSelect.disabled = true;
                    deviceSelect.removeAttribute('required');
                    if (star) star.classList.add('hidden');
                } else {
                    wrapper.classList.remove('hidden');
                    deviceSelect.disabled = !document.getElementById('customer_id')?.value;
                    deviceSelect.setAttribute('required', 'required');
                    if (star) star.classList.remove('hidden');
                }
            }

            // Update devices when sale is selected; hide device field when sale is linked
            document.getElementById('sale_id')?.addEventListener('change', function() {
                const saleId = this.value;
                const customerId = document.getElementById('customer_id').value;
                toggleDeviceField(!!saleId);
                if (saleId && customerId) {
                    loadDevices(customerId, saleId);
                } else if (customerId) {
                    loadDevices(customerId);
                }
            });

            // When page loads with customer and sale already selected, hide device field and load sale's devices
            document.addEventListener('DOMContentLoaded', function() {
                const customerId = document.getElementById('customer_id')?.value;
                const saleId = document.getElementById('sale_id')?.value;
                toggleDeviceField(!!saleId);
                if (saleId && customerId) {
                    loadDevices(customerId, saleId);
                } else if (customerId) {
                    loadDevices(customerId);
                }
            });
        </script>
    @endif
@endsection

