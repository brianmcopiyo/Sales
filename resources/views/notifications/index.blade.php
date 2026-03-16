@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-primary tracking-tight">Notifications</h1>
                <p class="text-sm font-medium text-themeMuted mt-1">{{ $unreadCount }} unread</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <nav class="flex rounded-xl border border-themeBorder bg-themeInput/80 p-1" aria-label="Filter">
                    <a href="{{ route('notifications.index', ['filter' => 'all']) }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition {{ ($filter ?? 'all') === 'all' ? 'bg-themeCard text-themeHeading shadow-sm' : 'text-themeBody hover:text-themeHeading' }}">All</a>
                    <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition {{ ($filter ?? '') === 'unread' ? 'bg-themeCard text-themeHeading shadow-sm' : 'text-themeBody hover:text-themeHeading' }}">Unread</a>
                    <a href="{{ route('notifications.index', ['filter' => 'read']) }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition {{ ($filter ?? '') === 'read' ? 'bg-themeCard text-themeHeading shadow-sm' : 'text-themeBody hover:text-themeHeading' }}">Read</a>
                </nav>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="inline">
                        @csrf
                        <input type="hidden" name="redirect_filter" value="{{ $filter ?? 'all' }}">
                        <button type="submit"
                            class="bg-themeHover text-themeBody px-5 py-2.5 rounded-xl font-medium hover:bg-themeBorder transition">
                            Mark all as read
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div
            class="bg-themeCard rounded-2xl border border-themeBorder shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] overflow-hidden">
            @if ($notifications->isNotEmpty())
                <ul class="divide-y divide-themeBorder">
                    @foreach ($notifications as $notification)
                        @php $data = $notification->data ?? []; @endphp
                        <li class="{{ $notification->read_at ? '' : 'bg-primary/5' }}">
                            <a href="{{ route('notifications.mark-read', $notification->id) }}"
                                class="block px-6 py-4 hover:bg-themeInput/80 transition">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-medium text-themeHeading">
                                                {{ $data['title'] ?? 'Notification' }}</span>
                                            @if (!$notification->read_at)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary">Unread</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-themeBody mt-0.5">{{ $data['message'] ?? '' }}</div>
                                        <div class="text-xs text-themeMuted mt-1">
                                            {{ $notification->created_at->format('M d, Y H:i') }}</div>
                                    </div>
                                    <span class="shrink-0 text-primary text-sm font-medium">View</span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="px-6 py-3 border-t border-themeBorder">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center text-themeMuted">
                    <svg class="mx-auto h-12 w-12 text-themeMuted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if (($filter ?? 'all') === 'unread')
                        <p class="mt-2 text-sm font-medium text-themeHeading">No unread notifications</p>
                        <p class="mt-1 text-sm text-themeMuted">You’re all caught up.</p>
                    @elseif (($filter ?? '') === 'read')
                        <p class="mt-2 text-sm font-medium text-themeHeading">No read notifications</p>
                        <p class="mt-1 text-sm text-themeMuted">Notifications you open will appear here.</p>
                    @else
                        <p class="mt-2 text-sm font-medium text-themeHeading">No notifications</p>
                        <p class="mt-1 text-sm text-themeMuted">You’ll see stock transfer and other activity here.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

