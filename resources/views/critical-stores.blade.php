@extends('layouts.app', ['pageTitle' => 'Información de Tiendas'])

@section('title', 'Información de Tiendas — Dashboard CDT')

@push('head')
<style>
 #cs-table td, #cs-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
 #cs-table th { position: sticky; top: 0; z-index: 1; }
 .page-btn { min-width: 2rem; text-align: center; }
 .page-btn.active { background: #166534; color: white; border-color: #166534; }
 .col-toggle { user-select: none; cursor: pointer; }
 .col-toggle input { accent-color: #166534; }
 .dark .page-btn.active { background: #14532d; border-color: #14532d; }
 .dark .col-toggle input { accent-color: #4ade80; }
</style>
@endpush

@section('content')
 @isset($error)
 <div class="bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">{{ $error }}</div>
 @endisset

 <div id="app">
 {{-- KPIs --}}
 <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-red-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🔴 Críticas</p>
 <p class="text-3xl font-bold text-red-600" id="kpi-rojo">{{ $summary['rojo'] }}</p>
 <p class="text-xs text-gray-400 dark:text-gray-500">{{ $totalCount > 0 ? round($summary['rojo'] / $totalCount * 100) : 0 }}% del total</p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-yellow-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🟡 Monitoreo</p>
 <p class="text-3xl font-bold text-yellow-600" id="kpi-amarillo">{{ $summary['amarillo'] }}</p>
 <p class="text-xs text-gray-400 dark:text-gray-500">{{ $totalCount > 0 ? round($summary['amarillo'] / $totalCount * 100) : 0 }}% del total</p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-green-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🟢 Normales</p>
 <p class="text-3xl font-bold text-green-600" id="kpi-verde">{{ $summary['verde'] }}</p>
 <p class="text-xs text-gray-400 dark:text-gray-500">{{ $totalCount > 0 ? round($summary['verde'] / $totalCount * 100) : 0 }}% del total</p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Total tiendas</p>
 <p class="text-3xl font-bold text-blue-600" id="kpi-total">{{ $totalCount }}</p>
 <p class="text-xs text-gray-400 dark:text-gray-500">{{ $filteredCount !== $totalCount ? 'Filtradas: ' . $filteredCount : 'Sin filtros' }}</p>
 </div>
 </div>

 {{-- Desglose por factor --}}
 @if(!empty($summary['desgloseLabels']))
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 mb-6">
 <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">📊 Factores más recurrentes</p>
 <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
 @foreach($summary['desgloseLabels'] as $factor)
 <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
 <div class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $factor['count'] }}</div>
 <div class="text-xs text-gray-500 dark:text-gray-400">{{ $factor['label'] }}</div>
 </div>
 @endforeach
 </div>
 </div>
 @endif

 {{-- Filters --}}
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-4">
 <form method="GET" action="{{ url('/informacion-tiendas') }}" class="flex flex-wrap items-end gap-3">
 <div class="flex-1 min-w-[160px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
 <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
 placeholder="Buscar..."
 class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
 </div>
 <div class="min-w-[140px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Nivel</label>
 <select name="nivel" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white dark:bg-gray-800">
 <option value="">Todos</option>
 <option value="rojo" {{ $filters['nivel'] === 'rojo' ? 'selected' : '' }}>🔴 Crítico</option>
 <option value="amarillo" {{ $filters['nivel'] === 'amarillo' ? 'selected' : '' }}>🟡 Monitoreo</option>
 <option value="verde" {{ $filters['nivel'] === 'verde' ? 'selected' : '' }}>🟢 Normal</option>
 </select>
 </div>
   <div class="min-w-[190px]">
   <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Indicador</label>
   <select name="indicador" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white dark:bg-gray-800">
   <option value="">Todos</option>
   @foreach($indicadores as $key => $label)
   <option value="{{ $key }}" {{ ($filters['indicador'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
   @endforeach
   </select>
   </div>
  <div class="flex gap-2">
  <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Filtrar</button>
  <a href="{{ url('/informacion-tiendas') }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">Limpiar</a>
  <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">⬇ CSV</a>
  </div>
  </form>
 </div>

 {{-- Column toggles --}}
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
 <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold self-center">Columnas:</span>
  <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200" data-group="General">
  <input type="checkbox" checked disabled class="opacity-50"> 📋 General
  </label>
  <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Factores">
  <input type="checkbox" checked> 🔴 Factores
  </label>
  <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Detalle">
  <input type="checkbox" checked> 📝 Detalle
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
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-x-auto">
 <table id="cs-table" class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200">
 <thead class="bg-gray-50 dark:bg-gray-800">
 <tr id="cs-header"></tr>
 </thead>
 <tbody id="cs-body" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
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

  var FACTOR_KEYS = ['capital_bajo', 'capital_dictaminado_bajo', 'comite_vencido', 'auditoria_elevada', 'pagare_vencido', 'rotacion_baja', 'asamblea_pendiente'];
  var FACTOR_LABELS = {
  capital_bajo: 'Capital total bajo',
  capital_dictaminado_bajo: 'Capital Bienestar bajo',
  comite_vencido: 'Comité vencido',
  auditoria_elevada: 'Auditoría > $500k',
  pagare_vencido: 'Pagare vencido',
  rotacion_baja: 'Rotación baja',
  asamblea_pendiente: 'Asamblea pendiente',
  };

  var columnGroups = {
  General: ['Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio'],
  Factores: ['Factores'],
  Detalle: ['Detalle'],
  };
 
  var columnLabels = {
  Estado: 'Estado',
  Nombre_Almacen: 'Almacén',
  No_Tienda_Actual: 'Tienda #',
  Municipio: 'Municipio',
  Factores: 'Factores',
  Detalle: 'Detalle',
  };

 function renderCell(col, store) {
 var e = store._critico || {};

 if (col === 'Estado') {
 var levelConfig = {
 rojo: ['bg-red-100 text-red-800', '🔴 ' + e.count + ' — Crítico'],
 amarillo: ['bg-yellow-100 text-yellow-800', '🟡 ' + e.count + ' — Monitoreo'],
 verde: ['bg-green-100 text-green-800', '🟢 ' + e.count + ' — Normal'],
 };
 var cfg = levelConfig[e.level] || levelConfig.verde;
 return '<span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold ' + cfg[0] + '">' + cfg[1] + '</span>';
 }

  if (col === 'Nombre_Almacen') return '<strong class="text-gray-900 dark:text-gray-100">' + esc(store[col] || '—') + '</strong>';
  if (col === 'No_Tienda_Actual') {
  var n = store[col];
  return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">' + (n || '—') + '</span>';
  }
  if (col === 'Municipio') return esc(store[col] || '—');

 if (col === 'Factores') {
 return FACTOR_KEYS.map(function (key) {
 var active = e.conditions && e.conditions[key];
 var lbl = (e.labels && e.labels[key] && e.labels[key].label) || FACTOR_LABELS[key] || key;
 var title = active ? '🔴 ' + lbl : '⚪ ' + lbl;
 var html = active ? '<span class="text-base cursor-help" title="' + esc(title) + '">🔴</span>' : '<span class="text-base text-gray-300 cursor-help" title="' + esc(title) + '">⚪</span>';
 return html;
 }).join(' ');
 }

 if (col === 'Detalle') {
 if (!e.conditions || !e.labels) return '<span class="text-gray-400 dark:text-gray-500 text-xs">Sin incidencias</span>';
 var active = FACTOR_KEYS.filter(function (k) { return e.conditions[k]; });
 if (active.length === 0) return '<span class="text-gray-400 dark:text-gray-500 text-xs">Sin incidencias</span>';
  var factorStyles = {
  capital_bajo: ['bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700', '💰'],
  capital_dictaminado_bajo: ['bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-700', '🏛️'],
  comite_vencido: ['bg-red-100 text-red-800 border-red-300 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700', '📅'],
 auditoria_elevada: ['bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-700', '🔍'],
  pagare_vencido: ['bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700', '📄'],
 rotacion_baja: ['bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700', '📉'],
 asamblea_pendiente: ['bg-cyan-100 text-cyan-800 border-cyan-300 dark:bg-cyan-900/30 dark:text-cyan-300 dark:border-cyan-700', '🗳️'],
 };
 return '<div class="flex flex-wrap gap-1.5 max-w-md">' +
 active.map(function (k) {
 var info = e.labels[k] || {};
 var st = factorStyles[k] || ['bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600', '▪'];
 return '<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-lg border ' + st[0] + '">' +
 st[1] + ' ' + esc(info.label || k) +
 '<span class="font-normal opacity-70 ml-0.5">' + esc(info.detail || '') + '</span>' +
 '</span>';
 }).join('') +
 '</div>';
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

 var headerRow = document.getElementById('cs-header');
 headerRow.innerHTML = cols.map(function (c) {
 return '<th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">' + (columnLabels[c] || c) + '</th>';
 }).join('');

 var body = document.getElementById('cs-body');
 body.innerHTML = pageData.map(function (store) {
 var level = (store._critico || {}).level || 'verde';
 var bgClass = level === 'rojo' ? ' bg-red-50 dark:bg-red-900/20' : (level === 'amarillo' ? ' bg-amber-50 dark:bg-amber-900/20' : '');
 return '<tr class="hover:bg-gray-100 dark:hover:bg-gray-700/30' + bgClass + '">' +
 cols.map(function (c) {
 return '<td>' + renderCell(c, store) + '</td>';
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

  var storageKey = 'col_prefs_critical';
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
