@extends('layouts.app', ['pageTitle' => 'Inicio'])

@section('title', 'CDT — Panel de Monitoreo')

@section('content')
<div class="min-h-[calc(100vh-10rem)] flex flex-col items-center justify-center">
    <div class="text-center mb-10">
        <h1 class="text-3xl lg:text-4xl font-bold text-gray-800 dark:text-gray-100 mb-2">
            Panel de Monitoreo
        </h1>
        <p class="text-sm lg:text-base text-gray-500 dark:text-gray-400">
            Selecciona una región para ver sus indicadores
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 w-full max-w-6xl">
        @foreach($regionales as $reg)
            @php
                $regionCookie = $reg['clave'];
                $isActive = request()->cookie('region_filter', '') === $regionCookie;
            @endphp
            <form action="{{ url('/set-region') }}" method="POST">
                @csrf
                <input type="hidden" name="region" value="{{ $reg['clave'] }}">
                <input type="hidden" name="uo" value="">
                <input type="hidden" name="redirect" value="{{ route('dashboard') }}">
                <button type="submit"
                        class="w-full text-left bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg border-2 transition-all duration-200
                               {{ $isActive ? 'border-green-500 ring-2 ring-green-300' : 'border-transparent hover:border-green-400 hover:-translate-y-1' }}">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <span class="text-3xl">📌</span>
                            <span class="text-xs font-bold bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 px-2 py-1 rounded-full">
                                {{ $reg['clave'] }}
                            </span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">{{ $reg['nombre'] }}</h2>
                        <div class="grid grid-cols-3 gap-1.5 mb-3">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-2 text-center border-l-2 border-blue-500">
                                <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($reg['almacenes']) }}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Almacenes</p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-2 text-center border-l-2 border-green-500">
                                <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ count($reg['uos']) }}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Unidades</p>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-2 text-center border-l-2 border-purple-500">
                                <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($reg['total']) }}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Tiendas</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($reg['uos'] as $uo)
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                                    {{ $uo['nombre'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </button>
            </form>
        @endforeach
    </div>

    @if(count($regionales) === 0)
        <div class="text-center py-20">
            <p class="text-gray-400 text-lg">No hay datos disponibles. <a href="{{ url('/carga-masiva') }}" class="text-blue-600 hover:underline">Sube un archivo CSV</a> para comenzar.</p>
        </div>
    @endif
</div>
@endsection
