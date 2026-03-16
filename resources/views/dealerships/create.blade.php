@extends('layouts.app')

@section('title', 'New Dealership')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('dealerships.index'), 'label' => 'Back to Dealerships'])
        <div>
            <h1 class="text-3xl font-semibold text-primary tracking-tight">New Dealership</h1>
            <p class="text-sm font-medium text-themeMuted mt-1">Add a dealership for restock orders</p>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('dealerships.store') }}" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-themeBody mb-2">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                        @error('name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="code" class="block text-sm font-medium text-themeBody mb-2">Code (optional)</label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}" maxlength="64"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                            placeholder="e.g. DL-001">
                        @error('code')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Create dealership</button>
                    <a href="{{ route('dealerships.index') }}" class="bg-themeHover text-themeBody px-6 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
