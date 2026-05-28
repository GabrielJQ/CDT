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

        @if(count($numericColumns) > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                @foreach($numericColumns as $col => $total)
                    <div class="bg-white rounded-xl shadow p-6 border-l-4 {{ $loop->first ? 'border-blue-500' : ($loop->index % 2 === 0 ? 'border-yellow-500' : 'border-green-500') }}">
                        <p class="text-sm text-gray-500 uppercase tracking-wide">{{ $col }}</p>
                        <p class="text-3xl font-bold text-gray-800">{{ is_float($total) ? '$' . number_format($total, 2) : number_format($total) }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if(count($stores) > 0)
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach($headers as $header)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($stores as $store)
                            <tr class="hover:bg-gray-50">
                                @foreach($headers as $header)
                                    @php
                                        $val = $store[$header] ?? '';
                                        $isNumeric = is_numeric(str_replace(',', '', $val));
                                    @endphp
                                    <td class="px-4 py-3 whitespace-nowrap {{ $isNumeric ? 'text-right font-mono' : 'text-gray-900' }}">
                                        @if($isNumeric)
                                            {{ number_format((float)$val, 2) }}
                                        @else
                                            {{ $val }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray-400 mt-2">Total de tiendas: {{ count($stores) }}</p>
        @else
            <div class="bg-white rounded-xl shadow p-6 text-center text-gray-500">
                No hay datos de tiendas para mostrar.
            </div>
        @endif
    </div>
</body>
</html>
