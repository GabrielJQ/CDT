@extends('layouts.app', ['pageTitle' => 'Conectividad'])

@section('title', 'Conectividad — Dashboard CDT')

@push('head')
<style>
 #conn-table td, #conn-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
 #conn-table th { position: sticky; top: 0; z-index: 1; }
</style>
@endpush

@section('content')
  @isset($error)
  <x-alert type="error">{{ $error }}</x-alert>
  @endisset

  <livewire:connectivity-table />
@endsection
