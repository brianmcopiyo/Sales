@extends('layouts.app')

@section('title', 'Audit result')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('audit-reports.index'), 'label' => 'Back to Audit reports'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Audit: {{ $auditRun->checkIn->outlet->name }}</h1>
            @if ($auditRun->compliance_score !== null)
                <span class="inline-flex items-center px-4 py-2 rounded-xl text-lg font-semibold {{ $auditRun->compliance_score >= 80 ? 'bg-emerald-100 text-emerald-800' : ($auditRun->compliance_score >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                    Compliance: {{ $auditRun->compliance_score }}%
                </span>
            @endif
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-themeHeading mb-4">Visit</h2>
            <p class="text-themeBody">Outlet: <a href="{{ route('outlets.show', $auditRun->checkIn->outlet) }}" class="text-primary hover:underline">{{ $auditRun->checkIn->outlet->name }}</a></p>
            <p class="text-themeBody">Rep: {{ $auditRun->checkIn->user->name ?? '—' }}</p>
            <p class="text-themeBody">Completed: {{ $auditRun->completed_at ? $auditRun->completed_at->format('d M Y H:i') : '—' }}</p>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-themeHeading mb-4">Answers</h2>
            @foreach ($auditRun->template->sections as $section)
                <div class="mb-6 last:mb-0">
                    <h3 class="text-base font-medium text-primary mb-2">{{ $section->name }}</h3>
                    <ul class="space-y-2 text-sm text-themeBody">
                        @foreach ($section->questions as $q)
                            @php $ans = $auditRun->answers->firstWhere('audit_question_id', $q->id); @endphp
                            <li class="flex flex-wrap gap-2 items-start">
                                <span class="font-medium">{{ $q->question_text }}:</span>
                                @if ($q->question_type === 'photo' && $ans?->photo_path)
                                    <span><img src="{{ asset('storage/' . $ans->photo_path) }}" alt="Photo" class="max-h-24 rounded border border-themeBorder"></span>
                                @else
                                    <span>{{ $ans?->answer_value ?? '—' }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
@endsection
