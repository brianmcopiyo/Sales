@extends('layouts.app')

@section('title', 'Device Request')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Device Request</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">IMEI {{ $deviceRequest->device->imei ?? '—' }}</p>
            </div>
            <a href="{{ route('device-requests.index') }}" class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Device Requests
            </a>
        </div>

        <div class="bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-sm max-w-2xl">
            <dl class="grid grid-cols-1 gap-4">
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Device</dt>
                    <dd class="mt-1 font-medium text-themeHeading">{{ $deviceRequest->device->product->name ?? '—' }} · IMEI {{ $deviceRequest->device->imei ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Requesting branch</dt>
                    <dd class="mt-1 text-themeBody">{{ $deviceRequest->requestingBranch->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Requested by</dt>
                    <dd class="mt-1 text-themeBody">{{ $deviceRequest->requestedByUser->name ?? '—' }} · {{ $deviceRequest->created_at->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Host branch (device location)</dt>
                    <dd class="mt-1 text-themeBody">{{ $deviceRequest->device->branch->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-themeMuted">Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $deviceRequest->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($deviceRequest->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                            {{ ucfirst($deviceRequest->status) }}
                        </span>
                    </dd>
                </div>
                @if ($deviceRequest->notes)
                    <div>
                        <dt class="text-sm font-medium text-themeMuted">Notes</dt>
                        <dd class="mt-1 text-themeBody">{{ $deviceRequest->notes }}</dd>
                    </div>
                @endif
                @if ($deviceRequest->isRejected() && $deviceRequest->rejection_reason)
                    <div>
                        <dt class="text-sm font-medium text-themeMuted">Rejection reason</dt>
                        <dd class="mt-1 text-red-700">{{ $deviceRequest->rejection_reason }}</dd>
                    </div>
                @endif
            </dl>

            @if ($isHost && $deviceRequest->isPending())
                <div class="mt-6 pt-6 border-t border-themeBorder flex flex-wrap gap-2">
                    <form action="{{ route('device-requests.approve', $deviceRequest) }}" method="post" class="inline">
                        @csrf
                        <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-emerald-700 transition">Approve</button>
                    </form>
                    <button type="button" onclick="document.getElementById('reject-modal').classList.remove('hidden')" class="bg-red-100 text-red-700 px-4 py-2 rounded-xl font-medium hover:bg-red-200 transition">Reject</button>
                </div>
                <div id="reject-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" onclick="if (event.target === this) this.classList.add('hidden')">
                    <div class="bg-themeCard rounded-2xl p-6 max-w-md w-full shadow-xl" onclick="event.stopPropagation()">
                        <h3 class="text-lg font-semibold text-primary mb-2">Reject request</h3>
                        <form action="{{ route('device-requests.reject', $deviceRequest) }}" method="post">
                            @csrf
                            <label for="rejection_reason" class="block text-sm font-medium text-themeBody mb-2">Reason (optional)</label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" class="w-full px-3 py-2 border border-themeBorder rounded-xl text-sm mb-4"></textarea>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl font-medium bg-themeHover text-themeBody">Cancel</button>
                                <button type="submit" class="px-4 py-2 rounded-xl font-medium bg-red-600 text-white hover:bg-red-700">Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
