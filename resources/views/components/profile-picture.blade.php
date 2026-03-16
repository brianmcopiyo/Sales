@props([
    'user',
    'size' => 'md', // xs, sm, md, lg, xl
    'class' => '',
])

@php
    $sizeClasses = [
        'xs' => 'w-6 h-6',
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10',
        'lg' => 'w-16 h-16',
        'xl' => 'w-32 h-32',
    ];
    $iconSizes = [
        'xs' => 'w-3 h-3',
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-8 h-8',
        'xl' => 'w-16 h-16',
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
@endphp

@if ($user->profile_picture_url)
    <img src="{{ $user->profile_picture_url }}" 
         alt="{{ $user->name }}" 
         class="{{ $sizeClass }} rounded-full object-cover border-2 border-themeBorder {{ $class }}">
@else
    <div class="{{ $sizeClass }} rounded-full bg-primary/10 flex items-center justify-center border-2 border-themeBorder {{ $class }}">
        <svg class="{{ $iconSize }} text-primary/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
    </div>
@endif
