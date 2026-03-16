@extends('layouts.app')

@section('title', 'Edit category – Petty Cash')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit category</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $pettyCashCategory->name }}</p>
            </div>
            <a href="{{ route('petty-cash.categories.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to Categories</span>
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('petty-cash.categories.update', $pettyCashCategory) }}" class="space-y-4 max-w-xl">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="block text-sm font-medium text-themeBody mb-2">Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $pettyCashCategory->name) }}" required maxlength="80"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading"
                        placeholder="e.g. Office supplies">
                    @error('name')
                        <p class="text-red-500 text-sm font-light mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-themeBody mb-2">Description (optional)</label>
                    <textarea id="description" name="description" rows="2" maxlength="500"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">{{ old('description', $pettyCashCategory->description) }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $pettyCashCategory->is_active) ? 'checked' : '' }}
                        class="rounded border-themeBorder text-primary focus:ring-primary/20">
                    <label for="is_active" class="text-sm font-medium text-themeBody">Active (show in request form)</label>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Update category
                    </button>
                    <a href="{{ route('petty-cash.categories.index') }}"
                        class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
