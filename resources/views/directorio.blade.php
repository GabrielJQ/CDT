@extends('layouts.app', ['pageTitle' => 'Directorio de Tiendas'])

@section('title', 'Directorio — Dashboard CDT')

@section('content')
    @isset($error)
        <div class="bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">{{ $error }}</div>
    @endisset

    <livewire:directorio-table />
@endsection
