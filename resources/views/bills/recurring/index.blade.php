@extends('layouts.app')

@section('title', 'Recurring Bills')

@section('content')
    <div class="space-y-6">
        @include('partials.back-button', ['href' => route('bills.index'), 'label' => 'Back to Bills'])
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Recurring Bills</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Templates for rent, utilities, subscriptions – create the next bill with one click</p>
            </div>
            @if($canCreate)
                <a href="{{ route('bills.recurring.create') }}"
                    class="bg-primary text-white px-5 py-2.5 rounded-xl font-medium hover:bg-primary-dark transition shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>New template</span>
                </a>
            @endif
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Frequency</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Next due</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($recurring as $rb)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $rb->vendor?->name ?? '—' }}</div>
                                    @if($rb->description)
                                        <div class="text-xs text-themeMuted truncate max-w-[180px]" title="{{ $rb->description }}">{{ Str::limit($rb->description, 40) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">{{ $rb->branch?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">{{ $rb->category?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-themeHeading">{{ $currencySymbol }} {{ number_format($rb->amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">{{ ucfirst($rb->frequency) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-themeBody">{{ $rb->next_due_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($rb->is_active)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-themeHover text-themeBody">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    @if($canCreate)
                                        <a href="{{ route('bills.recurring.edit', $rb) }}" class="font-medium text-themeBody hover:text-primary">Edit</a>
                                        @if($rb->is_active)
                                            <form action="{{ route('bills.recurring.create-next', $rb) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="font-medium text-primary hover:text-primary-dark">Create next bill</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-themeMuted font-medium">No recurring bill templates. Create one for rent, utilities, or subscriptions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recurring->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $recurring->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
