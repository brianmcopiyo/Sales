@extends('layouts.app')

@section('title', 'Audit Templates')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('outlets.index'), 'label' => 'Back to Outlets'])
        <div class="flex flex-wrap justify-between items-center gap-4">
            <h1 class="text-3xl font-semibold text-primary tracking-tight">Outlet Audit Templates</h1>
            @if (auth()->user()?->hasPermission('outlets.manage'))
                <a href="{{ route('audit-templates.create') }}"
                    class="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">
                    Add template
                </a>
            @endif
            @if (auth()->user()?->hasPermission('distribution.reports'))
                <a href="{{ route('audit-reports.index') }}"
                    class="inline-flex items-center gap-2 bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition shadow-sm">
                    Audit reports
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3">{{ session('success') }}</div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-sm">
            <form method="GET" action="{{ route('audit-templates.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="w-48">
                    <label for="category" class="block text-sm font-medium text-themeBody mb-1">Category</label>
                    <select id="category" name="category"
                        class="w-full px-4 py-2.5 border border-themeBorder rounded-xl text-themeHeading focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">All categories</option>
                        @foreach ($categories as $val => $label)
                            <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm">Filter</button>
                @if (request('category'))
                    <a href="{{ route('audit-templates.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">Clear</a>
                @endif
            </form>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Sections</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-themeMuted uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-themeBorder">
                        @forelse ($templates as $t)
                            <tr class="hover:bg-themeInput/30 transition">
                                <td class="px-4 py-3">
                                    <a href="{{ route('audit-templates.show', $t) }}" class="font-medium text-primary hover:underline">{{ $t->name }}</a>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-themeHover text-themeBody">
                                        {{ $categories[$t->category ?? 'general'] ?? ucfirst($t->category ?? 'General') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-themeBody">{{ $t->sections_count ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $t->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-themeHover text-themeBody' }}">
                                        {{ $t->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('audit-templates.show', $t) }}" class="text-primary hover:underline">View</a>
                                    @if (auth()->user()?->hasPermission('outlets.manage'))
                                        <a href="{{ route('audit-templates.edit', $t) }}" class="ml-3 text-primary hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('audit-templates.destroy', $t) }}" class="inline ml-3" onsubmit="return confirm('Delete this template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-themeMuted">No audit templates yet. Create one to define checklists for outlet visits.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($templates->hasPages())
                <div class="px-4 py-3 border-t border-themeBorder">{{ $templates->links() }}</div>
            @endif
        </div>
    </div>
@endsection
