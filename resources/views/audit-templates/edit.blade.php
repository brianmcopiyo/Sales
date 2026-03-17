@extends('layouts.app')

@section('title', 'Edit Audit Template')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('audit-templates.show', $auditTemplate), 'label' => 'Back to Template'])
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Edit: {{ $auditTemplate->name }}</h1>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">{{ session('success') }}</div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-themeHeading mb-4">Template details</h2>
            <form method="POST" action="{{ route('audit-templates.update', $auditTemplate) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-themeBody mb-1">Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $auditTemplate->name) }}" required class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-2 pt-8">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $auditTemplate->is_active) ? 'checked' : '' }} class="rounded border-themeBorder text-primary focus:ring-primary/20">
                        <label for="is_active" class="text-sm font-medium text-themeBody">Active</label>
                    </div>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-themeBody mb-1">Description</label>
                    <textarea id="description" name="description" rows="2" class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('description', $auditTemplate->description) }}</textarea>
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Save template</button>
            </form>
        </div>

        @foreach ($auditTemplate->sections as $section)
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h3 class="text-base font-semibold text-themeHeading">{{ $section->name }}</h3>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('audit-sections.update', $section) }}" class="inline flex items-center gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $section->name }}" class="w-48 px-3 py-1.5 border border-themeBorder rounded-lg text-sm">
                            <input type="number" name="sort_order" value="{{ $section->sort_order }}" min="0" class="w-16 px-2 py-1.5 border border-themeBorder rounded-lg text-sm">
                            <button type="submit" class="text-sm text-primary hover:underline">Update</button>
                        </form>
                        <form method="POST" action="{{ route('audit-sections.destroy', $section) }}" class="inline" onsubmit="return confirm('Remove this section and its questions?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
                <ul class="space-y-3 mb-4">
                    @foreach ($section->questions as $q)
                        <li class="flex flex-wrap items-start gap-2 py-2 border-b border-themeBorder last:border-0">
                            <span class="flex-1 text-sm text-themeBody">{{ $q->question_text }} <span class="text-themeMuted">({{ $q->question_type }}{{ $q->score_max ? ', 0–' . $q->score_max : '' }})</span></span>
                            <form method="POST" action="{{ route('audit-questions.update', $q) }}" class="inline flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="question_text" value="{{ $q->question_text }}" class="min-w-[200px] px-3 py-1.5 border border-themeBorder rounded-lg text-sm">
                                <select name="question_type" class="px-2 py-1.5 border border-themeBorder rounded-lg text-sm">
                                    <option value="yes_no" {{ $q->question_type === 'yes_no' ? 'selected' : '' }}>Yes/No</option>
                                    <option value="score" {{ $q->question_type === 'score' ? 'selected' : '' }}>Score</option>
                                    <option value="photo" {{ $q->question_type === 'photo' ? 'selected' : '' }}>Photo</option>
                                </select>
                                <input type="number" name="score_max" value="{{ $q->score_max }}" min="1" max="10" placeholder="Max" class="w-14 px-2 py-1.5 border border-themeBorder rounded-lg text-sm">
                                <input type="number" name="sort_order" value="{{ $q->sort_order }}" min="0" class="w-14 px-2 py-1.5 border border-themeBorder rounded-lg text-sm">
                                <button type="submit" class="text-sm text-primary hover:underline">Update</button>
                            </form>
                            <form method="POST" action="{{ route('audit-questions.destroy', $q) }}" class="inline" onsubmit="return confirm('Remove this question?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('audit-sections.questions.store', $section) }}" class="flex flex-wrap gap-2 p-3 bg-themeInput/30 rounded-xl">
                    @csrf
                    <input type="text" name="question_text" placeholder="New question text" required class="flex-1 min-w-[180px] px-3 py-2 border border-themeBorder rounded-lg text-sm">
                    <select name="question_type" class="px-3 py-2 border border-themeBorder rounded-lg text-sm">
                        <option value="yes_no">Yes/No</option>
                        <option value="score">Score</option>
                        <option value="photo">Photo</option>
                    </select>
                    <input type="number" name="score_max" placeholder="Max (score)" min="1" max="10" class="w-20 px-2 py-2 border border-themeBorder rounded-lg text-sm">
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-dark transition">Add question</button>
                </form>
            </div>
        @endforeach

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <h3 class="text-base font-semibold text-themeHeading mb-3">Add section</h3>
            <form method="POST" action="{{ route('audit-templates.sections.store', $auditTemplate) }}" class="flex flex-wrap gap-2">
                @csrf
                <input type="text" name="name" placeholder="Section name" required class="min-w-[200px] px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20">
                <input type="number" name="sort_order" placeholder="Order" min="0" value="{{ $auditTemplate->sections->max('sort_order') + 1 }}" class="w-24 px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading">
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Add section</button>
            </form>
        </div>
    </div>
@endsection
