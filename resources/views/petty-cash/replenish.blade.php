@extends('layouts.app')

@section('title', 'Replenish Petty Cash')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Replenish Fund</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $pettyCashFund->branch->name }} — Current balance: {{ $pettyCashFund->currency }} {{ number_format($pettyCashFund->current_balance, 2) }}</p>
            </div>
            <a href="{{ route('petty-cash.funds.show', $pettyCashFund) }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to fund details</span>
            </a>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-xl shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('petty-cash.replenish.store', $pettyCashFund) }}" class="space-y-6">
                @csrf
                <div>
                    <label for="amount" class="block text-sm font-medium text-themeBody mb-2">Amount *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                        placeholder="0.00">
                    @error('amount')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="reference" class="block text-sm font-medium text-themeBody mb-2">Reference (e.g. bank transfer)</label>
                    <input type="text" id="reference" name="reference" value="{{ old('reference') }}"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                        placeholder="Optional">
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium text-themeBody mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="2"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('notes') }}</textarea>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Replenish fund
                    </button>
                    <a href="{{ route('petty-cash.funds.show', $pettyCashFund) }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
