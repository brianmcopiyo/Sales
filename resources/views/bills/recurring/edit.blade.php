@extends('layouts.app')

@section('title', 'Edit Recurring Bill Template')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('bills.recurring.index'), 'label' => 'Back to Recurring Bills'])
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Recurring Bill Template</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">{{ $recurringBill->vendor?->name ?? 'Template' }} – {{ ucfirst($recurringBill->frequency) }}</p>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('bills.recurring.update', $recurringBill) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="vendor_id" class="block text-sm font-medium text-themeBody mb-2">Vendor *</label>
                        <select id="vendor_id" name="vendor_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id', $recurringBill->vendor_id) == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
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
                                <option value="{{ $b->id }}" {{ old('branch_id', $recurringBill->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
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
                                <option value="{{ $c->id }}" {{ old('category_id', $recurringBill->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-themeBody mb-2">Amount *</label>
                        <input type="number" id="amount" name="amount" value="{{ old('amount', $recurringBill->amount) }}" step="0.01" min="0" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('amount')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="frequency" class="block text-sm font-medium text-themeBody mb-2">Frequency *</label>
                        <select id="frequency" name="frequency" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="monthly" {{ old('frequency', $recurringBill->frequency) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="quarterly" {{ old('frequency', $recurringBill->frequency) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="yearly" {{ old('frequency', $recurringBill->frequency) === 'yearly' ? 'selected' : '' }}>Yearly</option>
                        </select>
                        @error('frequency')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="next_due_date" class="block text-sm font-medium text-themeBody mb-2">Next due date *</label>
                        <input type="date" id="next_due_date" name="next_due_date" value="{{ old('next_due_date', $recurringBill->next_due_date?->format('Y-m-d')) }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('next_due_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-themeBody mb-2">Description</label>
                        <textarea id="description" name="description" rows="2"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('description', $recurringBill->description) }}</textarea>
                        @error('description')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2 flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $recurringBill->is_active) ? 'checked' : '' }}
                            class="rounded border-themeBorder text-primary focus:ring-primary/20">
                        <label for="is_active" class="text-sm font-medium text-themeBody">Active (can create next bill from this template)</label>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Update template</button>
                    <a href="{{ route('bills.recurring.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
