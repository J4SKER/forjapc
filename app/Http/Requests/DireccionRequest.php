<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DireccionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alias' => ['required', 'string', 'max:40'],
            'nombre_receptor' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'departamento_id' => ['required', 'integer', Rule::exists('departamentos', 'id')->where('activo', true)],
            'municipio' => ['required', 'string', 'max:80'],
            'direccion' => ['required', 'string', 'max:255'],
            'referencias' => ['nullable', 'string', 'max:255'],
            'es_principal' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'alias' => 'nombre de la dirección',
            'departamento_id' => 'departamento',
        ];
    }
}
