<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard CDT')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    </style>
    @stack('head')
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        @php
            $currentPath = request()->path();
            $navItems = [
                '/' => ['label' => 'Dashboard', 'icon' => '📊'],
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
        @endphp

        {{-- Overlay for mobile --}}
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

        {{-- Sidebar --}}
        <aside id="sidebar" class="flex-shrink-0 bg-[#166534] text-white flex flex-col z-30 overflow-hidden">
            <div class="border-b border-green-700 flex items-center justify-between flex-shrink-0" style="height:3.5rem">
                <div class="px-4 flex items-center gap-2 overflow-hidden">
                    <span class="text-xl font-bold tracking-tight flex-shrink-0">CDT</span>
                    <span class="nav-label text-xs text-green-300 whitespace-nowrap">Panel de Monitoreo</span>
                </div>
                <button onclick="toggleSidebar()" class="lg:hidden text-green-200 hover:text-white text-xl leading-none px-2">&times;</button>
            </div>
            <nav class="flex-1 p-2 space-y-1 overflow-y-auto overflow-x-hidden">
                @foreach($navItems as $path => $item)
                    @php
                        $isActive = $currentPath === $path || ($path !== '/' && str_starts_with($currentPath, $path));
                    @endphp
                    <a href="{{ url($path === '/' ? '' : $path) }}"
                       title="{{ $item['label'] }}"
                       class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                              {{ $isActive ? 'bg-green-700 text-white' : 'text-green-100 hover:bg-green-700/50' }}">
                        <span class="text-lg flex-shrink-0 w-6 text-center">{{ $item['icon'] }}</span>
                        <span class="nav-label truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                {{-- Dropdown: Presencia Tiendas --}}
                <div>
                    <button type="button" id="presencia-toggle"
                            title="Presencia Tiendas"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition w-full text-left
                                   {{ $presenciaActive ? 'bg-green-700 text-white' : 'text-green-100 hover:bg-green-700/50' }}">
                        <span class="text-lg flex-shrink-0 w-6 text-center">🏪</span>
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
                               class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                                      {{ $isChildActive ? 'bg-green-700 text-white' : 'text-green-100 hover:bg-green-700/50' }}">
                                <span class="text-base flex-shrink-0 w-6 text-center">{{ $child['icon'] }}</span>
                                <span class="nav-label truncate">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
            <div class="border-t border-green-700 text-xs text-green-300 p-3 sidebar-extra flex-shrink-0">
                @php $layoutUpdated = cache()->get('dashboard_updated_at', '—'); @endphp
                <p>Actualizado: <span class="font-mono text-white">{{ $layoutUpdated }}</span></p>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0">
            {{-- Top bar --}}
            <div class="bg-white border-b border-gray-200 px-4 lg:px-6 py-2 lg:py-3 flex flex-wrap items-center gap-2 lg:gap-3">
                <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-900 text-xl leading-none pr-2">☰</button>
                <h2 class="text-base lg:text-lg font-semibold text-gray-800 truncate flex-1">{{ $pageTitle ?? 'Dashboard' }}</h2>
                <div class="flex items-center gap-2 w-full lg:w-auto">
                    @php $currentRegion = request()->cookie('region_filter', ''); @endphp
                    <form action="{{ url('/set-region') }}" method="POST" id="region-form" class="flex-1 lg:flex-none">
                        @csrf
                        <select name="region" onchange="document.getElementById('region-form').submit()"
                                class="w-full lg:w-auto border border-gray-300 rounded-lg px-2 lg:px-3 py-1.5 lg:py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                            <option value="">🌎 Todas las regiones</option>
                            <option value="U.O. OAXACA" {{ $currentRegion === 'U.O. OAXACA' ? 'selected' : '' }}>📍 Oaxaca</option>
                            <option value="U.O. ISTMO" {{ $currentRegion === 'U.O. ISTMO' ? 'selected' : '' }}>📍 Istmo</option>
                            <option value="U.O. MIXTECA" {{ $currentRegion === 'U.O. MIXTECA' ? 'selected' : '' }}>📍 Mixteca</option>
                        </select>
                    </form>
                    <form action="{{ url('/refresh') }}" method="POST" class="flex-none">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 lg:px-4 py-1.5 lg:py-2 rounded-lg text-xs lg:text-sm shadow transition whitespace-nowrap">
                            ↻ Refrescar
                        </button>
                    </form>
                </div>
            </div>

            {{-- Alerts --}}
            <div class="px-4 lg:px-6 pt-3 lg:pt-4">
                @session('success')
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">{{ $value }}</div>
                @endsession
                @session('error')
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">{{ $value }}</div>
                @endsession
                @isset($error)
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">{{ $error }}</div>
                @endisset
            </div>

            {{-- Content --}}
            <div class="p-3 lg:p-6 flex-1">
                @yield('content')
            </div>
        </main>
    </div>
    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('expanded');
            if (window.innerWidth < 1024) {
                overlay.classList.toggle('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('presencia-toggle');
            var submenu = document.getElementById('presencia-submenu');
            var arrow = document.getElementById('presencia-arrow');
            if (toggle && submenu && arrow) {
                toggle.addEventListener('click', function () {
                    submenu.classList.toggle('hidden');
                    arrow.classList.toggle('-rotate-90');
                });
            }

            document.querySelectorAll('.nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 1024) {
                        var sidebar = document.getElementById('sidebar');
                        var overlay = document.getElementById('sidebar-overlay');
                        sidebar.classList.remove('expanded');
                        overlay.classList.add('hidden');
                    }
                });
            });
        });
    </script>
    @stack('footer')
</body>
</html>
