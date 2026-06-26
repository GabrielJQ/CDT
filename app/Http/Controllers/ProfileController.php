<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Requests\ProfilePasswordRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UnidadOperativa;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function show()
    {
        $user = request()->user()->load(['region', 'unidadOperativa']);

        $unidades = collect();
        if ($user->isRegional() && $user->region_id) {
            $unidades = UnidadOperativa::where('region_id', $user->region_id)
                ->orderBy('nombre')
                ->get();
        }

        $roleDescriptions = [];
        foreach (RolUsuario::cases() as $role) {
            $roleDescriptions[$role->value] = $role->description();
        }

        return view('profile', [
            'user' => $user,
            'unidades' => $unidades,
            'roleDescriptions' => $roleDescriptions,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'name' => $request->name,
        ]);

        return redirect()->to(url()->previous().'#section-nombre')
            ->with('success', 'Nombre actualizado correctamente.');
    }

    public function updatePassword(ProfilePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        return redirect()->to(url()->previous().'#section-password')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
}
