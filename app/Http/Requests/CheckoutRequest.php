<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['nit' => strtoupper(trim((string) ($this->input('nit') ?: 'CF')))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'metodo_entrega' => ['required', Rule::in(['ENVIO', 'RECOGER'])],
            'direccion_id' => ['required_if:metodo_entrega,ENVIO', 'nullable', 'integer',
                Rule::exists('direcciones', 'id')->where('usuario_id', $this->user()->id)],
            'nombre_facturacion' => ['required', 'string', 'max:120'],
            'nit' => ['required', 'string', 'max:15', 'regex:/^(CF|[0-9]{1,12}-?[0-9K])$/'],
            'telefono' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'metodo_pago' => ['required', Rule::in(['PAYPAL', 'TRANSFERENCIA'])],
            'notas' => ['nullable', 'string', 'max:500'],
            'acepto' => ['accepted'],
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
            'metodo_entrega' => 'forma de entrega',
            'direccion_id' => 'dirección de entrega',
            'nombre_facturacion' => 'nombre para la factura',
            'metodo_pago' => 'forma de pago',
            'acepto' => 'aceptación de términos',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['direccion_id.required_if' => 'Elige la dirección a la que enviaremos tu pedido.'];
    }
}
