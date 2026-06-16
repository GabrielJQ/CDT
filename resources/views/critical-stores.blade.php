@extends('layouts.app', ['pageTitle' => 'Información de Tiendas'])

@section('title', 'Información de Tiendas — Dashboard CDT')

@section('content')
    <livewire:critical-stores-table />
@endsection
