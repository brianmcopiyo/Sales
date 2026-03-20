@extends('layouts.portal')

@section('title', 'Submit a Claim')

@section('content')
<div class="py-6 max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('portal.claims.index') }}" class="text-sm hover:underline" style="color:#6b7280;">&larr; Back to Claims</a>
    </div>

    <h1 class="text-xl font-bold mb-6" style="color:#111827;">Submit a Claim</h1>

    <div class="rounded-xl border shadow-sm p-6" style="background:#fff; border-color:#e5e7eb;">
        <form method="POST" action="{{ route('portal.claims.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Claim Type --}}
            <div class="mb-5">
                <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Claim Type <span class="text-red-500">*</span></label>
                <select name="type" required
                        class="w-full text-sm border rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary/20 @error('type') border-red-400 @enderror"
                        style="border-color:{{ $errors->has('type') ? '#f87171' : '#e5e7eb' }};">
                    <option value="">Select type...</option>
                    @foreach (\App\Models\DistributorClaim::TYPES as $val => $label)
                        <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Reference Sale --}}
            <div class="mb-5">
                <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Related Order (optional)</label>
                <select name="reference_sale_id"
                        class="w-full text-sm border rounded-lg px-3 py-2.5 focus:outline-none"
                        style="border-color:#e5e7eb;">
                    <option value="">None</option>
                    @foreach ($recentSales as $sale)
                        <option value="{{ $sale->id }}"
                            {{ (old('reference_sale_id') ?? $preselectedSaleId) === $sale->id ? 'selected' : '' }}>
                            {{ $sale->sale_number ?? $sale->id }} — {{ $sale->created_at->format('M d, Y') }} ({{ number_format($sale->total, 2) }})
                        </option>
                    @endforeach
                </select>
                @error('reference_sale_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Amount --}}
            <div class="mb-5">
                <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Amount Claimed (optional)</label>
                <input type="number" name="amount_claimed" value="{{ old('amount_claimed') }}"
                       step="0.01" min="0" placeholder="0.00"
                       class="w-full text-sm border rounded-lg px-3 py-2.5 focus:outline-none @error('amount_claimed') border-red-400 @enderror"
                       style="border-color:{{ $errors->has('amount_claimed') ? '#f87171' : '#e5e7eb' }};">
                @error('amount_claimed')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div class="mb-5">
                <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Description <span class="text-red-500">*</span></label>
                <textarea name="description" rows="5" required minlength="10" maxlength="2000"
                          placeholder="Describe the issue in detail..."
                          class="w-full text-sm border rounded-lg px-3 py-2.5 focus:outline-none resize-none @error('description') border-red-400 @enderror"
                          style="border-color:{{ $errors->has('description') ? '#f87171' : '#e5e7eb' }};">{{ old('description') }}</textarea>
                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Attachments --}}
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1.5" style="color:#374151;">Supporting Documents (optional)</label>
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:text-white"
                       style="--file-bg:#006F78;">
                <p class="text-xs mt-1" style="color:#6b7280;">Accepted: JPG, PNG, PDF. Max 5MB each.</p>
                @error('attachments.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="text-sm px-5 py-2.5 rounded-lg font-medium text-white transition-opacity hover:opacity-90"
                        style="background-color:#006F78;">
                    Submit Claim
                </button>
                <a href="{{ route('portal.claims.index') }}"
                   class="text-sm px-5 py-2.5 rounded-lg font-medium border transition-colors hover:bg-gray-50"
                   style="border-color:#e5e7eb; color:#374151;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
