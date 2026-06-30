@props([
    'title' => '',
    'description' => '',
    'wireTarget' => '',
    'columns' => [],
    'stores' => [],
    'filteredCount' => 0,
    'totalCount' => 0,
    'page' => 1,
    'totalPages' => 1,
    'from' => 0,
    'to' => 0,
    'alignCenter' => [],
    'alignRight' => [],
    'tableStyle' => '',
    'tableId' => '',
    'exportRoute' => '',
    'exportParams' => [],
    'columnLabelFn' => null,
    'sortArrowFn' => null,
    'sortableFn' => null,
    'noKpis' => false,
    'noFilters' => false,
    'noColumnToggles' => false,
])

<div class="page-shell" wire:loading.class="opacity-70" wire:target="{{ $wireTarget }}">
    <x-module-header :title="$title" :description="$description" />

    @unless($noKpis)
        {{ $kpis }}
    @endunless

    @unless($noFilters)
        {{ $filters }}
    @endunless

    @if($exportRoute)
        <x-export-button :route="$exportRoute" :params="$exportParams" class="mb-6" />
    @endif

    @unless($noColumnToggles)
        {{ $columnToggles }}
    @endunless

    <x-table-summary :from="$from" :to="$to" :filteredCount="$filteredCount" :totalCount="$totalCount" />

    <div x-data="{ page: @entangle('page') }" x-init="$watch('page', () => $nextTick(() => $el.scrollTop = 0))" class="max-h-[65vh] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800">
        <table @if($tableId) id="{{ $tableId }}" @endif class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200" @if($tableStyle) style="{{ $tableStyle }}" @endif>
            <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                <tr>
                    @foreach($columns as $column)
                        @php
                            $sortable = $sortableFn ? $sortableFn($column) : ! in_array($column, ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'], true);
                            $align = in_array($column, $alignRight, true) ? 'text-right' : (in_array($column, $alignCenter, true) ? 'text-center' : 'text-left');
                        @endphp
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800 {{ $align }} {{ $sortable ? 'cursor-pointer select-none hover:text-gray-800 dark:hover:text-gray-100' : '' }}" @if($sortable) wire:click="sortBy('{{ $column }}')" title="Ordenar columna" @endif>
                            {{ $columnLabelFn ? $columnLabelFn($column) : $column }}
                            @if($sortable && $sortArrowFn)
                                <span class="ml-1 text-[10px]">{{ $sortArrowFn($column) }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                {{ $rows }}
            </tbody>
        </table>
    </div>

    <x-table-pagination :page="$page" :totalPages="$totalPages" />
</div>
