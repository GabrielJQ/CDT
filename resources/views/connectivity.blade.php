@extends('layouts.app', ['pageTitle' => 'Conectividad'])

@section('title', 'Conectividad — Dashboard CDT')

@push('head')
<style>
 #conn-table td, #conn-table th { padding: 0.4rem 0.6rem; font-size: 0.8rem; }
 #conn-table th { position: sticky; top: 0; z-index: 1; }
 .page-btn { min-width: 2rem; text-align: center; }
 .page-btn.active { background: #166534; color: white; border-color: #166534; }
 .col-toggle { user-select: none; cursor: pointer; }
 .col-toggle input { accent-color: #166534; }
 .dark .page-btn.active { background: #14532d; border-color: #14532d; }
 .dark .col-toggle input { accent-color: #4ade80; }
</style>
@endpush

@section('content')
  @isset($error)
  <div class="bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">{{ $error }}</div>
  @endisset

  <livewire:connectivity-table />
@endsection
