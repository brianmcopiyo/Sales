@extends('layouts.app')

@section('title', 'Fill audit')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('check-ins.index'), 'label' => 'Back to Check-ins'])
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Outlet audit: {{ $auditRun->checkIn->outlet->name }}</h1>
        <p class="text-themeBody">Template: <strong>{{ $auditRun->template->name }}</strong>. Complete the checklist below.</p>

        <form method="POST" action="{{ route('audit-runs.submit', $auditRun) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @foreach ($auditRun->template->sections as $section)
                <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-themeHeading mb-4">{{ $section->name }}</h2>
                    <div class="space-y-4">
                        @foreach ($section->questions as $q)
                            <div class="border-b border-themeBorder pb-4 last:border-0">
                                <label class="block text-sm font-medium text-themeBody mb-2">{{ $q->question_text }}</label>
                                @if ($q->question_type === 'yes_no')
                                    <div class="flex gap-4">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio" name="answers[{{ $q->id }}]" value="yes" required class="text-primary focus:ring-primary/20">
                                            <span>Yes</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio" name="answers[{{ $q->id }}]" value="no" class="text-primary focus:ring-primary/20">
                                            <span>No</span>
                                        </label>
                                    </div>
                                @elseif ($q->question_type === 'score')
                                    <select name="answers[{{ $q->id }}]" required class="w-full max-w-xs px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        @for ($i = 0; $i <= ($q->score_max ?? 5); $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                @else
                                    <input type="file" name="photo[{{ $q->id }}]" accept="image/*" class="w-full text-sm text-themeBody file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-primary file:text-white file:font-medium">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div class="flex gap-3">
                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Submit audit</button>
                <a href="{{ route('check-ins.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
            </div>
        </form>
    </div>
@endsection
