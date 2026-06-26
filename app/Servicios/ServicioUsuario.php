<?php

namespace App\Servicios;

use App\Enums\RolUsuario;
use App\Models\Region;
use App\Models\UnidadOperativa;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class ServicioUsuario
{
    public function listUsers(): LengthAwarePaginator
    {
        return User::query()
            ->with(['region', 'unidadOperativa'])
            ->orderBy('name')
            ->paginate(25);
    }

    public function formData(): array
    {
        return [
            'roles' => RolUsuario::values(),
            'regiones' => Region::query()->where('is_active', true)->orderBy('nombre')->get(),
            'unidadesOperativas' => UnidadOperativa::query()->with('region')->where('is_active', true)->orderBy('nombre')->get(),
        ];
    }

    public function createUser(array $data): User
    {
        $data = $this->normalizeRoleData($data);
        $data['password'] = Hash::make($data['password']);

        return User::query()->create($data);
    }

    public function updateUser(User $user, array $data): User
    {
        $data = $this->normalizeRoleData($data);

        if (($data['password'] ?? '') !== '') {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user;
    }

    public function toggleActive(User $user, User $currentUser): array
    {
        if ($user->is($currentUser)) {
            return ['success' => false, 'message' => 'No puedes desactivar tu propio usuario.'];
        }

        $user->update(['is_active' => ! $user->is_active]);

        return ['success' => true, 'message' => 'Estatus del usuario actualizado.'];
    }

    public function resetPassword(User $user): array
    {
        $password = 'Cdt'.random_int(100000, 999999);
        $user->update(['password' => Hash::make($password)]);

        return ['success' => true, 'message' => "Contraseña temporal para {$user->email}: {$password}"];
    }

    private function normalizeRoleData(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $role = isset($data['role']) ? RolUsuario::tryFrom($data['role']) : null;

        if ($role !== null && $role->isGlobal()) {
            $data['region_id'] = null;
            $data['unidad_operativa_id'] = null;
        }

        if ($role === RolUsuario::Regional) {
            $data['unidad_operativa_id'] = null;
        }

        return $data;
    }
}
