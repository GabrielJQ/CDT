@extends('layouts.app', ['pageTitle' => 'Directorio CxC'])

@section('title', 'Directorio Casa por Casa — CDT')

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100">🏪 Directorio Tiendas Salud Casa por Casa</h3>
            <span class="text-xs text-gray-500">{{ number_format($totalCount) }} tiendas</span>
        </div>

        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Estado</label>
                <select name="estado" onchange="this.form.submit()" class="w-full text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <option value="">Todos</option>
                    @foreach($estados as $e)
                        <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Unidad Operativa</label>
                <select name="uo" onchange="this.form.submit()" class="w-full text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <option value="">Todas</option>
                    @foreach($unidadesOperativas as $uo)
                        <option value="{{ $uo }}" {{ request('uo') === $uo ? 'selected' : '' }}>{{ $uo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Estatus</label>
                <select name="estatus" onchange="this.form.submit()" class="w-full text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
                    <option value="">Todos</option>
                    @foreach($estatusList as $s)
                        <option value="{{ $s }}" {{ request('estatus') === $s ? 'selected' : '' }}>{{ $s ?: 'Sin estatus' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Almacén, tienda, municipio..."
                    class="w-full text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 text-xs uppercase">
                        <th class="py-2 pr-3">Tienda</th>
                        <th class="py-2 pr-3">Almacén</th>
                        <th class="py-2 pr-3">Municipio</th>
                        <th class="py-2 pr-3">Estado</th>
                        <th class="py-2 pr-3">U. Operativa</th>
                        <th class="py-2 pr-3">Encargado</th>
                        <th class="py-2 pr-3">Anaquel</th>
                        <th class="py-2 pr-3">Estatus</th>
                        <th class="py-2 pr-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stores as $s)
                        <tr class="border-b border-gray-100 dark:border-gray-700/50 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                            <td class="py-2 pr-3 font-mono text-xs">{{ $s->no_tienda }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $s->almacen }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $s->municipio }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $s->estado }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $s->unidad_operativa }}</td>
                            <td class="py-2 pr-3 text-xs max-w-32 truncate" title="{{ $s->encargado }}">{{ $s->encargado ?: '—' }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $s->tipo_anaquel ?: '—' }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $s->estatus ?: '—' }}</td>
                            <td class="py-2 pr-3">
                                <a href="{{ route('casa-x-casa.show', $s->id) }}" class="text-blue-600 hover:underline text-xs">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-sm text-gray-400">No se encontraron tiendas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $stores->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
