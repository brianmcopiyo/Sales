@props(['href', 'label' => 'Back'])
<a href="{{ $href }}"
    class="inline-flex items-center gap-2 text-sm font-medium text-themeBody hover:text-primary transition mb-4 group">
    <svg class="w-4 h-4 shrink-0 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor"
        viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
    </svg>
    <span>{{ $label }}</span>
</a>
