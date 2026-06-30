@extends('layouts.app', ['pageTitle' => 'Directorio de Tiendas'])

@section('title', 'Directorio — Dashboard CDT')

@section('content')
    @isset($error)
        <x-alert type="error">{{ $error }}</x-alert>
    @endisset

    <livewire:directorio-table />
@endsection
