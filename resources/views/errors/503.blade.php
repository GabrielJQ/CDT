<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio no disponible — Dashboard CDT</title>
    @vite('resources/css/app.css')
    <script>document.documentElement.classList.toggle('dark', /tema=dark/.test(document.cookie));</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-6">
    <div class="text-center max-w-md">
        <div class="text-7xl mb-4">🔧</div>
        <h1 class="text-5xl font-bold text-gray-800 dark:text-gray-100 mb-2">503</h1>
        <p class="text-lg text-gray-500 dark:text-gray-400 mb-6">Servicio no disponible</p>
        @php $msg = $exception->getMessage() ?? ''; @endphp
        @if($msg)
            <p class="text-sm text-red-500 dark:text-red-400 mb-2 bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-lg inline-block">{{ $msg }}</p>
        @endif
        <p class="text-sm text-gray-400 dark:text-gray-500 mb-8">No se pudieron obtener los datos. Intenta recargar o vuelve más tarde.</p>
        <div class="flex gap-3 justify-center">
            <a href="{{ url('/refresh') }}" onclick="event.preventDefault(); document.getElementById('refresh-form').submit();" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-semibold transition inline-block">↻ Reintentar</a>
            <a href="{{ url('/') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-6 py-2.5 rounded-lg font-semibold transition inline-block">← Volver al inicio</a>
        </div>
        <form id="refresh-form" action="{{ url('/refresh') }}" method="POST" class="hidden">@csrf</form>
    </div>
</body>
</html>
