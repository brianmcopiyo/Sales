@extends('layouts.app')

@section('title', 'Create Audit Template')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('audit-templates.index'), 'label' => 'Back to Templates'])
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Create Audit Template</h1>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm max-w-2xl">
            <form method="POST" action="{{ route('audit-templates.store') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-themeBody mb-1">Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary @error('name') border-red-300 @enderror">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-themeBody mb-1">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('description') }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                        class="rounded border-themeBorder text-primary focus:ring-primary/20">
                    <label for="is_active" class="text-sm font-medium text-themeBody">Active</label>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Create</button>
                    <a href="{{ route('audit-templates.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
