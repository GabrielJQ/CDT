@php
    $selectedRole = old('role', $user?->role ?? 'nacional');
    $selectedRegion = (string) old('region_id', $user?->region_id ?? '');
    $selectedUo = (string) old('unidad_operativa_id', $user?->unidad_operativa_id ?? '');
@endphp

<form action="{{ $action }}" method="POST" class="institutional-card p-5 lg:p-6 space-y-5">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-extrabold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nombre</label>
            <input name="name" value="{{ old('name', $user?->name) }}" required class="input-filter">
            @error('name')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-extrabold uppercase tracking-wide text-gray-500 dark:text-gray-400">Correo</label>
            <input name="email" type="email" value="{{ old('email', $user?->email) }}" required class="input-filter">
            @error('email')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-extrabold uppercase tracking-wide text-gray-500 dark:text-gray-400">Contraseña</label>
            <input name="password" type="password" {{ $user ? '' : 'required' }} class="input-filter" placeholder="{{ $user ? 'Dejar en blanco para conservarla' : 'Mínimo 8 caracteres' }}">
            @error('password')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-extrabold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rol</label>
            <select name="role" id="role-select" required class="input-filter">
                @foreach($roles as $role)
                    <option value="{{ $role }}" {{ $selectedRole === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                @endforeach
            </select>
            @error('role')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div id="region-field">
            <label class="mb-1 block text-xs font-extrabold uppercase tracking-wide text-gray-500 dark:text-gray-400">Región</label>
            <select name="region_id" id="region-id-select" class="input-filter">
                <option value="">Sin región</option>
                @foreach($regiones as $region)
                    <option value="{{ $region->id }}" {{ $selectedRegion === (string) $region->id ? 'selected' : '' }}>{{ $region->nombre }}</option>
                @endforeach
            </select>
            @error('region_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div id="uo-field">
            <label class="mb-1 block text-xs font-extrabold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unidad operativa</label>
            <select name="unidad_operativa_id" id="uo-id-select" class="input-filter">
                <option value="">Sin UO</option>
                @foreach($unidadesOperativas as $uo)
                    <option value="{{ $uo->id }}" data-region="{{ $uo->region_id }}" {{ $selectedUo === (string) $uo->id ? 'selected' : '' }}>{{ $uo->nombre }}</option>
                @endforeach
            </select>
            @error('unidad_operativa_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm font-bold text-gray-600 dark:text-gray-300">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user?->is_active ?? true) ? 'checked' : '' }}>
        Usuario activo
    </label>

    <div class="flex justify-end gap-2">
        <a href="{{ route('usuarios.index') }}" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-gold">Guardar</button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var role = document.getElementById('role-select');
        var region = document.getElementById('region-id-select');
        var uo = document.getElementById('uo-id-select');
        var regionField = document.getElementById('region-field');
        var uoField = document.getElementById('uo-field');

        function refreshFields() {
            var currentRole = role.value;
            regionField.classList.toggle('hidden', currentRole === 'admin' || currentRole === 'nacional');
            uoField.classList.toggle('hidden', currentRole !== 'unidad');

            Array.from(uo.options).forEach(function (option) {
                if (!option.value) return;
                option.hidden = region.value && option.dataset.region !== region.value;
            });
        }

        role.addEventListener('change', refreshFields);
        region.addEventListener('change', refreshFields);
        refreshFields();
    });
</script>
