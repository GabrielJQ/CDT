@extends('layouts.app', ['pageTitle' => 'Conectividad'])

@section('title', 'Conectividad — Dashboard CDT')

@push('head')
<style>
    #conn-table td, #conn-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
    #conn-table th { position: sticky; top: 0; z-index: 1; }
    .page-btn { min-width: 2rem; text-align: center; }
    .page-btn.active { background: #166534; color: white; border-color: #166534; }
    .col-toggle { user-select: none; cursor: pointer; }
    .col-toggle input { accent-color: #166534; }
</style>
@endpush

@section('content')
    @isset($error)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ $error }}</div>
    @endisset

    <div id="app">
        {{-- KPI Cards --}}
        @if(!empty($kpis))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                @foreach(['TELEFONIA', 'Señal de celular', 'INTERNET'] as $key)
                    @php $k = $kpis[$key] ?? null; @endphp
                    @if($k)
                        @php $barYes = $kpis['_total'] > 0 ? round($k['yes'] / $kpis['_total'] * 100) : 0; @endphp
                        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-green-500">
                            <p class="text-sm text-gray-500 uppercase tracking-wide">{{ $k['icon'] }} {{ $k['label'] }}</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $k['yes'] }} <span class="text-sm font-normal text-gray-400">/ {{ $kpis['_total'] }} ({{ $barYes }}%)</span></p>
                            <div class="mt-2 flex gap-4 text-xs">
                                <span class="text-green-600 font-semibold">Sí: {{ $k['yes'] }}</span>
                                <span class="text-red-500 font-semibold">No: {{ $k['no'] }}</span>
                                @if($k['undef'] > 0)
                                    <span class="text-gray-400 font-semibold">—: {{ $k['undef'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Compañía distribution --}}
            @if(!empty($kpis['_compania']))
                <div class="bg-white rounded-xl shadow p-5 mb-6">
                    <p class="text-sm text-gray-500 uppercase tracking-wide mb-3">📡 Distribución por Compañía (tiendas con señal)</p>
                    <div class="flex flex-wrap gap-6">
                        @foreach($kpis['_compania'] as $comp => $info)
                            <div class="flex-1 min-w-[120px]">
                                <div class="text-lg font-bold text-gray-800">{{ $comp }}</div>
                                <div class="text-2xl font-bold text-blue-600">{{ $info['pct'] }}%</div>
                                <div class="text-xs text-gray-400">{{ $info['count'] }} tiendas</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow p-4 mb-4">
            <form method="GET" action="{{ url('/conectividad') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Almacén</label>
                    <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
                           placeholder="Buscar..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="min-w-[130px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">📞 Teléfono</label>
                    <select name="telefono" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">Todos</option>
                        <option value="si" {{ $filters['telefono'] === 'si' ? 'selected' : '' }}>Sí</option>
                        <option value="no" {{ $filters['telefono'] === 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="min-w-[130px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">📱 Señal Celular</label>
                    <select name="senial" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">Todos</option>
                        <option value="si" {{ $filters['senial'] === 'si' ? 'selected' : '' }}>Sí</option>
                        <option value="no" {{ $filters['senial'] === 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="min-w-[130px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Compañía</label>
                    <select name="compania" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">Todas</option>
                        @foreach($filterOptions['companias'] ?? [] as $comp)
                            <option value="{{ $comp }}" {{ $filters['compania'] === $comp ? 'selected' : '' }}>{{ $comp }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[130px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">🌐 Internet</label>
                    <select name="internet" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">Todos</option>
                        <option value="si" {{ $filters['internet'] === 'si' ? 'selected' : '' }}>Sí</option>
                        <option value="no" {{ $filters['internet'] === 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Filtrar</button>
                    <a href="{{ url('/conectividad') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">Limpiar</a>
                </div>
            </form>
        </div>

        {{-- Column toggles --}}
        <div class="bg-white rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
            <span class="text-xs text-gray-500 uppercase font-semibold self-center">Columnas:</span>
            <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer" data-group="General">
                <input type="checkbox" checked disabled class="opacity-50"> 📋 General
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer" data-group="Conectividad">
                <input type="checkbox" checked> 📡 Conectividad
            </label>
        </div>

        {{-- Count --}}
        <div class="text-sm text-gray-500 mb-2">
            Mostrando <strong id="info-from">0</strong>–<strong id="info-to">0</strong> de <strong id="info-total">{{ count($stores) }}</strong> tiendas
            @if($filteredCount !== $totalCount)
                <span class="text-gray-400">(filtradas de {{ $totalCount }})</span>
            @endif
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table id="conn-table" class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr id="conn-header"></tr>
                </thead>
                <tbody id="conn-body" class="divide-y divide-gray-200"></tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between mt-4">
            <button id="page-prev" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition">
                ← Anterior
            </button>
            <div id="page-numbers" class="flex gap-1"></div>
            <button id="page-next" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition">
                Siguiente →
            </button>
        </div>
    </div>
@endsection

@push('footer')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var PAGE_SIZE = 25;
        var allStores = @json($stores);
        var currentPage = 1;

        var columnGroups = {
            General: ['Nombre_Almacen', 'No_Tienda_Actual', 'Municipio'],
            Conectividad: ['TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET'],
        };

        var columnLabels = {
            Nombre_Almacen: 'Almacén',
            No_Tienda_Actual: 'Tienda #',
            Municipio: 'Municipio',
            TELEFONIA: '📞 Teléfono',
            'Señal de celular': '📱 Señal Celular',
            Compañía: 'Compañía',
            INTERNET: '🌐 Internet',
        };

        function yesNoBadge(val) {
            var v = (val || '').trim().toUpperCase();
            if (v === 'S') return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Sí</span>';
            if (v === 'N') return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">No</span>';
            return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">—</span>';
        }

        function renderCell(col, store) {
            if (col === 'Nombre_Almacen') return '<strong class="text-gray-900">' + esc(store[col] || '—') + '</strong>';
            if (col === 'No_Tienda_Actual') {
                var n = store[col];
                return '<span class="font-mono text-gray-700">' + (n ? Number(n).toLocaleString() : '—') + '</span>';
            }
            if (col === 'Municipio') return esc(store[col] || '—');
            if (col === 'TELEFONIA' || col === 'Señal de celular' || col === 'INTERNET') return '<div class="text-center">' + yesNoBadge(store[col]) + '</div>';
            if (col === 'Compañía') {
                var c = (store[col] || '').trim();
                return '<span class="text-gray-700">' + (c || '—') + '</span>';
            }
            return esc(store[col] || '');
        }

        function esc(str) {
            if (str == null) return '';
            var d = document.createElement('div');
            d.textContent = String(str);
            return d.innerHTML;
        }

        function getActiveCols() {
            var cols = [];
            document.querySelectorAll('[data-group] input').forEach(function (cb) {
                var group = cb.closest('[data-group]').dataset.group;
                if (cb.checked && columnGroups[group]) cols = cols.concat(columnGroups[group]);
            });
            return cols;
        }

        function renderTable() {
            var cols = getActiveCols();
            var total = allStores.length;
            var totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
            if (currentPage > totalPages) currentPage = totalPages;

            var start = (currentPage - 1) * PAGE_SIZE;
            var end = Math.min(start + PAGE_SIZE, total);
            var pageData = allStores.slice(start, end);

            document.getElementById('conn-header').innerHTML = cols.map(function (c) {
                return '<th class="text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">' + (columnLabels[c] || c) + '</th>';
            }).join('');

            document.getElementById('conn-body').innerHTML = pageData.map(function (store) {
                return '<tr class="hover:bg-gray-50">' +
                    cols.map(function (c) {
                        return '<td class="text-gray-700">' + renderCell(c, store) + '</td>';
                    }).join('') +
                    '</tr>';
            }).join('');

            document.getElementById('info-from').textContent = total > 0 ? start + 1 : 0;
            document.getElementById('info-to').textContent = end;
            document.getElementById('info-total').textContent = total;

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            var container = document.getElementById('page-numbers');
            container.innerHTML = '';
            if (totalPages <= 1) return;

            var maxVisible = 7;
            var pages = [];
            var startP, endP;

            if (totalPages <= maxVisible) {
                startP = 1; endP = totalPages;
            } else {
                var half = Math.floor(maxVisible / 2);
                startP = currentPage - half;
                endP = currentPage + half;
                if (startP < 1) { startP = 1; endP = maxVisible; }
                if (endP > totalPages) { endP = totalPages; startP = totalPages - maxVisible + 1; }
            }

            if (startP > 1) {
                pages.push(1);
                if (startP > 2) pages.push('…');
            }
            for (var i = startP; i <= endP; i++) pages.push(i);
            if (endP < totalPages) {
                if (endP < totalPages - 1) pages.push('…');
                pages.push(totalPages);
            }

            pages.forEach(function (p) {
                if (p === '…') {
                    container.innerHTML += '<span class="text-gray-400 px-1 self-end">…</span>';
                } else {
                    container.innerHTML += '<button class="page-btn px-2.5 py-1 text-sm border border-gray-300 rounded-lg transition hover:bg-gray-100 ' + (p === currentPage ? 'active' : 'bg-white text-gray-700') + '" data-page="' + p + '">' + p + '</button>';
                }
            });

            container.querySelectorAll('[data-page]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    currentPage = parseInt(this.dataset.page);
                    renderTable();
                });
            });

            document.getElementById('page-prev').disabled = currentPage <= 1;
            document.getElementById('page-next').disabled = currentPage >= totalPages;
        }

        document.getElementById('page-prev').addEventListener('click', function () {
            if (currentPage > 1) { currentPage--; renderTable(); }
        });
        document.getElementById('page-next').addEventListener('click', function () {
            var total = Math.max(1, Math.ceil(allStores.length / PAGE_SIZE));
            if (currentPage < total) { currentPage++; renderTable(); }
        });

        document.querySelectorAll('[data-group] input').forEach(function (cb) {
            cb.addEventListener('change', function () {
                currentPage = 1;
                renderTable();
            });
        });

        renderTable();
    });
</script>
@endpush
