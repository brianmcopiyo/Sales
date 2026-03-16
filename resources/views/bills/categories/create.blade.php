@extends('layouts.app')

@section('title', 'New Bill Category')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('bills.categories.index'), 'label' => 'Back to Categories'])
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">New Bill Category</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Add a category for bills (e.g. Rent, Utilities)</p>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('bills.categories.store') }}" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-themeBody mb-2">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="80"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2 flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="rounded border-themeBorder text-primary focus:ring-primary/20">
                        <label for="is_active" class="text-sm font-medium text-themeBody">Active</label>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Create category</button>
                    <a href="{{ route('bills.categories.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
