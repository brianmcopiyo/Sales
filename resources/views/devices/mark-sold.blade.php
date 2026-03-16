@extends('layouts.app')

@section('title', 'Mark Device as Sold')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Mark device as sold</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $device->imei }} ·
                    {{ $device->product->name ?? 'Device' }}</p>
            </div>
            <a href="{{ route('devices.show', $device) }}"
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
            <form method="POST" action="{{ route('devices.mark-sold.submit', $device) }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="customer_id" class="block text-sm font-medium text-themeBody mb-2">Customer
                            <span class="text-red-500">*</span></label>
                        <p class="text-xs text-themeMuted mb-2">Required for every sale.
                        </p>
                        <select id="customer_id" name="customer_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">— Select customer —</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id', $device->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} @if ($customer->phone)
                                        ({{ $customer->phone }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="unit_price" class="block text-sm font-medium text-themeBody mb-2">Unit price (TSh)
                            *</label>
                        <input type="number" id="unit_price" name="unit_price"
                            value="{{ old('unit_price', $defaultUnitPrice) }}" required min="0" step="0.01"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                            placeholder="0.00">
                        <p class="text-xs font-medium text-themeMuted mt-1">Default from region pricing; you can change it.
                        </p>
                        @error('unit_price')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="customer_support_amount" class="block text-sm font-medium text-themeBody mb-2">Customer
                            support amount (TSh)</label>
                        <input type="number" id="customer_support_amount" name="customer_support_amount"
                            value="{{ old('customer_support_amount', 0) }}" min="0" step="0.01"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                            placeholder="0.00">
                        <p class="text-xs font-medium text-themeMuted mt-1">Optional. Creates a disbursement record for this
                            device.</p>
                        @error('customer_support_amount')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit"
                        class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">
                        Mark as sold & create sale
                    </button>
                    <a href="{{ route('devices.show', $device) }}"
                        class="text-themeBody hover:text-themeHeading font-medium">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

