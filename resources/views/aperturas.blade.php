@extends('layouts.app', ['pageTitle' => 'Apertura de Tiendas'])

@section('title', 'Aperturas — Dashboard CDT')

@push('head')
<style>
    #aper-table td, #aper-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
    #aper-table th { position: sticky; top: 0; z-index: 1; }
    .page-btn { min-width: 2rem; text-align: center; }
    .page-btn.active { background: #166534; color: white; border-color: #166534; }
    .col-toggle { user-select: none; cursor: pointer; }
    .col-toggle input { accent-color: #166534; }
    .badge { display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }
</style>
@endpush

@section('content')
    @isset($error)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ $error }}</div>
    @endisset

    <div id="app">
        {{-- KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">🏪 Tiendas</p>
                <p class="text-2xl font-bold text-gray-800">{{ $kpis['total'] }}</p>
                @if($filteredCount !== $totalCount)
                    <p class="text-xs text-gray-400">de {{ $totalCount }} totales</p>
                @endif
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">📅 Abiertas este mes</p>
                <p class="text-2xl font-bold text-green-600">{{ $kpis['esteMes'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-amber-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">📅 Abiertas este año</p>
                <p class="text-2xl font-bold text-amber-600">{{ $kpis['esteAnio'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-gray-400">
                <p class="text-xs text-gray-500 uppercase tracking-wide">⚠️ Sin fecha</p>
                <p class="text-2xl font-bold text-gray-600">{{ $kpis['sinFecha'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow p-4 mb-4">
            <form method="GET" action="{{ url('/aperturas') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Almacén</label>
                    <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
                           placeholder="Buscar..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Desde</label>
                    <input type="date" name="desde" value="{{ $filters['desde'] }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Hasta</label>
                    <input type="date" name="hasta" value="{{ $filters['hasta'] }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Filtrar</button>
                    <a href="{{ url('/aperturas') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">Limpiar</a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">⬇ CSV</a>
                </div>
            </form>
        </div>

        {{-- Column toggles --}}
        <div class="bg-white rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
            <span class="text-xs text-gray-500 uppercase font-semibold self-center">Columnas:</span>
            <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer" data-group="General">
                <input type="checkbox" checked disabled class="opacity-50"> 📋 General
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer" data-group="Apertura">
                <input type="checkbox" checked> 📅 Apertura
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
            <table id="aper-table" class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr id="aper-header"></tr>
                </thead>
                <tbody id="aper-body" class="divide-y divide-gray-200"></tbody>
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
            General: ['Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio'],
            Apertura: ['_fecha_apertura', '_antiguedad'],
        };

        var columnLabels = {
            Nombre_Almacen: 'Almacén',
            Localidad: 'Localidad',
            No_Tienda_Actual: '#',
            Municipio: 'Municipio',
            _fecha_apertura: 'Apertura',
            _antiguedad: 'Antigüedad',
        };

        function formatDate(isoStr) {
            if (!isoStr) return '<span class="text-gray-400">—</span>';
            var parts = isoStr.substring(0, 10).split('-');
            return '<span class="font-mono text-gray-700">' + parts[2] + '/' + parts[1] + '/' + parts[0] + '</span>';
        }

        function renderCell(col, store) {
            if (col === 'Nombre_Almacen') return '<strong class="text-gray-900">' + esc(store[col] || '—') + '</strong>';
            if (col === 'Localidad' || col === 'Municipio') return esc(store[col] || '—');
            if (col === 'No_Tienda_Actual') {
                var n = store[col];
                return '<span class="font-mono text-gray-700 text-center block">' + (n || '—') + '</span>';
            }

            if (col === '_fecha_apertura') {
                var f = store._fecha_apertura;
                if (f) return '<div class="text-center font-mono text-gray-700">' + formatDate(f) + '</div>';
                return '<div class="text-center text-gray-400">—</div>';
            }

            if (col === '_antiguedad') {
                var iso = store._fecha_apertura;
                if (!iso) return '<div class="text-center text-gray-400">—</div>';
                var d = new Date(iso);
                var now = new Date();
                var diffMs = now - d;
                var diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                var diffMeses = Math.floor(diffDias / 30);
                var label, color;
                if (diffDias <= 0) { label = 'Hoy'; color = 'bg-green-100 text-green-800'; }
                else if (diffDias < 30) { label = diffDias + ' día' + (diffDias > 1 ? 's' : ''); color = 'bg-green-100 text-green-800'; }
                else if (diffMeses < 12) { label = diffMeses + ' mes' + (diffMeses > 1 ? 'es' : ''); color = diffMeses <= 3 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'; }
                else { var anos = Math.floor(diffMeses / 12); label = anos + ' año' + (anos > 1 ? 's' : ''); color = 'bg-gray-100 text-gray-600'; }
                return '<div class="text-center"><span class="badge ' + color + '">' + label + '</span></div>';
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

            document.getElementById('aper-header').innerHTML = cols.map(function (c) {
                return '<th class="text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">' + (columnLabels[c] || c) + '</th>';
            }).join('');

            document.getElementById('aper-body').innerHTML = pageData.map(function (store) {
                var f = store._fecha_apertura;
                var isRecent = false;
                if (f) {
                    var d = new Date(f);
                    var threeAgo = new Date();
                    threeAgo.setMonth(threeAgo.getMonth() - 3);
                    isRecent = d >= threeAgo;
                }
                var cls = isRecent ? ' bg-green-50 hover:bg-green-100' : ' hover:bg-gray-50';
                return '<tr class="' + cls + '">' +
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
