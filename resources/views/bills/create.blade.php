@extends('layouts.app')

@section('title', 'New Bill')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('bills.index'), 'label' => 'Back to Bills'])
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">New Bill</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Record an incoming vendor invoice</p>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('bills.store') }}" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="vendor_id" class="block text-sm font-medium text-themeBody mb-2">Vendor *</label>
                        <select id="vendor_id" name="vendor_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">Select vendor</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                        <select id="branch_id" name="branch_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">Organization-wide</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-themeBody mb-2">Category</label>
                        <select id="category_id" name="category_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">None</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="invoice_number" class="block text-sm font-medium text-themeBody mb-2">Invoice number</label>
                        <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number') }}"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('invoice_number')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-themeBody mb-2">Invoice date *</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('invoice_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-themeBody mb-2">Due date *</label>
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('due_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-themeBody mb-2">Amount *</label>
                        <input type="number" id="amount" name="amount" value="{{ old('amount') }}" step="0.01" min="0" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('amount')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-themeBody mb-2">Currency</label>
                        <input type="text" id="currency" name="currency" value="{{ old('currency', 'TSh') }}" maxlength="10"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    </div>
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-themeBody mb-2">Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Create bill</button>
                    <a href="{{ route('bills.index') }}" class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
