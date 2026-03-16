@extends('layouts.app')

@section('title', 'Device Requests')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Device Requests</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">Request a device from another branch when you need it for a sale</p>
            </div>
            <a href="{{ route('sales.index') }}"
                class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to Sales</span>
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm">
                <div class="text-xs font-medium text-themeMuted mb-1">Outgoing pending</div>
                <div class="text-2xl font-semibold text-amber-600">{{ $stats['outgoing_pending'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm">
                <div class="text-xs font-medium text-themeMuted mb-1">Outgoing approved</div>
                <div class="text-2xl font-semibold text-emerald-600">{{ $stats['outgoing_approved'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm">
                <div class="text-xs font-medium text-themeMuted mb-1">Outgoing rejected</div>
                <div class="text-2xl font-semibold text-red-600">{{ $stats['outgoing_rejected'] }}</div>
            </div>
            <div class="bg-themeCard rounded-2xl border border-themeBorder p-5 shadow-sm">
                <div class="text-xs font-medium text-themeMuted mb-1">Incoming pending</div>
                <div class="text-2xl font-semibold text-sky-600">{{ $stats['incoming_pending'] }}</div>
            </div>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder overflow-hidden shadow-sm">
            <div class="border-b border-themeBorder bg-themeInput/80 px-6">
                <nav class="flex gap-6" aria-label="Tabs">
                    <a href="{{ route('device-requests.index', ['tab' => 'outgoing']) }}"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ ($tab ?? 'outgoing') === 'outgoing' ? 'border-primary text-primary' : 'border-transparent text-themeMuted hover:text-themeBody' }}">
                        My requests
                        @if ($stats['outgoing_pending'] > 0)
                            <span class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $stats['outgoing_pending'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('device-requests.index', ['tab' => 'incoming']) }}"
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ ($tab ?? '') === 'incoming' ? 'border-primary text-primary' : 'border-transparent text-themeMuted hover:text-themeBody' }}">
                        Incoming (approve or reject)
                        @if ($stats['incoming_pending'] > 0)
                            <span class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">{{ $stats['incoming_pending'] }}</span>
                        @endif
                    </a>
                </nav>
            </div>
            <div class="p-6">
                @if ($tab === 'incoming')
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Requests for devices in your branch</h2>
                    @if ($incoming->count() > 0)
                        <div class="space-y-3">
                            @foreach ($incoming as $req)
                                <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50 flex flex-wrap items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="font-medium text-themeHeading">{{ $req->device->product->name ?? 'Device' }} · IMEI {{ $req->device->imei }}</div>
                                        <div class="text-sm text-themeBody mt-1">
                                            Requested by <span class="font-medium">{{ $req->requestingBranch->name }}</span>
                                            ({{ $req->requestedByUser->name ?? '—' }}) · {{ $req->created_at->format('M d, Y H:i') }}
                                        </div>
                                        @if ($req->notes)
                                            <p class="text-sm text-themeMuted mt-1">{{ Str::limit($req->notes, 120) }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('device-requests.approve', $req) }}" method="post" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700 transition text-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Approve
                                            </button>
                                        </form>
                                        <button type="button" onclick="document.getElementById('reject-form-{{ $req->id }}').classList.toggle('hidden')"
                                            class="inline-flex items-center gap-2 bg-red-100 text-red-700 px-4 py-2 rounded-xl font-medium hover:bg-red-200 transition text-sm">
                                            Reject
                                        </button>
                                        <div id="reject-form-{{ $req->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onclick="if (event.target === this) this.classList.add('hidden')">
                                            <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl" onclick="event.stopPropagation()">
                                                <h3 class="text-lg font-semibold text-primary mb-2">Reject device request</h3>
                                                <form action="{{ route('device-requests.reject', $req) }}" method="post">
                                                    @csrf
                                                    <label for="rejection_reason_{{ $req->id }}" class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                                                    <textarea id="rejection_reason_{{ $req->id }}" name="rejection_reason" rows="2" class="w-full px-3 py-2 border border-themeBorder rounded-xl text-sm mb-4" placeholder="Optional reason for rejection"></textarea>
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" onclick="document.getElementById('reject-form-{{ $req->id }}').classList.add('hidden')" class="px-4 py-2 rounded-xl font-medium bg-themeHover text-themeBody">Cancel</button>
                                                        <button type="submit" class="px-4 py-2 rounded-xl font-medium bg-red-600 text-white hover:bg-red-700">Reject request</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">{{ $incoming->links() }}</div>
                    @else
                        <p class="text-themeMuted">No pending requests for devices in your branch.</p>
                    @endif
                @else
                    <h2 class="text-lg font-semibold text-primary tracking-tight mb-4">Your device requests</h2>
                    @if ($outgoing->count() > 0)
                        <div class="space-y-3">
                            @foreach ($outgoing as $req)
                                <div class="border border-themeBorder rounded-xl p-4 bg-themeInput/50 flex flex-wrap items-center justify-between gap-4">
                                    <a href="{{ route('device-requests.show', $req) }}" class="min-w-0 flex-1 block hover:opacity-90">
                                        <div class="font-medium text-themeHeading">{{ $req->device->product->name ?? 'Device' }} · IMEI {{ $req->device->imei }}</div>
                                        <div class="text-sm text-themeBody mt-1">
                                            From <span class="font-medium">{{ $req->device->branch->name ?? 'branch' }}</span>
                                            · {{ $req->created_at->format('M d, Y H:i') }}
                                            · <span class="font-medium {{ $req->status === 'pending' ? 'text-amber-600' : ($req->status === 'approved' ? 'text-emerald-600' : 'text-red-600') }}">{{ ucfirst($req->status) }}</span>
                                        </div>
                                        @if ($req->notes)
                                            <p class="text-sm text-themeMuted mt-1">{{ Str::limit($req->notes, 120) }}</p>
                                        @endif
                                    </a>
                                    <a href="{{ route('device-requests.show', $req) }}" class="text-sm font-medium text-primary hover:underline">View</a>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">{{ $outgoing->links() }}</div>
                    @else
                        <p class="text-themeMuted">You have not requested any devices. When you try to sell a device from another branch, you can request it from the Create Sale page.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
