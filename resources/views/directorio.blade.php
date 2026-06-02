@extends('layouts.app', ['pageTitle' => 'Directorio de Tiendas'])

@section('title', 'Directorio — Dashboard CDT')

@push('head')
<style>
    #dir-table td, #dir-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
    #dir-table th { position: sticky; top: 0; z-index: 1; }
    .cell-empty { background: #fef2f2; color: #9ca3af; }
    .cell-empty::before { content: '—'; }
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
        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">📋 Total tiendas</p>
                <p class="text-2xl font-bold text-gray-800" id="stat-total">{{ $totalCount }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">📄 Página</p>
                <p class="text-2xl font-bold text-gray-800"><span id="stat-page">1</span> <span class="text-sm font-normal text-gray-400">de <span id="stat-pages">1</span></span></p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-red-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">🔴 Incompletos</p>
                <p class="text-2xl font-bold text-red-600" id="stat-incompletos">{{ $globalStats['incompletos'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-orange-400">
                <p class="text-xs text-gray-500 uppercase tracking-wide">💰 Sin capital</p>
                <p class="text-2xl font-bold text-orange-600" id="stat-sinCapital">{{ $globalStats['sinCapital'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow p-4 mb-4">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Buscar almacén o tienda</label>
                    <input type="text" id="filter-search" placeholder="Escribe para filtrar..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                </div>
                <div class="flex gap-3 items-end pb-1">
                    <label class="col-toggle flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" id="filter-incompletos"> 🔴 Solo incompletos
                    </label>
                    <label class="col-toggle flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" id="filter-sinCapital"> 💰 Sin capital
                    </label>
                    <button id="filter-clear" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-sm font-semibold transition">
                        Limpiar
                    </button>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition inline-block">⬇ CSV</a>
                </div>
            </div>
        </div>

        {{-- Column toggles --}}
        <div class="bg-white rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
            <span class="text-xs text-gray-500 uppercase font-semibold self-center">Columnas:</span>
            <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer" data-group="ID">
                <input type="checkbox" checked disabled class="opacity-50"> 🆔 ID
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer" data-group="Contacto">
                <input type="checkbox"> 📞 Contacto
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer" data-group="Ventas">
                <input type="checkbox"> 📊 Ventas
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer" data-group="Capital">
                <input type="checkbox"> 💰 Capital
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer" data-group="Comite">
                <input type="checkbox"> 🏛️ Comité
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer" data-group="Auditoria">
                <input type="checkbox"> 🔍 Auditoría
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer" data-group="Ubicacion">
                <input type="checkbox"> 🌐 Ubicación
            </label>
        </div>

        {{-- Count --}}
        <div class="text-sm text-gray-500 mb-2">
            Mostrando <strong id="info-from">0</strong>–<strong id="info-to">0</strong> de <strong id="info-total">{{ $totalCount }}</strong> tiendas
        </div>

        {{-- Table wrapper --}}
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table id="dir-table" class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr id="dir-header"></tr>
                </thead>
                <tbody id="dir-body" class="divide-y divide-gray-200"></tbody>
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
        var filtered = [];
        var currentPage = 1;

        var columnGroups = {
            ID: ['Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura'],
            Contacto: ['TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET'],
            Ventas: ['Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu', 'Bon_Mes'],
            Capital: ['Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto'],
            Comite: ['Fec_CRA', 'Vigencia', 'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA'],
            Auditoria: ['Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes'],
            Ubicacion: ['Latitud', 'Longitud'],
        };

        var columnLabels = {
            'Nombre_Almacen': 'Almacén', 'No_Tienda_Actual': '#',
            'Municipio': 'Municipio', 'Fecha_Apertura': 'Apertura',
            'TELEFONIA': '📞 Tel.',
            'Señal de celular': '📱 Señal', 'Compañía': 'Compañía', 'INTERNET': '🌐 Internet',
            'Vta_Mes': 'Vta Mes', 'VtaNeta_Mes': 'Vta Neta', 'Vta_Acu': 'Vta Acum',
            'VtaNeta_Acu': 'Vta Neta Acum', 'Bon_Mes': 'Bon Mes',
            'Cap_Tot': 'Cap Total', 'Cap_Com': 'Cap Com', 'Cap_Dic': 'Cap Dic',
            'Pagare_Monto': 'Pagare', 'Fec_CRA': 'Fec CRA', 'Vigencia': 'Vigencia',
            'Nom_Pre_CRA': 'Presidente', 'Nom_Pre_Sup_CRA': 'Pres. Suplente',
            'Nom_Sec_CRA': 'Secretario', 'Nom_Sec_Sup_CRA': 'Sec. Suplente',
            'Nom_Tes_CRA': 'Tesorero', 'Nom_Vcv_CRA': 'Vocal', 'Nom_Voc_Gen_CRA': 'Vocal General',
            'Latitud': 'Latitud', 'Longitud': 'Longitud',
            'Fch_Audit': 'Fch Audit',
            'Imp_Res_Audi_Mes': 'Impuesto', 'Audit_Realiza_Mes': 'Auditoría',
        };

        var moneyColumns = {
            Cap_Tot: true, Cap_Com: true, Cap_Dic: true, Pagare_Monto: true,
            Vta_Mes: true, VtaNeta_Mes: true, Vta_Acu: true, VtaNeta_Acu: true, Bon_Mes: true,
            Imp_Res_Audi_Mes: true,
        };

        var trackedColumns = [
            'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
            'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
            'Pagare_Monto', 'Fec_CRA', 'Vigencia', 'Fch_Audit', 'Imp_Res_Audi_Mes',
            'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Direccion',
            'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA',
            'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
        ];

        function isEmpty(val) {
            return val === '' || val === null || val === undefined || val === '0' || String(val).trim() === '';
        }

        function getActiveColumns() {
            var cols = [];
            document.querySelectorAll('[data-group] input').forEach(function (cb) {
                var group = cb.closest('[data-group]').dataset.group;
                if (cb.checked && columnGroups[group]) {
                    cols = cols.concat(columnGroups[group]);
                }
            });
            return cols;
        }

        function getValue(store, col) {
            return store[col] !== undefined ? String(store[col]).trim() : '';
        }

        function applyFilters() {
            var search = document.getElementById('filter-search').value.trim().toLowerCase();
            var onlyIncompletos = document.getElementById('filter-incompletos').checked;
            var onlySinCapital = document.getElementById('filter-sinCapital').checked;

            filtered = allStores.filter(function (store) {
                if (search) {
                    var name = (store.Nombre_Almacen || '').toLowerCase();
                    var clave = (store.Clave_Sucursal || '').toLowerCase();
                    var num = String(store.No_Tienda_Actual || '');
                    if (name.indexOf(search) === -1 && clave.indexOf(search) === -1 && num.indexOf(search) === -1) {
                        return false;
                    }
                }
                if (onlyIncompletos || onlySinCapital) {
                    var hasEmpty = trackedColumns.some(function (c) { return isEmpty(getValue(store, c)); });
                    var noCapital = isEmpty(getValue(store, 'Cap_Tot'));
                    if (onlyIncompletos && !hasEmpty) return false;
                    if (onlySinCapital && !noCapital) return false;
                }
                return true;
            });

            currentPage = 1;
            renderTable();
        }

        function renderTable() {
            var cols = getActiveColumns();
            var totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
            if (currentPage > totalPages) currentPage = totalPages;

            var start = (currentPage - 1) * PAGE_SIZE;
            var end = Math.min(start + PAGE_SIZE, filtered.length);
            var pageData = filtered.slice(start, end);

            var header = document.getElementById('dir-header');
            var body = document.getElementById('dir-body');

            header.innerHTML = cols.map(function (c) {
                return '<th class="text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">' + (columnLabels[c] || c) + '</th>';
            }).join('');

            body.innerHTML = pageData.map(function (store) {
                var noCapital = isEmpty(getValue(store, 'Cap_Tot'));
                return '<tr class="hover:bg-gray-50' + (noCapital ? ' bg-orange-50' : '') + '">' +
                    cols.map(function (c) {
                        var val = getValue(store, c);
                        if (isEmpty(val)) {
                            return '<td class="cell-empty"></td>';
                        }
                        var display = moneyColumns[c] ? formatMoney(val) : escapeHtml(val);
                        return '<td class="text-gray-700">' + display + '</td>';
                    }).join('') +
                    '</tr>';
            }).join('');

            var totalFiltered = filtered.length;
            document.getElementById('info-from').textContent = totalFiltered > 0 ? start + 1 : 0;
            document.getElementById('info-to').textContent = end;
            document.getElementById('info-total').textContent = totalFiltered;

            document.getElementById('stat-page').textContent = totalPages > 0 ? currentPage : 0;
            document.getElementById('stat-pages').textContent = totalPages;

            var incompCount = filtered.filter(function (s) {
                return trackedColumns.some(function (c) { return isEmpty(getValue(s, c)); });
            }).length;
            var sinCapCount = filtered.filter(function (s) {
                return isEmpty(getValue(s, 'Cap_Tot'));
            }).length;
            document.getElementById('stat-incompletos').textContent = incompCount;
            document.getElementById('stat-sinCapital').textContent = sinCapCount;

            renderPagination(totalPages);
        }

        function formatMoney(val) {
            var num = parseFloat(String(val).replace(/,/g, ''));
            if (isNaN(num)) return null;
            return '$' + num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function escapeHtml(str) {
            var d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
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
            var total = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
            if (currentPage < total) { currentPage++; renderTable(); }
        });

        document.querySelectorAll('[data-group] input').forEach(function (cb) {
            cb.addEventListener('change', function () {
                renderTable();
            });
        });

        document.getElementById('filter-search').addEventListener('input', applyFilters);
        document.getElementById('filter-incompletos').addEventListener('change', applyFilters);
        document.getElementById('filter-sinCapital').addEventListener('change', applyFilters);
        document.getElementById('filter-clear').addEventListener('click', function () {
            document.getElementById('filter-search').value = '';
            document.getElementById('filter-incompletos').checked = false;
            document.getElementById('filter-sinCapital').checked = false;
            applyFilters();
        });

        filtered = allStores.slice();
        renderTable();
    });
</script>
@endpush
