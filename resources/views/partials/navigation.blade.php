@php
    try {
        if (isset($navigation) && method_exists($navigation, 'getMenuSections')) {
            $menuSections = $navigation->getMenuSections();
            $navService = $navigation;
        } else {
            // Fallback: try to get service from container
            $navService = app(\App\Services\NavigationService::class);
            $menuSections = method_exists($navService, 'getMenuSections') ? $navService->getMenuSections() : [];
        }
    } catch (\Exception $e) {
        $menuSections = [];
        $navService = null;
    }
@endphp

@if (!empty($menuSections) && is_array($menuSections))
    @foreach ($menuSections as $section)
        @if (isset($section['items']) && is_array($section['items']) && !empty($section['items']))
            {{-- Section Title --}}
            @if (!empty($section['title']))
                <div
                    class="px-4 py-2 mt-5 mb-0.5 text-[11px] font-semibold text-themeMuted uppercase tracking-widest first:mt-0">
                    {{ $section['title'] }}
                </div>
            @endif

            {{-- Section Items (already filtered by permission in NavigationService) --}}
            @foreach ($section['items'] as $item)
                @if (isset($item['route']) && isset($item['title']))
                    @php
                        $activeClass =
                            isset($navService) && isset($item['active_pattern'])
                                ? $navService->getActiveClass($item['active_pattern'])
                                : '';
                    @endphp
                    <a href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 {{ str_contains($activeClass ?? '', 'bg-') ? 'bg-primary/10 text-primary' : 'text-themeBody hover:bg-themeHover hover:text-primary' }}">
                        <svg class="w-5 h-5 shrink-0 {{ str_contains($activeClass ?? '', 'bg-') ? 'text-primary' : 'text-themeMuted group-hover:text-primary' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $item['icon'] ?? '' }}"></path>
                        </svg>
                        {{ $item['title'] }}
                    </a>
                @endif
            @endforeach
        @endif
    @endforeach
@else
    {{-- Fallback: Try old format (flat menu) --}}
    @php
        try {
            if (isset($navigation) && method_exists($navigation, 'getMenuItems')) {
                $menuItems = $navigation->getMenuItems();
            } else {
                $navService = app(\App\Services\NavigationService::class);
                $menuItems = $navService->getMenuItems();
            }
        } catch (\Exception $e) {
            $menuItems = [];
            $navService = null;
        }
    @endphp

    @if (!empty($menuItems) && is_array($menuItems))
        @foreach ($menuItems as $item)
            @if (isset($item['route']) && isset($item['title']))
                @php $activeClass = isset($navService) && isset($item['active_pattern']) ? $navService->getActiveClass($item['active_pattern']) : ''; @endphp
                <a href="{{ route($item['route']) }}"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 {{ str_contains($activeClass ?? '', 'bg-') ? 'bg-primary/10 text-primary' : 'text-themeBody hover:bg-themeHover hover:text-primary' }}">
                    <svg class="w-5 h-5 shrink-0 {{ str_contains($activeClass ?? '', 'bg-') ? 'text-primary' : 'text-themeMuted' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="{{ $item['icon'] ?? '' }}"></path>
                    </svg>
                    {{ $item['title'] }}
                </a>
            @endif
        @endforeach
    @else
        {{-- Final fallback navigation if service fails --}}
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-themeBody hover:bg-themeHover hover:text-primary rounded-xl transition-all duration-200">
            <svg class="w-5 h-5 text-themeMuted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Dashboard
        </a>
    @endif
@endif
