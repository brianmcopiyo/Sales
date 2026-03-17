@extends('layouts.app')

@section('title', 'Start Outlet Audit')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('check-ins.index'), 'label' => 'Back to Check-ins'])
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Start outlet audit</h1>

        <p class="text-themeBody">Visit: <strong>{{ $checkIn->outlet->name }}</strong> on {{ $checkIn->check_in_at->format('d M Y H:i') }}. Select a template to run the audit.</p>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm max-w-xl">
            <form method="POST" action="{{ route('audit-runs.store') }}">
                @csrf
                <input type="hidden" name="check_in_id" value="{{ $checkIn->id }}">
                <div class="mb-4">
                    <label for="audit_template_id" class="block text-sm font-medium text-themeBody mb-2">Template *</label>
                    <select id="audit_template_id" name="audit_template_id" required class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition">Start audit</button>
                    <a href="{{ route('check-ins.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
