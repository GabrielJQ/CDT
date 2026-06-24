<!DOCTYPE html>
<html lang="es" class="{{ request()->cookie('tema', '') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — CDT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="absolute inset-0 opacity-80" style="background: radial-gradient(circle at 20% 15%, rgba(152, 130, 86, .22), transparent 24rem), radial-gradient(circle at 80% 90%, rgba(105, 28, 50, .16), transparent 22rem);"></div>

        <section class="institutional-card-top relative w-full max-w-md p-6 lg:p-8">
            <div class="mb-7 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl text-2xl font-extrabold text-white shadow-lg" style="background: linear-gradient(135deg, var(--gob-verde), var(--gob-verde-oscuro));">CDT</div>
                <p class="eyebrow">Panel de Monitoreo</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-gray-950 dark:text-white">Iniciar sesión</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Accede con tu usuario institucional.</p>
            </div>

            @if(session('error'))
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-xs font-extrabold uppercase tracking-wide text-gray-500 dark:text-gray-400">Correo</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="input-institutional w-full">
                    @error('email')
                        <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-xs font-extrabold uppercase tracking-wide text-gray-500 dark:text-gray-400">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="input-institutional w-full">
                    @error('password')
                        <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm font-semibold text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-[#13322B] focus:ring-[#988256]">
                    Mantener sesión iniciada
                </label>

                <button type="submit" class="btn-gold w-full py-3">Entrar</button>
            </form>
        </section>
    </main>
</body>
</html>
