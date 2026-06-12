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
 .dark .page-btn.active { background: #14532d; border-color: #14532d; }
 .dark .col-toggle input { accent-color: #4ade80; }
</style>
@endpush

@section('content')
 @isset($error)
 <div class="bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">{{ $error }}</div>
 @endisset

 <div id="app" class="page-shell">
 {{-- KPIs --}}
 @php $aperTotal = $kpis['total']; @endphp
 <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas mostradas</p>
 <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $kpis['total'] }}
 @if($filteredCount !== $totalCount)
 <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ $totalCount }} totales</span>
 @endif
 </p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-green-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Abiertas este mes</p>
 <p class="text-2xl font-bold text-green-600">{{ $kpis['esteMes'] }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $kpis['total'] > 0 ? round($kpis['esteMes'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-amber-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Abiertas este año</p>
 <p class="text-2xl font-bold text-amber-600">{{ $kpis['esteAnio'] }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $kpis['total'] > 0 ? round($kpis['esteAnio'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-gray-400">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">⚠️ Sin fecha de apertura</p>
 <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $kpis['sinFecha'] }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $kpis['total'] > 0 ? round($kpis['sinFecha'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
 </div>
 </div>

 {{-- Filters --}}
  <div class="filter-panel">
 <form method="GET" action="{{ url('/aperturas') }}" class="flex flex-wrap items-end gap-3">
 <div class="flex-1 min-w-[160px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
 <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
 placeholder="Buscar..."
  class="input-filter">
 </div>
 <div class="min-w-[150px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Desde</label>
 <input type="date" name="desde" value="{{ $filters['desde'] }}"
  class="input-filter">
 </div>
 <div class="min-w-[150px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Hasta</label>
 <input type="date" name="hasta" value="{{ $filters['hasta'] }}"
  class="input-filter">
 </div>
 <div class="flex gap-2">
  <button type="submit" class="btn-filter">Filtrar</button>
  <a href="{{ url('/aperturas') }}" class="btn-secondary">Limpiar</a>
  <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-export">⬇ CSV</a>
 </div>
 </form>
 </div>

 {{-- Column toggles --}}
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
 <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold self-center">Columnas:</span>
  <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200" data-group="General">
  <input type="checkbox" checked disabled class="opacity-50"> 📋 General
  </label>
  <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Apertura">
  <input type="checkbox" checked> 📅 Apertura
  </label>
 </div>

 {{-- Count --}}
 <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
 Mostrando <strong id="info-from">0</strong>–<strong id="info-to">0</strong> de <strong id="info-total">{{ count($stores) }}</strong> tiendas
 @if($filteredCount !== $totalCount)
 <span class="text-gray-400 dark:text-gray-500">(filtradas de {{ $totalCount }})</span>
 @endif
 </div>

 {{-- Table --}}
  <div class="table-shell">
 <table id="aper-table" class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200">
 <thead class="bg-gray-50 dark:bg-gray-800">
 <tr id="aper-header"></tr>
 </thead>
 <tbody id="aper-body" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
 </table>
 </div>

 {{-- Pagination --}}
 <div class="flex items-center justify-between mt-4">
 <button id="page-prev" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 disabled:opacity-30 disabled:cursor-not-allowed transition">
 ← Anterior
 </button>
 <div id="page-numbers" class="flex gap-1"></div>
 <button id="page-next" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 disabled:opacity-30 disabled:cursor-not-allowed transition">
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
 if (!isoStr) return '<span class="text-gray-400 dark:text-gray-500">—</span>';
 var parts = isoStr.substring(0, 10).split('-');
 return '<span class="font-mono text-gray-700 dark:text-gray-300">' + parts[2] + '/' + parts[1] + '/' + parts[0] + '</span>';
 }

 function renderCell(col, store) {
 if (col === 'Nombre_Almacen') return '<strong class="text-gray-900 dark:text-gray-100">' + esc(store[col] || '—') + '</strong>';
 if (col === 'Localidad' || col === 'Municipio') return esc(store[col] || '—');
 if (col === 'No_Tienda_Actual') {
 var n = store[col];
 return '<span class="font-mono text-gray-700 dark:text-gray-300 text-center block">' + (n || '—') + '</span>';
 }

 if (col === '_fecha_apertura') {
 var f = store._fecha_apertura;
 if (f) return '<div class="text-center font-mono text-gray-700 dark:text-gray-300">' + formatDate(f) + '</div>';
 return '<div class="text-center text-gray-400 dark:text-gray-500">—</div>';
 }

 if (col === '_antiguedad') {
 var iso = store._fecha_apertura;
 if (!iso) return '<div class="text-center text-gray-400 dark:text-gray-500">—</div>';
 var d = new Date(iso);
 var now = new Date();
 var diffMs = now - d;
 var diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
 var diffMeses = Math.floor(diffDias / 30);
 var label, color;
 if (diffDias <= 0) { label = 'Hoy'; color = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'; }
 else if (diffDias < 30) { label = diffDias + ' día' + (diffDias > 1 ? 's' : ''); color = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'; }
 else if (diffMeses < 12) { label = diffMeses + ' mes' + (diffMeses > 1 ? 'es' : ''); color = diffMeses <= 3 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'; }
  else { var anos = Math.floor(diffMeses / 12); label = anos + ' año' + (anos > 1 ? 's' : ''); color = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200'; }
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
 return '<th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">' + (columnLabels[c] || c) + '</th>';
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
 var cls = isRecent ? ' bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30' : ' hover:bg-gray-50 dark:hover:bg-gray-700/30';
 return '<tr class="' + cls + '">' +
 cols.map(function (c) {
 return '<td class="text-gray-700 dark:text-gray-300">' + renderCell(c, store) + '</td>';
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
 container.innerHTML += '<span class="text-gray-400 dark:text-gray-500 px-1 self-end">…</span>';
 } else {
 container.innerHTML += '<button class="page-btn px-2.5 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition hover:bg-gray-100 dark:hover:bg-gray-700/30 ' + (p === currentPage ? 'active' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300') + '" data-page="' + p + '">' + p + '</button>';
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

 var storageKey = 'col_prefs_aperturas';
 function saveColPrefs() {
 var prefs = {};
 document.querySelectorAll('[data-group] input').forEach(function (cb) {
 if (!cb.disabled) {
 prefs[cb.closest('[data-group]').dataset.group] = cb.checked;
 }
 });
 localStorage.setItem(storageKey, JSON.stringify(prefs));
 }
 function loadColPrefs() {
 var saved = localStorage.getItem(storageKey);
 if (saved) {
 try {
 var prefs = JSON.parse(saved);
 document.querySelectorAll('[data-group] input').forEach(function (cb) {
 if (!cb.disabled) {
 var group = cb.closest('[data-group]').dataset.group;
 if (prefs[group] !== undefined) {
 cb.checked = prefs[group];
 }
 }
 });
 } catch(e) {}
 }
 }

 document.querySelectorAll('[data-group] input').forEach(function (cb) {
 cb.addEventListener('change', function () {
 currentPage = 1;
 saveColPrefs();
 renderTable();
 });
 });

 loadColPrefs();
 renderTable();
 });
</script>
@endpush


