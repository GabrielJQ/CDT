@extends('layouts.app', ['pageTitle' => 'Usuarios'])

@section('title', 'Usuarios — CDT')

@section('content')
<div class="page-shell">
    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Administración</p>
                <h1 class="page-heading">Gestión de usuarios</h1>
                <p class="page-subheading">Administra accesos, roles y alcance operativo por región o unidad operativa.</p>
            </div>
            <a href="{{ route('usuarios.create') }}" class="btn-gold">Nuevo usuario</a>
        </div>
    </section>

    <div class="table-shell">
        <table class="table-institutional min-w-full text-sm">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left">Usuario</th>
                    <th class="px-4 py-3 text-left">Rol</th>
                    <th class="px-4 py-3 text-left">Alcance</th>
                    <th class="px-4 py-3 text-left">Estatus</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="px-4 py-3">
                            <p class="font-extrabold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="status-pill bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $user->role?->value }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            @if($user->hasGlobalAccess())
                                Todas las regiones
                            @elseif($user->isRegional())
                                {{ $user->region?->nombre ?? 'Sin región' }}
                            @else
                                {{ $user->unidadOperativa?->nombre ?? 'Sin UO' }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="status-pill {{ $user->is_active ? 'status-ok' : 'status-critical' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('usuarios.edit', $user) }}" class="btn-secondary px-3 py-1.5 text-xs">Editar</a>
                                <form action="{{ route('usuarios.toggle-active', $user) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-secondary px-3 py-1.5 text-xs">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                                <form action="{{ route('usuarios.reset-password', $user) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-guinda px-3 py-1.5 text-xs">Reset</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
