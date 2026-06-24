@extends('layouts.app', ['pageTitle' => 'Mi Perfil'])

@section('title', 'Mi Perfil — CDT')

@section('content')
<div class="page-shell max-w-3xl">
    <div class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Configuracion de cuenta</p>
                <h1 class="page-heading">Mi Perfil</h1>
            </div>
            <span class="status-pill {{ $user->isAdmin() ? 'status-critical' : ($user->isNacional() ? 'status-warning' : 'status-ok') }}">
                {{ ucfirst($user->role) }}
            </span>
        </div>
    </div>

    {{-- Info personal --}}
    <div class="institutional-card p-5 lg:p-6">
        <h2 class="institutional-title mb-4">Informacion personal</h2>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div>
                <dt class="kpi-label">Nombre</dt>
                <dd class="kpi-value text-base">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="kpi-label">Correo electronico</dt>
                <dd class="kpi-value text-base">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="kpi-label">Rol</dt>
                <dd class="kpi-value text-base capitalize">{{ $user->role }}</dd>
            </div>
            <div>
                <dt class="kpi-label">Miembro desde</dt>
                <dd class="kpi-value text-base">{{ $user->created_at->format('d/m/Y') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Alcance --}}
    <div class="institutional-card p-5 lg:p-6">
        <h2 class="institutional-title mb-4">Alcance operativo</h2>
        @if($user->hasGlobalAccess())
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Acceso a <strong>todas las regiones</strong> y <strong>todas las unidades operativas</strong> del pais.
            </p>
        @else
            <dl class="space-y-3">
                <div>
                    <dt class="kpi-label">Region</dt>
                    <dd class="font-bold text-gray-900 dark:text-gray-100">{{ $user->region?->nombre ?? '—' }}</dd>
                </div>
                @if($user->isRegional())
                    <div>
                        <dt class="kpi-label">Unidades operativas disponibles</dt>
                        <dd>
                            <ul class="mt-1 flex flex-wrap gap-1.5">
                                @forelse($unidades as $u)
                                    <li class="rounded-lg border border-[#988256]/30 bg-[#988256]/10 px-2.5 py-1 text-xs font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $u->nombre }}
                                    </li>
                                @empty
                                    <span class="text-sm text-gray-500">—</span>
                                @endforelse
                            </ul>
                        </dd>
                    </div>
                @elseif($user->isUnidad())
                    <div>
                        <dt class="kpi-label">Unidad operativa</dt>
                        <dd class="font-bold text-gray-900 dark:text-gray-100">{{ $user->unidadOperativa?->nombre ?? '—' }}</dd>
                    </div>
                @endif
            </dl>
        @endif
    </div>

    {{-- Permisos --}}
    <div class="institutional-card p-5 lg:p-6">
        <h2 class="institutional-title mb-4">Permisos</h2>
        <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
            {{ $roleDescriptions[$user->role] ?? '' }}
        </p>
        <ul class="mt-3 space-y-1.5 text-sm text-gray-600 dark:text-gray-300">
            <li class="flex items-center gap-2">
                <span class="text-green-600">✓</span> Dashboard con KPIs
            </li>
            <li class="flex items-center gap-2">
                <span class="text-green-600">✓</span> Directorio, Mapa, Conectividad, Aperturas, Auditoria
            </li>
            <li class="flex items-center gap-2">
                <span class="text-green-600">✓</span> Tiendas de Salud Casa x Casa
            </li>
            @if($user->canImportGlobal())
                <li class="flex items-center gap-2">
                    <span class="text-green-600">✓</span> Importacion de datos (carga masiva)
                </li>
            @endif
            @if($user->canManageUsers())
                <li class="flex items-center gap-2">
                    <span class="text-green-600">✓</span> Administracion de usuarios
                </li>
            @endif
        </ul>
    </div>

    {{-- Editar nombre --}}
    <div id="section-nombre" class="institutional-card p-5 lg:p-6">
        <h2 class="institutional-title mb-4">Editar nombre</h2>
        <form action="{{ url('/perfil') }}" method="POST" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            @method('PUT')
            <div class="flex-1">
                <label for="name" class="kpi-label mb-1 block">Nombre completo</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                       class="input-institutional w-full">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-gold shrink-0 px-5 py-2.5 text-xs lg:text-sm">Guardar</button>
        </form>
    </div>

    {{-- Cambiar contraseña --}}
    <div id="section-password" class="institutional-card p-5 lg:p-6">
        <h2 class="institutional-title mb-4">Cambiar contraseña</h2>
        <form action="{{ url('/perfil/password') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label for="current_password" class="kpi-label mb-1 block">Contraseña actual</label>
                <input type="password" name="current_password" id="current_password"
                       class="input-institutional w-full">
                @error('current_password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="password" class="kpi-label mb-1 block">Nueva contraseña</label>
                    <input type="password" name="password" id="password"
                           class="input-institutional w-full">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="kpi-label mb-1 block">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="input-institutional w-full">
                </div>
            </div>
            <button type="submit" class="btn-gold px-5 py-2.5 text-xs lg:text-sm">Actualizar contraseña</button>
        </form>
    </div>
</div>
@endsection

@push('footer')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function scrollTo(el) {
        if (!el) return;
        setTimeout(function () {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            var input = el.querySelector('input');
            if (input) input.focus();
        }, 100);
    }

    var hash = window.location.hash;
    if (hash) {
        var target = document.querySelector(hash);
        if (target) scrollTo(target);
    }

    document.querySelectorAll('[id^="section-"]').forEach(function (section) {
        if (section.querySelector('.text-red-600')) {
            scrollTo(section);
        }
    });
});
</script>
@endpush
