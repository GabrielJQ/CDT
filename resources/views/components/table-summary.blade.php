@props([
    'from' => 0,
    'to' => 0,
    'filteredCount' => 0,
    'totalCount' => 0,
])
<div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
    Mostrando <strong>{{ number_format($from) }}</strong>–<strong>{{ number_format($to) }}</strong> de <strong>{{ number_format($filteredCount) }}</strong> tiendas
    @if($filteredCount !== $totalCount)
        <span class="text-gray-400 dark:text-gray-500">(filtradas de {{ number_format($totalCount) }})</span>
    @endif
    <span wire:loading class="ml-2 text-[#988256] font-semibold">Actualizando...</span>
</div>
