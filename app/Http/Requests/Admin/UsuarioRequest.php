<?php

namespace App\Http\Requests\Admin;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($usuario)],
            'telefono' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'rol' => ['required', Rule::enum(Rol::class)],
            'activo' => ['boolean'],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }
}
