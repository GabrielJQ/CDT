<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard CDT')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('head')
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        @php
            $currentPath = request()->path();
            $navItems = [
                '/' => ['label' => 'Dashboard', 'icon' => '📊'],
                'conectividad' => ['label' => 'Conectividad', 'icon' => '📡'],
                'tiendas-criticas' => ['label' => 'Tiendas Críticas', 'icon' => '⚠️'],
                'mapa' => ['label' => 'Mapa', 'icon' => '🗺️'],
            ];
        @endphp

        <aside class="w-64 bg-[#166534] text-white flex-shrink-0 flex flex-col">
            <div class="p-5 border-b border-green-700">
                <h1 class="text-xl font-bold tracking-tight">CDT Dashboard</h1>
                <p class="text-xs text-green-300 mt-0.5">Panel de Monitoreo</p>
            </div>
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
                @foreach($navItems as $path => $item)
                    @php
                        $isActive = $currentPath === $path || ($path !== '/' && str_starts_with($currentPath, $path));
                    @endphp
                    <a href="/{{ $path === '/' ? '' : $path }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                              {{ $isActive ? 'bg-green-700 text-white' : 'text-green-100 hover:bg-green-700/50' }}">
                        <span class="text-lg">{{ $item['icon'] }}</span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            <div class="p-4 border-t border-green-700 text-xs text-green-300">
                @php $layoutUpdated = cache()->get('dashboard_updated_at', '—'); @endphp
                <p>Actualizado: <span class="font-mono text-white">{{ $layoutUpdated }}</span></p>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto">
            <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                <form action="/refresh" method="POST">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm shadow transition">
                        ↻ Refrescar datos
                    </button>
                </form>
            </div>

            <div class="px-6 pt-4">
                @session('success')
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ $value }}</div>
                @endsession
                @session('error')
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ $value }}</div>
                @endsession
                @isset($error)
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ $error }}</div>
                @endisset
            </div>

            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>
    @stack('footer')
</body>
</html>
