<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * El NIT se guarda en mayúsculas y sin espacios (ej. "1234567-K" o "CF").
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['nit' => strtoupper(trim((string) $this->input('nit')))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'nit' => ['required', 'string', 'max:15', 'regex:/^(CF|[0-9]{1,12}-?[0-9K])$/i'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Cambiar el correo exige la contraseña: con una sesión robada no se puede desviar
            // la recuperación de la cuenta a otro buzón.
            'password_actual' => [
                Rule::requiredIf(fn () => Str::lower((string) $this->input('email')) !== $this->user()->email),
                'nullable',
                'current_password',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['password_actual' => 'contraseña actual'];
    }
}
