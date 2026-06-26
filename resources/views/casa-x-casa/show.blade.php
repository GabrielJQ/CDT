@extends('layouts.app', ['pageTitle' => 'Tienda CxC #' . $store->no_tienda])

@section('title', "Tienda #{$store->no_tienda} — {$store->almacen} — CDT")

@section('content')
    <div class="mb-4">
        <a href="{{ route('casa-x-casa.directorio') }}" class="text-xs text-blue-600 hover:underline">← Volver al
            directorio</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6">
            <h3 class="text-base font-bold text-gray-800 dark:text-gray-100 mb-4">🏪 Datos de la tienda</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div class="text-gray-500 dark:text-gray-400">No. Tienda</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->no_tienda }}</div>

                <div class="text-gray-500 dark:text-gray-400">Almacén</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->almacen }}</div>

                <div class="text-gray-500 dark:text-gray-400">Unidad Operativa</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->unidad_operativa }}</div>

                <div class="text-gray-500 dark:text-gray-400">Estado</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->estado }}</div>

                <div class="text-gray-500 dark:text-gray-400">Municipio</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->municipio }}</div>

                <div class="text-gray-500 dark:text-gray-400">Localidad</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->localidad }}</div>

                <div class="text-gray-500 dark:text-gray-400">Dirección</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->direccion ?: '—' }}</div>

                <div class="text-gray-500 dark:text-gray-400">Encargado</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->encargado ?: '—' }}</div>
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6">
            <h3 class="text-base font-bold text-gray-800 dark:text-gray-100 mb-4">📦 Instalación</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div class="text-gray-500 dark:text-gray-400">Tipo de anaquel</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->tipo_anaquel ?: '—' }}</div>

                <div class="text-gray-500 dark:text-gray-400">Anaqueles instalados</div>
                <div class="font-semibold">
                    @if($store->anaqueles_instalados)
                        <span class="text-green-600">✅ Sí</span>
                    @else
                        <span class="text-orange-500">⏳ No</span>
                    @endif
                </div>

                <div class="text-gray-500 dark:text-gray-400">Aviso de funcionamiento</div>
                <div class="font-semibold">
                    @if($store->aviso_funcionamiento)
                        <span class="text-green-600">✅ Sí</span>
                    @else
                        <span class="text-orange-500">⏳ No</span>
                    @endif
                </div>

                <div class="text-gray-500 dark:text-gray-400">Estatus</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->estatus ?: '—' }}</div>

                <div class="text-gray-500 dark:text-gray-400">Comentarios</div>
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $store->comentarios ?: '—' }}</div>
            </dl>

            @if($store->latitud && $store->longitud)
                <hr class="my-4 border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500">📍 {{ $store->latitud }}, {{ $store->longitud }}</div>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6 mb-6">
        <h3 class="text-base font-bold text-gray-800 dark:text-gray-100 mb-4">🔗 Cruce con directorio nacional de tiendas
        </h3>
        @if($cruce)
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <p class="text-sm text-green-700 dark:text-green-300 font-semibold mb-3">✅ Esta tienda existe en el catálogo
                    nacional</p>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-gray-500 dark:text-gray-400">Fecha Apertura</div>
                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $cruce->Fecha_Apertura ?: '—' }}</div>

                    <div class="text-gray-500 dark:text-gray-400">Teléfono</div>
                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $cruce->TELEFONIA ?: '—' }}</div>

                    <div class="text-gray-500 dark:text-gray-400">Internet</div>
                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $cruce->INTERNET ?: '—' }}</div>

                    <div class="text-gray-500 dark:text-gray-400">Señal Celular</div>
                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $cruce->{'Señal de celular'} ?: '—' }}</div>

                    <div class="text-gray-500 dark:text-gray-400">Compañía</div>
                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $cruce->Compañía ?: '—' }}</div>

                    <div class="text-gray-500 dark:text-gray-400">Capital Total</div>
                    <div class="font-semibold text-gray-800 dark:text-gray-100">
                        {{ $cruce->Cap_Tot ? '$' . number_format($cruce->Cap_Tot, 2) : '—' }}</div>

                    <div class="text-gray-500 dark:text-gray-400">Vigencia</div>
                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $cruce->Vigencia ?: '—' }}</div>
                </dl>
            </div>
        @else
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <p class="text-sm text-orange-700 dark:text-orange-300">⚠️ Esta tienda <strong>no está registrada</strong> en el
                    directorio nacional de tiendas.</p>
            </div>
        @endif
    </div>
@endsection