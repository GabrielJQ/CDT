@extends('layouts.app', ['pageTitle' => 'Nuevo usuario'])

@section('title', 'Nuevo usuario — CDT')

@section('content')
<div class="page-shell max-w-4xl">
    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Administración</p>
                <h1 class="page-heading">Nuevo usuario</h1>
                <p class="page-subheading">Define rol y alcance operativo desde el alta.</p>
            </div>
            <a href="{{ route('usuarios.index') }}" class="btn-secondary">Volver</a>
        </div>
    </section>

    @include('users.form', ['action' => route('usuarios.store'), 'method' => 'POST', 'user' => null])
</div>
@endsection
