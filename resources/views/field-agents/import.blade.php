@extends('layouts.app')

@section('title', 'Import Field Agents')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', [
            'href' => route('field-agents.index'),
            'label' => 'Back to Field Agents',
        ])
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Import Field Agents</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Upload a CSV or Excel file to add multiple field agents at once.
                </p>
            </div>
        </div>

        <!-- Import form -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <form method="POST" action="{{ route('field-agents.import.submit') }}" enctype="multipart/form-data"
                class="flex flex-wrap gap-4 items-end">
                @csrf
                <div class="min-w-[240px] flex-1">
                    <label for="file" class="block text-sm font-medium text-themeBody mb-2">File (CSV or Excel) *</label>
                    <input type="file" id="file" name="file" accept=".csv,.xlsx,.xls" required
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium text-themeHeading">
                    <p class="text-xs font-medium text-themeMuted mt-1">Max 5 MB. Download sample for column format.</p>
                </div>
                <button type="submit"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    <span>Import</span>
                </button>
                <a href="{{ route('field-agents.import.sample') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <span>Download sample CSV</span>
                </a>
                <a href="{{ route('field-agents.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
            </form>
        </div>

        <!-- Required columns -->
        <div
            class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <h2 class="text-sm font-semibold text-themeHeading mb-2">Required columns</h2>
            <ul class="text-sm text-themeBody space-y-1 list-disc list-inside">
                <li><strong>name</strong> — Full name</li>
                <li><strong>email</strong> — Unique, valid email address</li>
                <li><strong>phone</strong> — Optional</li>
                <li><strong>is_active</strong> — Optional. Use 1/0, true/false, yes/no, or active/inactive. Default: active.</li>
                <li><strong>branch</strong> — Optional. Branch code or name. If empty, your branch is used. You can only assign branches you have access to.</li>
            </ul>
            <p class="mt-3 text-sm text-themeMuted">Empty rows are skipped. Each row creates a user with the Staff role and a field agent profile. A random password is generated for each agent.</p>
        </div>

        @if (isset($branches) && $branches->isNotEmpty())
            <div
                class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
                <h2 class="text-sm font-semibold text-themeHeading mb-2">Branches you can use (branch column)</h2>
                <p class="text-sm text-themeMuted mb-3">Use branch <strong>code</strong> or <strong>name</strong> in your file. Leave empty to use your branch.</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($branches as $b)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-lg bg-themeHover text-themeBody text-sm font-medium">{{ $b->code ?? $b->name }} — {{ $b->name }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
