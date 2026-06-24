<?php

namespace App\Http\Controllers;

use App\Models\UnidadOperativa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

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

        $roleDescriptions = [
            'admin' => 'Acceso total a todos los módulos, importación de datos y administración de usuarios.',
            'nacional' => 'Acceso a todos los módulos operativos e importación de datos. No administra usuarios.',
            'regional' => 'Acceso limitado a su región asignada. Puede filtrar por Unidad Operativa dentro de su región.',
            'unidad' => 'Acceso limitado únicamente a su Unidad Operativa asignada.',
        ];

        return view('profile', [
            'user' => $user,
            'unidades' => $unidades,
            'roleDescriptions' => $roleDescriptions,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update([
            'name' => $request->name,
        ]);

        return redirect()->to(url()->previous().'#section-nombre')
            ->with('success', 'Nombre actualizado correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return redirect()->to(url()->previous().'#section-password')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
}
