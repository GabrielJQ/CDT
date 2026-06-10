@extends('layouts.app', ['pageTitle' => 'Carga Masiva'])

@section('title', 'Carga Masiva — CDT')

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100">📤 Subir archivo CSV</h3>
        </div>
        <form action="{{ route('imports.upload') }}" method="POST" enctype="multipart/form-data" id="upload-form"
            class="space-y-4">
            @csrf

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
                class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold px-5 py-3 rounded-lg text-sm shadow transition">
                ⬆ Subir e Importar
            </button>
        </form>
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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
            <div class="text-lg font-bold text-gray-800 dark:text-gray-100">
                <a href="{{ route('imports.index') }}" class="text-blue-600 hover:underline">↻</a>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">🔄 Recargar</div>
        </div>
    </div>

@endsection