@extends('layouts.app', ['pageTitle' => 'Carga Masiva'])

@section('title', 'Carga Masiva — CDT')

@section('content')
<div class="page-shell">
    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Importaciones</p>
                <h1 class="page-heading">Carga masiva</h1>
                <p class="page-subheading">Sube archivos de Tiendas Bienestar y Tiendas de Salud. Solo elige el archivo correcto y subelo.</p>
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
                <span>Reemplazar datos existentes del periodo.</span>
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

            <p class="text-xs text-gray-400">Máx. 50 MB. La importación se procesa automáticamente en segundo plano.
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
                <span>Reemplazar datos existentes del periodo.</span>
            </label>

            <label for="xlsx_file"
                class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-green-300 dark:border-green-600 rounded-xl cursor-pointer bg-green-50 dark:bg-green-900/20 hover:border-green-500 hover:bg-green-100 dark:hover:bg-green-900/40 transition">
                <div class="text-center">
                    <div class="text-3xl mb-2">📊</div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Haz clic para seleccionar archivo Excel
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Archivo de Tiendas de Salud Casa por Casa</p>
                </div>
                <input type="file" name="xlsx_file" id="xlsx_file" accept=".xlsx,.xls" required class="hidden"
                    onchange="document.getElementById('cxc-submit-btn').disabled = false; document.getElementById('cxc-file-name').textContent = this.files[0]?.name;">
            </label>

            <p id="cxc-file-name" class="text-xs text-gray-500 text-center"></p>

            @error('xlsx_file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror

            <p class="text-xs text-gray-400">Máx. 50 MB. La importación se procesa automáticamente en segundo plano.</p>

            <button id="cxc-submit-btn" type="submit" disabled
                class="btn-upload">
                ⬆ Subir e Importar
            </button>
        </form>
    </div>
    </div>


</div>
@endsection
