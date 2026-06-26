@props([
    'title' => '',
    'description' => '',
    'exportUrl' => '',
])
<div class="institutional-card mb-6 flex flex-col gap-4 border-l-4 border-[#988256] p-5 lg:flex-row lg:items-center lg:justify-between">
    <div>
        <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[#988256]">Módulo operativo</p>
        <h3 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
        <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    </div>
    @if($exportUrl)
        <a href="{{ $exportUrl }}" class="btn-export self-start lg:self-center" wire:navigate.hover="false">Exportar CSV</a>
    @endif
</div>
