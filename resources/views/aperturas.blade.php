@extends('layouts.app', ['pageTitle' => 'Apertura de Tiendas'])

@section('title', 'Aperturas — Dashboard CDT')

@push('head')
<style>
 #aper-table td, #aper-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
 #aper-table th { position: sticky; top: 0; z-index: 1; }
 .page-btn { min-width: 2rem; text-align: center; }
 .page-btn.active { background: #166534; color: white; border-color: #166534; }
 .col-toggle { user-select: none; cursor: pointer; }
 .col-toggle input { accent-color: #166534; }
 .badge { display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }
 .dark .page-btn.active { background: #14532d; border-color: #14532d; }
 .dark .col-toggle input { accent-color: #4ade80; }
</style>
@endpush

@section('content')
 @isset($error)
 <div class="bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">{{ $error }}</div>
 @endisset

 <livewire:aperturas-table />
@endsection
