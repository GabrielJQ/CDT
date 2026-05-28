<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Operativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Dashboard Operativo</h1>
                @if($updatedAt)
                    <p class="text-sm text-gray-400 mt-1">Última actualización: {{ $updatedAt }}</p>
                @endif
            </div>
            <form action="/refresh" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow text-sm transition">
                    Refrescar datos
                </button>
            </form>
        </div>

        @session('success')
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">{{ $value }}</div>
        @endsession

        @session('error')
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ $value }}</div>
        @endsession

        @isset($error)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ $error }}</div>
        @endisset

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 uppercase tracking-wide">Total P&eacute;rdidas</p>
                <p class="text-3xl font-bold text-gray-800">${{ number_format($totalLosses, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
                <p class="text-sm text-gray-500 uppercase tracking-wide">Tiendas con Faltas</p>
                <p class="text-3xl font-bold text-gray-800">{{ $storesWithShortage }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
                <p class="text-sm text-gray-500 uppercase tracking-wide">Total Tiendas Registradas</p>
                <p class="text-3xl font-bold text-gray-800">{{ $totalStores }}</p>
            </div>
        </div>

        @if(count($stores) > 0)
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tienda</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ciudad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">P&eacute;rdidas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faltas Personal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estatus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($stores as $store)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $store['Tienda'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $store['Ciudad'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">${{ number_format($store['Perdidas_Monetarias'], 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $store['Faltas_Personal'] }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $estatus = $store['Estatus'];
                                        $badgeClass = match ($estatus) {
                                            'Ok' => 'bg-green-100 text-green-800',
                                            'Alerta' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                        {{ $estatus }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-xl shadow p-6 text-center text-gray-500">
                No hay datos de tiendas para mostrar.
            </div>
        @endif
    </div>
</body>
</html>
