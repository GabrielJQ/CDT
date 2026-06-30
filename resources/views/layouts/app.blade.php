<!DOCTYPE html>
<html lang="es" class="{{ request()->cookie('tema', '') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard CDT')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        #sidebar { width: 4rem; transition: width .2s ease; }
        #sidebar.expanded { width: 16rem; }
        @media (max-width: 1023px) {
            #sidebar { position: fixed; inset: 0; left: 0; z-index: 30; transform: translateX(-100%); width: 16rem; }
            #sidebar.expanded { transform: translateX(0); }
        }
        .nav-label { overflow: hidden; white-space: nowrap; }
        #sidebar:not(.expanded) .nav-label,
        #sidebar:not(.expanded) .sidebar-extra { display: none; }
        @media (max-width: 1023px) {
            #sidebar:not(.expanded) .nav-label,
            #sidebar:not(.expanded) .sidebar-extra { display: block; }
        }
        .dark #conn-table .cell-empty,
        .dark #cs-table .cell-empty,
        .dark #dir-table .cell-empty,
        .dark #aper-table .cell-empty,
        .dark #audit-table .cell-empty { background: #374151; color: #6b7280; }
    </style>
    @stack('head')
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="h-screen flex overflow-hidden">
        @php
            $currentPath = request()->path();
            $navItems = [
                'dashboard' => ['label' => 'Dashboard', 'icon' => '📊'],
                'auditoria' => ['label' => 'Auditoría', 'icon' => '🔍'],
                'directorio' => ['label' => 'Directorio', 'icon' => '📋'],
            ];
            $presenciaChildren = [
                'informacion-tiendas' => ['label' => 'Información de Tiendas', 'icon' => '📋'],
                'mapa' => ['label' => 'Mapa', 'icon' => '🗺️'],
                'conectividad' => ['label' => 'Conectividad', 'icon' => '📡'],
                'aperturas' => ['label' => 'Aperturas', 'icon' => '🏗️'],
            ];
            $presenciaActive = false;
            foreach ($presenciaChildren as $childPath => $child) {
                if ($currentPath === $childPath || str_starts_with($currentPath, $childPath)) {
                    $presenciaActive = true;
                    break;
                }
            }
            $sectionLabel = match (true) {
                str_starts_with($currentPath, 'casa-x-casa') => 'Tiendas de Salud',
                str_starts_with($currentPath, 'usuarios') => 'Administración',
                str_starts_with($currentPath, 'carga-masiva') => 'Importaciones',
                $presenciaActive => 'Presencia Tiendas',
                str_starts_with($currentPath, 'auditoria') => 'Control Operativo',
                str_starts_with($currentPath, 'directorio') => 'Directorio',
                default => 'Monitoreo CDT',
            };
            $authUser = auth()->user();
        @endphp

        {{-- Overlay for mobile --}}
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

        {{-- Sidebar --}}
        <aside id="sidebar" class="institutional-sidebar expanded shrink-0 text-white flex flex-col z-30 overflow-hidden">
            <div class="institutional-sidebar-brand flex items-center justify-between gap-2 shrink-0" style="height:3.75rem">
                <a href="{{ url('/') }}" class="flex min-w-0 flex-1 items-center gap-2 overflow-hidden rounded-lg transition hover:opacity-90" title="Ir al inicio">
                    <span class="text-xl font-extrabold tracking-tight shrink-0 leading-none">CDT</span>
                    <span class="nav-label min-w-0 text-[0.64rem] font-semibold uppercase leading-tight tracking-[0.18em] text-[#988256] whitespace-normal break-words">Panel de Monitoreo</span>
                </a>
                <button onclick="toggleSidebar()" class="lg:hidden text-white/70 hover:text-white text-xl leading-none px-2">&times;</button>
            </div>
            <nav class="flex-1 p-2 space-y-1 overflow-y-auto overflow-x-hidden">
                <x-nav-link href="{{ route('perfil') }}" title="Mi Perfil" icon="👤" :active="str_starts_with($currentPath, 'perfil')">Mi Perfil</x-nav-link>

                <x-nav-link href="{{ url('carga-masiva') }}" title="Carga Masiva" icon="📥" :active="str_starts_with($currentPath, 'carga-masiva')">Carga Masiva</x-nav-link>

                @if($authUser?->canManageUsers())
                    <x-nav-link href="{{ route('usuarios.index') }}" title="Usuarios" icon="👤" :active="str_starts_with($currentPath, 'usuarios')">Usuarios</x-nav-link>
                @endif

                @foreach($navItems as $path => $item)
                    @php
                        $isActive = $currentPath === $path || ($path !== '/' && str_starts_with($currentPath, $path));
                    @endphp
                    <x-nav-link href="{{ url($path === '/' ? '' : $path) }}" title="{{ $item['label'] }}" icon="{{ $item['icon'] }}" :active="$isActive">{{ $item['label'] }}</x-nav-link>
                @endforeach

                {{-- Dropdown: Presencia Tiendas --}}
                <div>
                    <button type="button" id="presencia-toggle"
                            title="Presencia Tiendas"
                            class="institutional-nav-link w-full text-left {{ $presenciaActive ? 'institutional-nav-link-active' : '' }}">
                        <span class="text-lg shrink-0 w-6 text-center">🏪</span>
                        <span class="nav-label flex-1 truncate">Presencia Tiendas</span>
                        <span id="presencia-arrow" class="sidebar-extra text-xs transition-transform {{ $presenciaActive ? 'rotate-0' : '-rotate-90' }}">▼</span>
                    </button>
                    <div id="presencia-submenu" class="ml-2 mt-1 space-y-1 {{ $presenciaActive ? '' : 'hidden' }}">
                        @foreach($presenciaChildren as $childPath => $child)
                            @php
                                $isChildActive = $currentPath === $childPath || str_starts_with($currentPath, $childPath);
                            @endphp
                            <a href="{{ url($childPath) }}"
                               title="{{ $child['label'] }}"
                               class="nav-link institutional-subnav-link {{ $isChildActive ? 'institutional-subnav-link-active' : '' }}">
                                <span class="text-base shrink-0 w-6 text-center">{{ $child['icon'] }}</span>
                                <span class="nav-label truncate">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Dropdown: Tiendas de Salud CxC --}}
                @php
                    $cxcPrefix = 'casa-x-casa';
                    $cxcActive = str_starts_with($currentPath, $cxcPrefix);
                    $cxcChildren = [
                        '' => ['label' => 'Dashboard', 'icon' => '📊'],
                        'directorio' => ['label' => 'Directorio', 'icon' => '📋'],
                        'mapa' => ['label' => 'Mapa', 'icon' => '🗺️'],
                    ];
                @endphp
                <div>
                    <button type="button" id="cxc-toggle"
                            title="Tiendas de Salud"
                            class="institutional-nav-link w-full text-left {{ $cxcActive ? 'institutional-nav-link-active' : '' }}">
                        <span class="text-lg shrink-0 w-6 text-center">🏥</span>
                        <span class="nav-label flex-1 truncate">Tiendas de Salud</span>
                        <span id="cxc-arrow" class="sidebar-extra text-xs transition-transform {{ $cxcActive ? 'rotate-0' : '-rotate-90' }}">▼</span>
                    </button>
                    <div id="cxc-submenu" class="ml-2 mt-1 space-y-1 {{ $cxcActive ? '' : 'hidden' }}">
                        @foreach($cxcChildren as $childPath => $child)
                            @php
                                $fullPath = $childPath === '' ? $cxcPrefix : $cxcPrefix.'/'.$childPath;
                                $isChildActive = $currentPath === $fullPath || ($childPath !== '' && str_starts_with($currentPath, $fullPath));
                            @endphp
                            <a href="{{ url($fullPath) }}"
                               title="{{ $child['label'] }}"
                               class="nav-link institutional-subnav-link {{ $isChildActive ? 'institutional-subnav-link-active' : '' }}">
                                <span class="text-base shrink-0 w-6 text-center">{{ $child['icon'] }}</span>
                                <span class="nav-label truncate">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
            <div class="border-t border-[#988256]/35 text-xs text-white/55 p-3 sidebar-extra shrink-0">
                @php $layoutUpdated = now()->toDateTimeString(); @endphp
                <p>Actualizado: <span class="font-mono text-white">{{ $layoutUpdated }}</span></p>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0">
            {{-- Top bar --}}
            <div class="institutional-topbar shrink-0 flex flex-wrap items-center gap-2 lg:gap-3">
                <button onclick="toggleSidebar()" class="rounded-lg px-2 py-1 text-xl leading-none text-white/80 transition hover:bg-white/10 hover:text-white">☰</button>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="hidden rounded-full border border-[#988256]/50 px-2 py-0.5 text-[0.65rem] font-extrabold uppercase tracking-[0.2em] text-[#d7c08a] sm:inline-flex">{{ $sectionLabel }}</span>
                        <h2 class="institutional-topbar-title">{{ $pageTitle ?? 'Dashboard' }}</h2>
                    </div>
                    <p class="hidden text-xs text-white/55 lg:block">Filtro global aplicado a todos los modulos operativos</p>
                </div>
                <div class="flex items-center gap-2 w-full lg:w-auto">
                    <form action="{{ url('/set-region') }}" method="POST" id="region-form" class="flex-1 lg:flex-none flex gap-1.5">
                        @csrf
                        <input type="hidden" name="redirect" value="{{ url()->current() }}">
                        @php
                            $isUnidad = $authUser?->isUnidad() ?? false;
                            $regionDisabled = !($authUser?->hasGlobalAccess() ?? false);
                            $uoDisabled = $isUnidad;
                        @endphp
                        <select name="region" id="region-select"
                                class="input-institutional w-full lg:w-auto text-xs lg:text-sm"
                                {{ $regionDisabled ? 'disabled' : '' }}>
                            <option value="">🌎 Todas</option>
                            @foreach($regionesData ?? [] as $reg)
                                <option value="{{ $reg['clave'] }}" {{ $currentRegionCookie === $reg['clave'] ? 'selected' : '' }}>
                                    {{ $reg['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                        <select name="uo" id="uo-select"
                                class="input-institutional w-full lg:w-auto text-xs lg:text-sm"
                                {{ $uoDisabled ? 'disabled' : '' }}>
                            <option value="">📍 Todas UO</option>
                            @foreach($regionesData ?? [] as $reg)
                                @foreach($reg['uos'] as $uo)
                                    <option value="{{ $uo['clave'] }}"
                                            data-region="{{ $reg['clave'] }}"
                                            {{ $currentUoCookie === $uo['clave'] ? 'selected' : '' }}>
                                        {{ $uo['nombre'] }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </form>
                    <form action="{{ url('/refresh') }}" method="POST" class="flex-none">
                        @csrf
                        <button type="submit" class="btn-gold px-3 py-1.5 text-xs lg:px-4 lg:py-2 lg:text-sm whitespace-nowrap">
                            ↻ Refrescar
                        </button>
                    </form>
                    <button id="tema-toggle" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg leading-none text-white/85 transition hover:bg-white/10 hover:text-white" title="Cambiar tema">🌙</button>
                    <div class="hidden min-w-0 flex-col text-right text-xs text-white/70 xl:flex">
                        <span class="truncate font-extrabold text-white">{{ $authUser?->name }}</span>
                        <span class="uppercase tracking-wide">{{ $authUser?->role?->value }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="flex-none">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-extrabold text-white/80 transition hover:bg-white/10 hover:text-white">Salir</button>
                    </form>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                {{-- Alerts --}}
                <div class="px-4 lg:px-6 pt-3 lg:pt-4">
                    @session('success')
                        <x-alert type="success">{{ $value }}</x-alert>
                    @endsession
                    @session('error')
                        <x-alert type="error">{{ $value }}</x-alert>
                    @endsession
                    @isset($error)
                        <x-alert type="error">{{ $error }}</x-alert>
                    @endisset
                </div>

                {{-- Content --}}
                <div class="p-3 lg:p-6">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
    @livewireScripts
    @stack('footer')
</body>
</html>
