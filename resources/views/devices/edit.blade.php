@extends('layouts.app')

@section('title', 'Edit Device')

@section('content')
<div class="w-full space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Device</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">{{ $device->imei }}</p>
        </div>
        <a href="{{ route('devices.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Back</span>
        </a>
    </div>

    <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('devices.update', $device) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="imei" class="block text-sm font-medium text-themeBody mb-2">IMEI * (15 digits)</label>
                    <input type="text" id="imei" name="imei" value="{{ old('imei', $device->imei) }}" required maxlength="15" pattern="[0-9]{15}"
                           placeholder="Enter 15-digit IMEI number"
                           class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 15)">
                    <p class="text-xs font-medium text-themeMuted mt-1">IMEI must be exactly 15 digits (numbers only)</p>
                </div>

                <div>
                    <label for="product_id" class="block text-sm font-medium text-themeBody mb-2">Product *</label>
                    <select id="product_id" name="product_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id', $device->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="customer_id" class="block text-sm font-medium text-themeBody mb-2">Customer (Optional)</label>
                    <select id="customer_id" name="customer_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        <option value="">No Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $device->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('notes', $device->notes) }}</textarea>
                </div>
            </div>

            <div class="flex space-x-3">
                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Update Device</span>
                </button>
                <a href="{{ route('devices.index') }}" class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span>Cancel</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

