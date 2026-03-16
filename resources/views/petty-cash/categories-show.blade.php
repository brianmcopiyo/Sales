@extends('layouts.app')

@section('title', $pettyCashCategory->name . ' – Petty Cash')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">{{ $pettyCashCategory->name }}</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Request history for this category</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('petty-cash.categories.edit', $pettyCashCategory) }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                    Edit category
                </a>
                <a href="{{ route('petty-cash.categories.index') }}"
                    class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Back to Categories</span>
                </a>
            </div>
        </div>

        @if($pettyCashCategory->description)
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-4 shadow-sm">
                <p class="text-sm font-medium text-themeBody">{{ $pettyCashCategory->description }}</p>
            </div>
        @endif

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)]">
            <h2 class="text-lg font-semibold text-primary tracking-tight p-6 pb-2">Request history</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-themeBorder">
                    <thead class="bg-themeInput/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Requested by</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-themeMuted uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-themeMuted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-themeCard divide-y divide-themeBorder">
                        @forelse($requests as $req)
                            <tr class="hover:bg-themeInput/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $req->fund->branch->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-amber-600">{{ $req->fund->currency }} {{ number_format($req->amount, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $req->requestedByUser->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($req->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-800">Pending</span>
                                    @elseif($req->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-sky-100 text-sky-800">Approved</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-800">Disbursed</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-themeHeading">{{ $req->created_at->format('M d, Y') }}</div>
                                    <div class="text-sm font-medium text-themeMuted">{{ $req->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('petty-cash.show-request', $req) }}"
                                        class="text-sm font-medium text-primary hover:text-primary-dark">View request</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-themeMuted font-medium">No requests in this category yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
                <div class="px-6 py-4 border-t border-themeBorder bg-themeInput/50">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
