@extends('layouts.app')

@section('title', 'Plan day')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('planned-visits.index'), 'label' => 'Back to Planned Visits'])
        <h1 class="text-3xl font-semibold text-primary tracking-tight">Plan day</h1>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm">
            <form method="POST" action="{{ route('planned-visits.store') }}" id="plan-form">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-themeBody mb-1">Rep (user) *</label>
                        <select id="user_id" name="user_id" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select user</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" {{ ($defaultUserId ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="planned_date" class="block text-sm font-medium text-themeBody mb-1">Date *</label>
                        <input type="date" id="planned_date" name="planned_date" value="{{ $defaultDate ?? today()->format('Y-m-d') }}" required
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div class="md:col-span-2">
                        <label for="outlet_ids" class="block text-sm font-medium text-themeBody mb-1">Outlets to visit (select in route order) *</label>
                        <select id="outlet_ids" name="outlet_ids[]" multiple size="12"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @foreach ($outlets as $o)
                                <option value="{{ $o->id }}">{{ $o->name }}{{ $o->code ? ' (' . $o->code . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-themeMuted">Hold Ctrl/Cmd to select multiple. Order = sequence.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-themeBody mb-1">Notes (optional)</label>
                        <textarea id="notes" name="notes" rows="2"
                            class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 mt-6">
                    <button type="submit" class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                        Save plan
                    </button>
                    <a href="{{ route('planned-visits.index') }}" class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
