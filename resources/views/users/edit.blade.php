@extends('layouts.app', ['pageTitle' => 'Editar usuario'])

@section('title', 'Editar usuario — CDT')

@section('content')
<div class="page-shell max-w-4xl">
    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Administración</p>
                <h1 class="page-heading">Editar usuario</h1>
                <p class="page-subheading">Actualiza datos, rol y alcance operativo.</p>
            </div>
            <a href="{{ route('usuarios.index') }}" class="btn-secondary">Volver</a>
        </div>
    </section>

    @include('users.form', ['action' => route('usuarios.update', $user), 'method' => 'PUT'])
</div>
@endsection
