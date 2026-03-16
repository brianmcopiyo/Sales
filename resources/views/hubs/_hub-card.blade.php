@props(['href', 'title', 'description', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'])
<a href="{{ $href }}"
    class="block bg-themeCard rounded-2xl border border-themeBorder p-6 shadow-[0_2px_15px_-3px_rgba(0,111,120,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:border-primary/30 hover:shadow-[0_4px_20px_-3px_rgba(0,111,120,0.15),0_10px_20px_-2px_rgba(0,0,0,0.04)] transition-all duration-200 group">
    <div class="flex items-start gap-4">
        <div
            class="shrink-0 w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary/20 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <h3 class="text-lg font-semibold text-primary tracking-tight group-hover:text-primary transition-colors">
                {{ $title }}</h3>
            @if (isset($description))
                <p class="mt-1 text-sm font-medium text-themeMuted">{{ $description }}</p>
            @endif
        </div>
        <svg class="w-5 h-5 text-themeMuted shrink-0 group-hover:text-primary group-hover:translate-x-0.5 transition-all"
            fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </div>
</a>
