@extends('layouts.app')

@section('title', 'Edit Petty Cash Fund')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit Fund</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $pettyCashFund->branch->name }}</p>
            </div>
            <a href="{{ route('petty-cash.funds.show', $pettyCashFund) }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to fund details</span>
            </a>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 max-w-2xl shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('petty-cash.funds.update', $pettyCashFund) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-themeBody mb-2">Branch</label>
                    <div class="px-4 py-2.5 rounded-xl border border-themeBorder bg-themeInput/50 text-themeBody font-medium">{{ $pettyCashFund->branch->name }}</div>
                    <p class="text-xs text-themeMuted mt-1">Branch cannot be changed.</p>
                </div>
                <div>
                    <label for="fund_limit" class="block text-sm font-medium text-themeBody mb-2">Fund Limit *</label>
                    <input type="number" id="fund_limit" name="fund_limit" step="0.01" min="0" value="{{ old('fund_limit', $pettyCashFund->fund_limit) }}" required
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
                            <option value="{{ $u->id }}" {{ old('custodian_user_id', $pettyCashFund->custodian_user_id) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="currency" class="block text-sm font-medium text-themeBody mb-2">Currency</label>
                    <input type="text" id="currency" name="currency" value="{{ old('currency', $pettyCashFund->currency) }}" maxlength="10"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    @error('currency')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <label class="flex items-center gap-2 text-sm font-medium text-themeBody">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $pettyCashFund->is_active) ? 'checked' : '' }}
                        class="rounded border-themeBorder text-primary focus:ring-primary/20"> Active
                </label>
                <div class="flex gap-2">
                    <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Update</button>
                    <a href="{{ route('petty-cash.funds.show', $pettyCashFund) }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
