<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\UnidadOperativa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['admin', 'nacional', 'regional', 'unidad'];

    public function index()
    {
        $users = User::query()
            ->with(['region', 'unidadOperativa'])
            ->orderBy('name')
            ->paginate(25);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);

        User::query()->create($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        return view('users.edit', $this->formData() + ['user' => $usuario]);
    }

    public function update(Request $request, User $usuario)
    {
        $data = $this->validated($request, $usuario);
        if (($data['password'] ?? '') !== '') {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleActive(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Estatus del usuario actualizado.');
    }

    public function resetPassword(User $user)
    {
        $password = 'Cdt'.random_int(100000, 999999);
        $user->update(['password' => Hash::make($password)]);

        return back()->with('success', "Contraseña temporal para {$user->email}: {$password}");
    }

    private function formData(): array
    {
        return [
            'roles' => self::ROLES,
            'regiones' => Region::query()->where('is_active', true)->orderBy('nombre')->get(),
            'unidadesOperativas' => UnidadOperativa::query()->with('region')->where('is_active', true)->orderBy('nombre')->get(),
        ];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user === null ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(self::ROLES)],
            'region_id' => ['nullable', 'integer', Rule::exists('regiones', 'id')],
            'unidad_operativa_id' => ['nullable', 'integer', Rule::exists('unidades_operativas', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        if (in_array($data['role'], ['admin', 'nacional'], true)) {
            $data['region_id'] = null;
            $data['unidad_operativa_id'] = null;
        }

        if ($data['role'] === 'regional') {
            $request->validate(['region_id' => ['required', 'integer', Rule::exists('regiones', 'id')]]);
            $data['unidad_operativa_id'] = null;
        }

        if ($data['role'] === 'unidad') {
            $request->validate([
                'region_id' => ['required', 'integer', Rule::exists('regiones', 'id')],
                'unidad_operativa_id' => ['required', 'integer', Rule::exists('unidades_operativas', 'id')->where('region_id', $data['region_id'])],
            ]);
        }

        return $data;
    }
}
