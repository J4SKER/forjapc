<?php

namespace App\Http\Requests\Admin;

use App\Enums\Rol;
use App\Models\User;
use App\Support\ReglasPassword;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    /**
     * Solo el propietario modifica cuentas de administrador (y nadie toca la del propietario).
     */
    public function authorize(): bool
    {
        $usuario = $this->route('usuario');

        return ! $usuario instanceof User || $this->user()->puedeGestionarA($usuario);
    }

    /**
     * Roles que quien edita puede asignar: el de administrador solo lo da el propietario
     * (o lo conserva un administrador que se edita a sí mismo).
     *
     * @return list<Rol>
     */
    public static function rolesAsignables(User $actor, ?User $usuario = null): array
    {
        if ($actor->esPropietario() || ($usuario && $actor->is($usuario))) {
            return Rol::cases();
        }

        return array_values(array_filter(Rol::cases(), fn (Rol $rol) => $rol !== Rol::Admin));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $rolNuevo = Rol::tryFrom((string) $this->input('rol'));

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($usuario)],
            'telefono' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'rol' => ['required', Rule::in(array_map(fn (Rol $rol) => $rol->value, self::rolesAsignables($this->user(), $usuario)))],
            'activo' => ['boolean'],
            // La contraseña solo se define al crear la cuenta (temporal). Después, el dueño la
            // cambia con un enlace de restablecimiento: nadie del personal la conoce.
            'password' => $usuario
                ? ['prohibited']
                : ['required', 'confirmed', $rolNuevo?->esPersonal() ? ReglasPassword::personal() : ReglasPassword::cliente()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rol.in' => 'Solo el propietario puede asignar el rol de administrador.',
            'password.prohibited' => 'La contraseña de una cuenta existente no se cambia aquí: envía un enlace para restablecerla.',
        ];
    }
}
