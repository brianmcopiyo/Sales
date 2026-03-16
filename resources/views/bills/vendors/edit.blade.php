@extends('layouts.app')

@section('title', 'Edit Vendor')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('bills.vendors.index'), 'label' => 'Back to Vendors'])
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Vendor</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">{{ $vendor->name }}</p>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('bills.vendors.update', $vendor) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-themeBody mb-2">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $vendor->name) }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="contact_person" class="block text-sm font-medium text-themeBody mb-2">Contact person</label>
                        <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $vendor->contact_person) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-themeBody mb-2">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $vendor->email) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-themeBody mb-2">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $vendor->phone) }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>
                    <div>
                        <label for="default_payment_terms" class="block text-sm font-medium text-themeBody mb-2">Default payment terms</label>
                        <select id="default_payment_terms" name="default_payment_terms"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">None</option>
                            <option value="due_on_receipt" {{ old('default_payment_terms', $vendor->default_payment_terms) === 'due_on_receipt' ? 'selected' : '' }}>Due on receipt</option>
                            <option value="net_30" {{ old('default_payment_terms', $vendor->default_payment_terms) === 'net_30' ? 'selected' : '' }}>Net 30</option>
                            <option value="custom" {{ old('default_payment_terms', $vendor->default_payment_terms) === 'custom' ? 'selected' : '' }}>Custom days</option>
                        </select>
                    </div>
                    <div>
                        <label for="terms_days" class="block text-sm font-medium text-themeBody mb-2">Terms (days)</label>
                        <input type="number" id="terms_days" name="terms_days" value="{{ old('terms_days', $vendor->terms_days) }}" min="1" max="365"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-themeBody mb-2">Address</label>
                        <textarea id="address" name="address" rows="2"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('address', $vendor->address) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes</label>
                        <textarea id="notes" name="notes" rows="2"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('notes', $vendor->notes) }}</textarea>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $vendor->is_active) ? 'checked' : '' }}
                                class="rounded border-themeBorder text-primary focus:ring-primary/20">
                            <span class="text-sm font-medium text-themeBody">Active</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Update vendor</button>
                    <a href="{{ route('bills.vendors.index') }}" class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
