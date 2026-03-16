@extends('layouts.app')

@section('title', 'Request Petty Cash')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Request Petty Cash</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Submit a petty cash request for approval</p>
            </div>
            <a href="{{ route('petty-cash.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back</span>
            </a>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-2xl shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
        <form method="POST" action="{{ route('petty-cash.request.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div>
                <label for="petty_cash_fund_id" class="block text-sm font-medium text-themeBody mb-2">Branch / Fund *</label>
                <select id="petty_cash_fund_id" name="petty_cash_fund_id" required class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    <option value="">Select branch fund</option>
                    @foreach($funds as $f)
                        <option value="{{ $f->id }}" {{ old('petty_cash_fund_id') == $f->id ? 'selected' : '' }}>{{ $f->branch->name }} ({{ $f->currency }} {{ number_format($f->current_balance, 2) }} available)</option>
                    @endforeach
                </select>
                @error('petty_cash_fund_id')
                    <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="amount" class="block text-sm font-medium text-themeBody mb-2">Amount *</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading" placeholder="0.00">
                @error('amount')
                    <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="petty_cash_category_id" class="block text-sm font-medium text-themeBody mb-2">Category</label>
                <select id="petty_cash_category_id" name="petty_cash_category_id" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    <option value="">—</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat->id }}" {{ old('petty_cash_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="reason" class="block text-sm font-medium text-themeBody mb-2">Reason / description</label>
                <textarea id="reason" name="reason" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading" placeholder="What is this for?">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="attachment" class="block text-sm font-medium text-themeBody mb-2">Attachment (optional)</label>
                <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                    class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                <p class="text-xs text-themeMuted mt-1">Optional. PDF or image (JPG, PNG, GIF, WebP). Max 5 MB.</p>
                @error('attachment')
                    <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    Submit request
                </button>
                <a href="{{ route('petty-cash.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                    Cancel
                </a>
            </div>
        </form>
        </div>
    </div>
@endsection
