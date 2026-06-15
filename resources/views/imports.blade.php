@extends('layouts.app', ['pageTitle' => 'Carga Masiva'])

@section('title', 'Carga Masiva — CDT')

@section('content')
<div class="page-shell">
    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Importaciones</p>
                <h1 class="page-heading">Carga masiva de informacion</h1>
                <p class="page-subheading">Centraliza la actualizacion de tiendas regulares y Tiendas de Salud Casa por Casa. Valida el archivo correcto antes de iniciar la importacion.</p>
            </div>
            <a href="{{ route('imports.index') }}" class="btn-secondary">Recargar estado</a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
    <div class="xl:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach(['regular' => 'Tiendas Regulares', 'casa_x_casa' => 'Tiendas de Salud CxC'] as $tipo => $label)
            @php $periodoActivo = $periodosActivos[$tipo] ?? null; @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-[#988256]">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Periodo activo</p>
                <h3 class="text-base font-extrabold text-gray-900 dark:text-gray-100">{{ $label }}</h3>
                @if($periodoActivo)
                    <p class="mt-2 text-2xl font-bold text-green-700 dark:text-green-300">{{ $periodoActivo->anio }} {{ $periodoActivo->trimestre }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Corte: {{ $periodoActivo->fecha_corte ? \Carbon\Carbon::parse($periodoActivo->fecha_corte)->format('d/m/Y') : 'Sin corte' }} · {{ number_format($periodoActivo->total_filas) }} filas</p>
                @else
                    <p class="mt-2 text-sm text-amber-600">Sin periodo activo</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100">📤 Subir archivo CSV — Tiendas Regulares</h3>
            <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">135 columnas</span>
        </div>
        <form action="{{ route('imports.upload') }}" method="POST" enctype="multipart/form-data" id="upload-form"
            class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Año</label>
                    <input type="number" name="anio" value="{{ old('anio', $currentYear) }}" min="2020" max="2100" required class="input-filter">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Trimestre</label>
                    <select name="trimestre" required class="input-filter">
                        @foreach($trimestres as $trimestre)
                            <option value="{{ $trimestre }}" {{ old('trimestre', 'T1') === $trimestre ? 'selected' : '' }}>{{ $trimestre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Fecha de corte</label>
                    <input type="date" name="fecha_corte" value="{{ old('fecha_corte', '2026-04-01') }}" class="input-filter">
                </div>
            </div>

            <label class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                <input type="checkbox" name="reemplazar_periodo" value="1" class="mt-0.5" {{ old('reemplazar_periodo') ? 'checked' : '' }}>
                <span>Reemplazar periodo si ya existe. Esto elimina los registros actuales de ese trimestre y carga el nuevo archivo; otros periodos no se afectan.</span>
            </label>

            <label for="csv_file"
                class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-800/50 hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/20 transition">
                <div class="text-center">
                    <div class="text-3xl mb-2">📄</div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Haz clic para seleccionar un archivo CSV
                    </p>
                    <p class="text-xs text-gray-400 mt-1">o arrastra y suelta aquí</p>
                </div>
                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required class="hidden"
                    onchange="document.getElementById('submit-btn').disabled = false; document.getElementById('file-name').textContent = this.files[0]?.name;">
            </label>

            <p id="file-name" class="text-xs text-gray-500 text-center"></p>

            @error('csv_file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror

            <p class="text-xs text-gray-400">Máx. 50 MB. El archivo se sanitiza, divide en chunks y encola automáticamente.
            </p>

            <button id="submit-btn" type="submit" disabled
                class="btn-upload">
                ⬆ Subir e Importar
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100">📤 Subir Excel — Salud Casa por Casa</h3>
            <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">18 columnas</span>
        </div>
        <form action="{{ route('imports.upload-casa-x-casa') }}" method="POST" enctype="multipart/form-data" id="cxc-upload-form"
            class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Año</label>
                    <input type="number" name="anio" value="{{ old('anio', $currentYear) }}" min="2020" max="2100" required class="input-filter">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Trimestre</label>
                    <select name="trimestre" required class="input-filter">
                        @foreach($trimestres as $trimestre)
                            <option value="{{ $trimestre }}" {{ old('trimestre', 'T1') === $trimestre ? 'selected' : '' }}>{{ $trimestre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Fecha de corte</label>
                    <input type="date" name="fecha_corte" value="{{ old('fecha_corte', '2026-04-01') }}" class="input-filter">
                </div>
            </div>

            <label class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                <input type="checkbox" name="reemplazar_periodo" value="1" class="mt-0.5" {{ old('reemplazar_periodo') ? 'checked' : '' }}>
                <span>Reemplazar periodo si ya existe. Esto elimina los registros actuales de ese trimestre y carga el nuevo archivo; otros periodos no se afectan.</span>
            </label>

            <label for="xlsx_file"
                class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-green-300 dark:border-green-600 rounded-xl cursor-pointer bg-green-50 dark:bg-green-900/20 hover:border-green-500 hover:bg-green-100 dark:hover:bg-green-900/40 transition">
                <div class="text-center">
                    <div class="text-3xl mb-2">📊</div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Haz clic para seleccionar archivo Excel
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Directorio Nacional Tiendas Salud Casa por Casa</p>
                </div>
                <input type="file" name="xlsx_file" id="xlsx_file" accept=".xlsx,.xls" required class="hidden"
                    onchange="document.getElementById('cxc-submit-btn').disabled = false; document.getElementById('cxc-file-name').textContent = this.files[0]?.name;">
            </label>

            <p id="cxc-file-name" class="text-xs text-gray-500 text-center"></p>

            @error('xlsx_file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror

            <p class="text-xs text-gray-400">Máx. 50 MB. El archivo se importa directamente a <code class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">tiendas_casa_x_casa</code> vía upsert.</p>

            <button id="cxc-submit-btn" type="submit" disabled
                class="btn-upload">
                ⬆ Subir e Importar
            </button>
        </form>
    </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100">📂 Archivos subidos</h3>
            <span class="text-xs text-gray-400">{{ count($archivos) }} archivos</span>
        </div>
        @if(count($archivos) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 text-xs uppercase">
                            <th class="py-2 pr-3">Archivo</th>
                            <th class="py-2 pr-3">Tamaño</th>
                            <th class="py-2 pr-3">Modificado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($archivos as $a)
                            <tr class="border-b border-gray-100 dark:border-gray-700/50 text-gray-700 dark:text-gray-300">
                                <td class="py-2 pr-3 font-mono text-xs">{{ $a['name'] }}</td>
                                <td class="py-2 pr-3 text-xs">{{ number_format($a['size'] / 1024, 1) }} KB</td>
                                <td class="py-2 pr-3 text-xs">
                                    {{ \Carbon\Carbon::createFromTimestamp($a['modified'])->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">No hay archivos subidos aún.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $chunkCount }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">🧩 Chunks pendientes</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="text-lg font-bold text-gray-800 dark:text-gray-100">
                @if($stagingCount !== false)
                    {{ number_format($stagingCount) }}
                @else
                    —
                @endif
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">🗄️ Filas en staging</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ number_format($cxcCount) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">🏪 Tiendas CxC</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <div class="text-lg font-bold text-gray-800 dark:text-gray-100">
                <a href="{{ route('imports.index') }}" class="text-blue-600 hover:underline">↻</a>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">🔄 Recargar</div>
        </div>
    </div>

</div>
@endsection
