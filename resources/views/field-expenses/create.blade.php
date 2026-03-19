@extends('layouts.app')

@section('title', 'New Field Expense')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-3xl font-semibold text-primary tracking-tight">New Field Expense</h1>

    <form method="POST" action="{{ route('field-expenses.store') }}" class="bg-themeCard border border-themeBorder rounded-2xl p-6 space-y-4 shadow-sm">
        @csrf
        <div>
            <label class="block text-sm font-medium text-themeBody mb-2">Date</label>
            <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-themeBody mb-2">Outlet (optional)</label>
            <select name="outlet_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
                <option value="">None</option>
                @foreach ($outlets as $o)
                    <option value="{{ $o->id }}" {{ old('outlet_id') === $o->id ? 'selected' : '' }}>{{ $o->name }}{{ $o->code ? ' (' . $o->code . ')' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-themeBody mb-2">Category</label>
                <input type="text" name="category" value="{{ old('category') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading" placeholder="Transport, Meals, Fuel" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-themeBody mb-2">Currency</label>
                <input type="text" name="currency" value="{{ old('currency', 'KES') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-themeBody mb-2">Amount</label>
            <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-themeBody mb-2">Description (optional)</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">{{ old('description') }}</textarea>
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('field-expenses.index') }}" class="px-4 py-2 rounded-xl border border-themeBorder text-themeBody">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-white hover:bg-primary-dark transition">Submit</button>
        </div>
    </form>
</div>
@endsection
