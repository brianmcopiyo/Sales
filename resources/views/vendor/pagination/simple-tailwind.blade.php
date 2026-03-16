@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-4 sm:flex-nowrap" aria-label="Pagination">
        <p class="text-sm font-medium text-themeMuted order-2 sm:order-1">
            Showing <span class="font-semibold text-themeHeading">{{ $paginator->firstItem() ?? 0 }}</span>
            to <span class="font-semibold text-themeHeading">{{ $paginator->lastItem() ?? 0 }}</span>
            of <span class="font-semibold text-themeHeading">{{ $paginator->total() }}</span> results
        </p>
        <div class="flex items-center gap-2 order-1 sm:order-2">
            @if ($paginator->onFirstPage())
                <span
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeMuted bg-themeInput border border-themeBorder rounded-xl cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-primary bg-themeCard border border-themeBorder rounded-xl hover:bg-primary/5 hover:border-[#006F78]/30 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Previous
                </a>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-primary border border-[#006F78] rounded-xl hover:bg-primary-dark transition shadow-sm">
                    Next
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-themeMuted bg-themeInput border border-themeBorder rounded-xl cursor-not-allowed">
                    Next
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
