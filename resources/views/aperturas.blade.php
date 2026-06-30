@extends('layouts.app', ['pageTitle' => 'Apertura de Tiendas'])

@section('title', 'Aperturas — Dashboard CDT')

@push('head')
<style>
 #aper-table td, #aper-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
 #aper-table th { position: sticky; top: 0; z-index: 1; }
</style>
@endpush

@section('content')
 @isset($error)
 <x-alert type="error">{{ $error }}</x-alert>
 @endisset

 <livewire:aperturas-table />
@endsection
