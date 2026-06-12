@extends('layouts.app', ['pageTitle' => 'Auditoría'])

@section('title', 'Auditoría — Dashboard CDT')

@push('head')
<style>
 #audit-table td, #audit-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
 #audit-table th { position: sticky; top: 0; z-index: 1; }
 .badge { display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }
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

 <div id="app" class="page-shell">
 {{-- KPIs --}}
 @php $kpiTotal = $totalCount; @endphp
 <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas evaluadas</p>
 <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $filteredCount }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ $totalCount }}</span></p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-red-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏛️ Comités de CRA vencidos</p>
 <p class="text-2xl font-bold text-red-600">{{ $kpis['comitesVencidos'] }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['comitesVencidos'] / $totalCount * 100, 1) : 0 }}%)</span></p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-orange-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🔍 Auditorías mayores a $500,000</p>
 <p class="text-2xl font-bold text-orange-600">{{ $kpis['auditoriaAlta'] }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['auditoriaAlta'] / $totalCount * 100, 1) : 0 }}%)</span></p>
 </div>
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-amber-500">
  <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">📉 Rotación menor a 0.5</p>
 <p class="text-2xl font-bold text-amber-600">{{ $kpis['rotacionBaja'] }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['rotacionBaja'] / $totalCount * 100, 1) : 0 }}%)</span></p>
 </div>
 </div>

 <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-gray-400">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Auditorías pendientes (+3 meses)</p>
 <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $kpis['auditoriaPendiente'] }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['auditoriaPendiente'] / $totalCount * 100, 1) : 0 }}%)</span></p>
 </div>
 </div>

  {{-- Rotación Desglose --}}
  <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Desglose de Rotación</h3>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-gray-500">
          <p class="text-xs text-gray-500 dark:text-gray-400">Rotación cero</p>
          <p class="text-xl font-bold text-gray-600">{{ $kpis['rotacionCero'] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionCero'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-red-500">
          <p class="text-xs text-gray-500 dark:text-gray-400">Rotación crítica (&lt;0.5)</p>
          <p class="text-xl font-bold text-red-600">{{ $kpis['rotacionCritico'] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionCritico'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-amber-500">
          <p class="text-xs text-gray-500 dark:text-gray-400">Rotación media (0.5 a 0.99)</p>
          <p class="text-xl font-bold text-amber-600">{{ $kpis['rotacionAmarillo'] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionAmarillo'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-green-500">
          <p class="text-xs text-gray-500 dark:text-gray-400">Rotación óptima (&ge;1)</p>
          <p class="text-xl font-bold text-green-600">{{ $kpis['rotacionOptimo'] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionOptimo'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
      </div>
  </div>

 {{-- Auditorías Desglose --}}
 <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Tiempos de Auditoría</h3>
 <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
     <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-blue-500">
         <p class="text-xs text-gray-500 dark:text-gray-400">Realizadas este mes</p>
         <p class="text-xl font-bold text-blue-600">{{ $kpis['auditoriasMes'] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['auditoriasMes'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
     </div>
     <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-orange-500">
         <p class="text-xs text-gray-500 dark:text-gray-400">Sin auditoría &gt; 3 meses (Trimestre)</p>
         <p class="text-xl font-bold text-orange-600">{{ $kpis['sinAuditoriaTrimestre'] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['sinAuditoriaTrimestre'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
     </div>
     <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-red-500">
         <p class="text-xs text-gray-500 dark:text-gray-400">Sin auditoría &gt; 1 año</p>
         <p class="text-xl font-bold text-red-600">{{ $kpis['sinAuditoriaAnio'] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['sinAuditoriaAnio'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
     </div>
 </div>

 {{-- Filters --}}
  <div class="filter-panel">
 <form method="GET" action="{{ url('/auditoria') }}" class="flex flex-wrap items-end gap-3">
 <div class="flex-1 min-w-[160px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
 <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
 placeholder="Buscar..."
  class="input-filter">
 </div>
 <div class="min-w-[140px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Nivel de riesgo</label>
 <select name="nivel"
  class="input-filter">
 <option value="">Todos</option>
 <option value="rojo" {{ $filters['nivel'] === 'rojo' ? 'selected' : '' }}>🔴 Crítico</option>
 <option value="amarillo" {{ $filters['nivel'] === 'amarillo' ? 'selected' : '' }}>🟡 Monitoreo</option>
 <option value="verde" {{ $filters['nivel'] === 'verde' ? 'selected' : '' }}>🟢 Normal</option>
 </select>
 </div>
 <div class="min-w-[150px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estado del comité</label>
 <select name="estado_comite"
  class="input-filter">
 <option value="">Todos</option>
 <option value="vigente" {{ $filters['estado_comite'] === 'vigente' ? 'selected' : '' }}>🟢 Vigente</option>
 <option value="proximo_a_vencer" {{ $filters['estado_comite'] === 'proximo_a_vencer' ? 'selected' : '' }}>🟡 Próximo a vencer</option>
 <option value="vencido" {{ $filters['estado_comite'] === 'vencido' ? 'selected' : '' }}>🔴 Vencido</option>
 <option value="sin_fecha" {{ $filters['estado_comite'] === 'sin_fecha' ? 'selected' : '' }}>⚪ Sin fecha</option>
 </select>
 </div>
 <div class="min-w-[150px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estado de auditoría</label>
 <select name="estado_auditoria"
  class="input-filter">
 <option value="">Todos</option>
 <option value="al_dia" {{ $filters['estado_auditoria'] === 'al_dia' ? 'selected' : '' }}>🟢 Al día</option>
 <option value="vencida" {{ $filters['estado_auditoria'] === 'vencida' ? 'selected' : '' }}>🔴 Vencida</option>
 <option value="sin_fecha" {{ $filters['estado_auditoria'] === 'sin_fecha' ? 'selected' : '' }}>⚪ Sin fecha</option>
 </select>
 </div>
 <div class="min-w-[140px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Aud. &gt; $500k</label>
 <select name="filtro_500k"
  class="input-filter">
 <option value="">Todos</option>
 <option value="si" {{ $filters['filtro_500k'] === 'si' ? 'selected' : '' }}>🔴 Sí</option>
 <option value="no" {{ $filters['filtro_500k'] === 'no' ? 'selected' : '' }}>🟢 No</option>
 </select>
 </div>
 <div class="min-w-[150px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Rango Rotación</label>
 <select name="rango_rotacion"
  class="input-filter">
  <option value="">Todos</option>
  <option value="cero" {{ $filters['rango_rotacion'] === 'cero' ? 'selected' : '' }}>Cero</option>
  <option value="critico" {{ $filters['rango_rotacion'] === 'critico' ? 'selected' : '' }}>Crítico (&lt;0.5)</option>
  <option value="amarillo" {{ $filters['rango_rotacion'] === 'amarillo' ? 'selected' : '' }}>Amarillo (0.5 a 0.99)</option>
  <option value="optimo" {{ $filters['rango_rotacion'] === 'optimo' ? 'selected' : '' }}>Óptimo (&ge;1)</option>
 </select>
 </div>
 <div class="min-w-[150px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Tiempo Auditoría</label>
 <select name="tiempo_auditoria"
  class="input-filter">
 <option value="">Todos</option>
 <option value="mes" {{ $filters['tiempo_auditoria'] === 'mes' ? 'selected' : '' }}>Realizada en mes</option>
 <option value="trimestre" {{ $filters['tiempo_auditoria'] === 'trimestre' ? 'selected' : '' }}>Sin aud. &gt; 3 meses</option>
 <option value="anio" {{ $filters['tiempo_auditoria'] === 'anio' ? 'selected' : '' }}>Sin aud. &gt; 1 año</option>
 </select>
 </div>
 <div class="min-w-[150px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Asambleas (Mes)</label>
 <select name="asambleas_mes"
  class="input-filter">
 <option value="">Todas</option>
 <option value="si" {{ $filters['asambleas_mes'] === 'si' ? 'selected' : '' }}>Con asambleas</option>
 <option value="no" {{ $filters['asambleas_mes'] === 'no' ? 'selected' : '' }}>Sin asambleas</option>
 </select>
 </div>
 <div class="flex gap-2">
  <button type="submit" class="btn-filter">Filtrar</button>
  <a href="{{ url('/auditoria') }}" class="btn-secondary">Limpiar</a>
 </div>
 </form>
 </div>

 {{-- Column toggles --}}
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
 <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold self-center">Columnas:</span>
  <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200" data-group="General">
  <input type="checkbox" checked disabled class="opacity-50"> 📋 General
  </label>
  <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Comite">
  <input type="checkbox" checked> 🏛️ Comité
  </label>
  <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Auditoria">
  <input type="checkbox" checked> 🔍 Auditoría
  </label>
  <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Rendimiento">
  <input type="checkbox" checked> 📊 Rendimiento
  </label>
 </div>

 {{-- Count --}}
 <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
 Mostrando <strong id="info-from">0</strong>–<strong id="info-to">0</strong> de <strong id="info-total">{{ $filteredCount }}</strong> tiendas
 @if($filteredCount !== $totalCount)
 <span class="text-gray-400 dark:text-gray-500">(filtradas de {{ $totalCount }})</span>
 @endif
 </div>

 {{-- Table --}}
  <div class="table-shell">
 <table id="audit-table" class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200">
 <thead class="bg-gray-50 dark:bg-gray-800">
 <tr id="audit-header"></tr>
 </thead>
 <tbody id="audit-body" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
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
@php
 $paginationPayload = $serverPagination ?? ['page' => 1, 'perPage' => 50, 'total' => count($stores), 'totalPages' => 1];
@endphp
<script>
  document.addEventListener('DOMContentLoaded', function () {
 var serverPagination = @json($paginationPayload);
 var PAGE_SIZE = serverPagination.perPage;
 var allStores = @json($stores);
 var currentPage = serverPagination.page;

 var columnGroups = {
 General: ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'],
 Comite: ['Vigencia', 'Comite', 'Fec_CRA', 'Asam_Real_Mes'],
 Auditoria: ['Fch_Audit', 'Estado_Aud', 'Imp_Res_Audi_Mes'],
 Rendimiento: ['Rotacion', 'Riesgo'],
 };

 var columnLabels = {
 Nombre_Almacen: 'Almacén',
 No_Tienda_Actual: 'Tienda #',
 Localidad: 'Localidad',
 Municipio: 'Municipio',
 Vigencia: 'Vigencia',
 Comite: 'Comité',
 Fec_CRA: 'Fecha CRA',
 Asam_Real_Mes: 'Asam. Mes',
 Fch_Audit: 'Fch. Audit',
 Estado_Aud: 'Estado Aud.',
 Imp_Res_Audi_Mes: 'Imp. Res. Audi.',
 Rotacion: 'Rotación',
 Riesgo: 'Riesgo',
 };

 function formatMoney(val) {
 var num = parseFloat(String(val).replace(/,/g, ''));
 if (isNaN(num)) return null;
 return '$' + num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
 }

 function mesesLabel(meses) {
 if (meses == null) return '';
 var m = Math.round(meses);
 if (m >= 12) {
 var a = Math.floor(m / 12);
 var r = m % 12;
 var label = a + ' año' + (a > 1 ? 's' : '');
 if (r > 0) label += ' ' + r + ' mes' + (r > 1 ? 'es' : '');
 return label;
 }
 return m + ' mes' + (m > 1 ? 'es' : '');
 }

 function renderCell(col, store) {
 var a = store._audit || {};
 if (col === 'Nombre_Almacen') return '<strong>' + esc(store[col] || '—') + '</strong>';
 if (col === 'Localidad' || col === 'Municipio') return esc(store[col] || '—');
 if (col === 'No_Tienda_Actual') {
 var n = store[col];
 return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">' + (n || '—') + '</span>';
 }

 if (col === 'Vigencia') {
 var d = a.vigencia;
 if (d) return '<span class="font-mono text-gray-700 dark:text-gray-300">' + d.substr(0, 10) + '</span>';
 return '<span class="text-gray-400 dark:text-gray-500">—</span>';
 }

 if (col === 'Comite') {
 var badges = { vigente: ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', '🟢 Vigente'], proximo_a_vencer: ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', '🟡 Próximo a vencer'], vencido: ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', '🔴 Vencido'] };
 var ec = a.estadoComite || 'sin_fecha';
 var b = badges[ec] || ['bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300', '⚪ Sin fecha'];
 return '<span class="badge ' + b[0] + '">' + b[1] + '</span>';
 }

 if (col === 'Fec_CRA') {
 var d = store[col];
 if (d) return '<span class="font-mono text-gray-700 dark:text-gray-300">' + String(d).substr(0, 10) + '</span>';
 return '<span class="text-gray-400 dark:text-gray-500">—</span>';
 }

 if (col === 'Asam_Real_Mes') {
 var v = parseInt(store[col]) || 0;
 var dateAsam = store['Asam_Fch_'] || '';
 var html = '';
 if (v > 0) {
 html = '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">' + v + ' asamblea(s)</span>';
 } else {
 html = '<span class="text-gray-400 dark:text-gray-500">0</span>';
 }
 if (dateAsam && dateAsam !== '0' && dateAsam !== '#N/A') {
 var d = String(dateAsam).substr(0, 10);
 html += '<br><span class="text-xs text-gray-500 dark:text-gray-400">📅 ' + d + '</span>';
 }
 return html;
 }

 if (col === 'Fch_Audit') {
 var d = a.fchAudit;
 if (d) return '<span class="font-mono text-gray-700 dark:text-gray-300">' + d.substr(0, 10) + '</span>';
 return '<span class="text-gray-400 dark:text-gray-500">—</span>';
 }

 if (col === 'Estado_Aud') {
 var fch = a.fchAudit;
 var meses = a.mesesSinAuditoria;
 var color, label, sub;
 if (!fch) { color = 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300'; label = '⚪ Sin fecha'; sub = ''; }
 else if (meses >= 3) { color = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'; label = '🔴 Vencida'; sub = mesesLabel(meses); }
 else { color = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'; label = '🟢 Al día'; sub = mesesLabel(meses); }
 var html = '<span class="badge ' + color + '">' + label + '</span>';
 if (sub) html += '<br><span class="text-xs text-gray-400 dark:text-gray-500">' + sub + '</span>';
 return html;
 }

 if (col === 'Imp_Res_Audi_Mes') {
 var imp = a.impuesto || 0;
 if (imp > 0) return '<span class="font-mono text-gray-700 dark:text-gray-300 text-right block">' + formatMoney(imp) + '</span>';
 return '<span class="text-gray-400 dark:text-gray-500">—</span>';
 }

 if (col === 'Rotacion') {
 var r = a.rotacion || 0;
 if (r > 0) return '<span class="font-mono text-gray-700 dark:text-gray-300">' + r.toFixed(2) + '</span>';
 return '<span class="text-gray-400 dark:text-gray-500">—</span>';
 }

 if (col === 'Riesgo') {
 var level = a.level || 'verde';
 var badges = { rojo: ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', '🔴 Crítico'], amarillo: ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', '🟡 Monitoreo'], verde: ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', '🟢 Normal'] };
 var b = badges[level] || ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', '🟢 Normal'];
 return '<span class="badge ' + b[0] + '">' + b[1] + '</span>';
 }

 return esc(store[col] || '');
 }

 function esc(str) {
 return window.CdtTables.escapeHtml(str);
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
 var total = serverPagination.total;
 var totalPages = serverPagination.totalPages;
 if (currentPage > totalPages) currentPage = totalPages;

 var start = (currentPage - 1) * PAGE_SIZE;
 var pageData = allStores;
 var end = Math.min(start + pageData.length, total);

 var headerRow = document.getElementById('audit-header');
 headerRow.innerHTML = cols.map(function (c) {
 return '<th class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800">' + (columnLabels[c] || c) + '</th>';
 }).join('');

 var body = document.getElementById('audit-body');
 body.innerHTML = pageData.map(function (store) {
 var level = (store._audit || {}).level || 'verde';
 var rowClass = level === 'rojo' ? ' bg-red-50 dark:bg-red-900/20' : (level === 'amarillo' ? ' bg-amber-50 dark:bg-amber-900/20' : '');
 return '<tr class="hover:bg-gray-100 dark:hover:bg-gray-700/30' + rowClass + '">' +
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
  btn.addEventListener('click', function () { goToPage(parseInt(this.dataset.page)); });
 });

 document.getElementById('page-prev').disabled = currentPage <= 1;
 document.getElementById('page-next').disabled = currentPage >= totalPages;
 }

 document.getElementById('page-prev').addEventListener('click', function () {
 if (currentPage > 1) goToPage(currentPage - 1);
 });
 document.getElementById('page-next').addEventListener('click', function () {
 if (currentPage < serverPagination.totalPages) goToPage(currentPage + 1);
 });

 function goToPage(page) {
 var url = new URL(window.location.href);
 url.searchParams.set('page', page);
 url.searchParams.set('per_page', PAGE_SIZE);
 window.location.href = url.toString();
 }

  var storageKey = 'col_prefs_auditoria';
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
      saveColPrefs();
      renderTable();
    });
  });

  loadColPrefs();
  renderTable();
 });
</script>
@endpush
