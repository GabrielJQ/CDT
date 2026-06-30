@extends('layouts.app', ['pageTitle' => 'Dashboard'])

@section('title', 'Dashboard — CDT')

@section('content')
    <livewire:dashboard-content />
@endsection

@push('footer')
@vite('resources/js/dashboard.js')
@endpush
