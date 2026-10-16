<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompraRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Se descartan filas vacías del detalle dinámico.
        $lineas = collect($this->input('lineas', []))
            ->filter(fn ($linea) => filled($linea['producto_id'] ?? null))
            ->values()
            ->all();

        $this->merge(['lineas' => $lineas]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', Rule::exists('proveedores', 'id')->where('activo', true)],
            'serie_factura' => ['nullable', 'string', 'max:20'],
            'numero_factura' => ['required', 'string', 'max:40'],
            'fecha_factura' => ['required', 'date', 'before_or_equal:today'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'lineas' => ['required', 'array', 'min:1', 'max:100'],
            'lineas.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->whereNull('deleted_at')],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'lineas.*.costo_unitario' => ['required', 'numeric', 'min:0.01', 'max:999999'],
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
            'proveedor_id' => 'proveedor',
            'numero_factura' => 'número de factura',
            'fecha_factura' => 'fecha de la factura',
            'lineas' => 'productos',
            'lineas.*.producto_id' => 'producto',
            'lineas.*.cantidad' => 'cantidad',
            'lineas.*.costo_unitario' => 'costo unitario',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['lineas.*.producto_id.distinct' => 'Un producto aparece más de una vez en la compra.'];
    }
}
