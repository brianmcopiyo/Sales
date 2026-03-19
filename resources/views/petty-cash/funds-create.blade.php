@extends('layouts.app')

@section('title', 'Create Petty Cash Fund')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Create New Fund</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Add a petty cash fund for a branch</p>
            </div>
            <a href="{{ route('petty-cash.funds.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to Manage Funds</span>
            </a>
        </div>

        @if($branchesWithoutFund->isEmpty())
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <p class="text-themeMuted font-medium">Every branch already has a petty cash fund. You cannot create another until a fund is removed or you have more branches.</p>
                <a href="{{ route('petty-cash.funds.index') }}" class="inline-block mt-4 text-primary font-medium hover:underline">Return to Manage Funds</a>
            </div>
        @else
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-2xl shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <form method="POST" action="{{ route('petty-cash.funds.store') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-themeBody mb-2">Branch *</label>
                        <select id="branch_id" name="branch_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">Select branch</option>
                            @foreach($branchesWithoutFund as $b)
                                <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="fund_limit" class="block text-sm font-medium text-themeBody mb-2">Fund Limit *</label>
                        <input type="number" id="fund_limit" name="fund_limit" step="0.01" min="0" value="{{ old('fund_limit', 0) }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('fund_limit')
                            <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="custodian_user_id" class="block text-sm font-medium text-themeBody mb-2">Custodian</label>
                        <select id="custodian_user_id" name="custodian_user_id"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                            <option value="">None</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ old('custodian_user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-themeBody mb-2">Currency</label>
                        <input type="text" id="currency" name="currency" value="{{ old('currency', config('app.currency_symbol')) }}" maxlength="10"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('currency')
                            <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                            class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                            Create fund
                        </button>
                        <a href="{{ route('petty-cash.funds.index') }}"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
