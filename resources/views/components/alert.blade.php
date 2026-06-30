@props(['type' => 'error'])

@php
    $styles = [
        'success' => 'bg-green-100 dark:bg-green-900/50 border-green-400 dark:border-green-600 text-green-700 dark:text-green-300',
        'error' => 'bg-red-100 dark:bg-red-900/50 border-red-400 dark:border-red-600 text-red-700 dark:text-red-300',
    ];
    $class = $styles[$type] ?? $styles['error'];
@endphp

<div class="{{ $class }} border px-4 py-3 rounded mb-4 text-sm">
    {{ $slot }}
</div>
