<?php

namespace App\Http\Requests;

use App\Enums\RolUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageUsers() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('usuario'))],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(RolUsuario::values())],
            'region_id' => ['nullable', 'integer', Rule::exists('regiones', 'id')],
            'unidad_operativa_id' => ['nullable', 'integer', Rule::exists('unidades_operativas', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
