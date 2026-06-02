<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado — Dashboard CDT</title>
    @vite('resources/css/app.css')
    <script>document.documentElement.classList.toggle('dark', /tema=dark/.test(document.cookie));</script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-6">
    <div class="text-center max-w-md">
        <div class="text-7xl mb-4">🔒</div>
        <h1 class="text-5xl font-bold text-gray-800 dark:text-gray-100 mb-2">403</h1>
        <p class="text-lg text-gray-500 dark:text-gray-400 mb-6">Acceso denegado</p>
        <p class="text-sm text-gray-400 dark:text-gray-500 mb-8">No tienes permiso para acceder a esta página.</p>
        <a href="{{ url('/') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-semibold transition inline-block">← Volver al inicio</a>
    </div>
</body>
</html>
