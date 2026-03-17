@extends('layouts.app')

@section('title', $auditTemplate->name)

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('audit-templates.index'), 'label' => 'Back to Templates'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $auditTemplate->name }}</h1>
            @if (auth()->user()?->hasPermission('outlets.manage'))
                <a href="{{ route('audit-templates.edit', $auditTemplate) }}" class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Edit template</a>
            @endif
        </div>

        @if ($auditTemplate->description)
            <p class="text-themeBody">{{ $auditTemplate->description }}</p>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-themeHeading mb-4">Sections &amp; questions</h2>
            @forelse ($auditTemplate->sections as $section)
                <div class="mb-6 last:mb-0">
                    <h3 class="text-base font-medium text-primary mb-2">{{ $section->name }}</h3>
                    <ul class="list-disc list-inside space-y-1 text-sm text-themeBody">
                        @foreach ($section->questions as $q)
                            <li>
                                {{ $q->question_text }}
                                <span class="text-themeMuted">({{ $q->question_type }}{{ $q->question_type === 'score' && $q->score_max ? ' 0–' . $q->score_max : '' }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-themeMuted">No sections yet. Edit the template to add sections and questions.</p>
            @endforelse
        </div>
    </div>
@endsection
