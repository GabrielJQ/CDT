@props([
    'page' => 1,
    'totalPages' => 1,
])
<div class="flex items-center justify-between mt-4">
    <button type="button" wire:click="previousTablePage({{ $totalPages }})" @disabled($page <= 1) wire:loading.attr="disabled" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 disabled:opacity-30 disabled:cursor-not-allowed transition">
        ← Anterior
    </button>
    <div class="flex gap-1">
        @php
            $startPage = max(1, $page - 3);
            $endPage = min($totalPages, $page + 3);
        @endphp
        @if($startPage > 1)
            <button type="button" wire:click="goToTablePage(1, {{ $totalPages }})" wire:loading.attr="disabled" class="page-btn px-2.5 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition hover:bg-gray-100 dark:hover:bg-gray-700/30 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">1</button>
            @if($startPage > 2)
                <span class="text-gray-400 dark:text-gray-500 px-1 self-end">...</span>
            @endif
        @endif
        @for($tablePage = $startPage; $tablePage <= $endPage; $tablePage++)
            <button type="button" wire:click="goToTablePage({{ $tablePage }}, {{ $totalPages }})" wire:loading.attr="disabled" class="page-btn px-2.5 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition hover:bg-gray-100 dark:hover:bg-gray-700/30 {{ $tablePage === $page ? 'active' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">{{ $tablePage }}</button>
        @endfor
        @if($endPage < $totalPages)
            @if($endPage < $totalPages - 1)
                <span class="text-gray-400 dark:text-gray-500 px-1 self-end">...</span>
            @endif
            <button type="button" wire:click="goToTablePage({{ $totalPages }}, {{ $totalPages }})" wire:loading.attr="disabled" class="page-btn px-2.5 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition hover:bg-gray-100 dark:hover:bg-gray-700/30 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">{{ $totalPages }}</button>
        @endif
    </div>
    <button type="button" wire:click="nextTablePage({{ $totalPages }})" @disabled($page >= $totalPages) wire:loading.attr="disabled" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 disabled:opacity-30 disabled:cursor-not-allowed transition">
        Siguiente →
    </button>
</div>
