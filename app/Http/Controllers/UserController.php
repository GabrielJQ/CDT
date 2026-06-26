<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Servicios\ServicioUsuario;

class UserController extends Controller
{
    public function __construct(
        private ServicioUsuario $usuario,
    ) {}

    public function index()
    {
        return view('users.index', ['users' => $this->usuario->listUsers()]);
    }

    public function create()
    {
        return view('users.create', $this->usuario->formData());
    }

    public function store(StoreUserRequest $request)
    {
        $this->usuario->createUser($request->validated());

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        return view('users.edit', $this->usuario->formData() + ['user' => $usuario]);
    }

    public function update(UpdateUserRequest $request, User $usuario)
    {
        $this->usuario->updateUser($usuario, $request->validated());

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleActive(User $user)
    {
        $result = $this->usuario->toggleActive($user, auth()->user());

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function resetPassword(User $user)
    {
        $result = $this->usuario->resetPassword($user);

        return back()->with('success', $result['message']);
    }
}
