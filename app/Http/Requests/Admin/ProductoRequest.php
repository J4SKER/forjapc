<?php

namespace App\Http\Requests\Admin;

use App\Models\Categoria;
use App\Models\Producto;
use App\Support\CamposEspecificacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('nombre')),
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'precio_anterior' => $this->filled('precio_anterior') ? $this->input('precio_anterior') : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Producto|null $producto */
        $producto = $this->route('producto');
        $categoria = Categoria::find($this->input('categoria_id'));

        return [
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')],
            'marca_id' => ['required', 'integer', Rule::exists('marcas', 'id')],
            'sku' => ['required', 'string', 'max:40', Rule::unique('productos', 'sku')->ignore($producto)],
            'nombre' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', Rule::unique('productos', 'slug')->ignore($producto)],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'precio_anterior' => ['nullable', 'numeric', 'gt:precio', 'max:999999'],
            'garantia_meses' => ['required', 'integer', 'min:0', 'max:120'],
            'stock_minimo' => ['required', 'integer', 'min:0', 'max:1000'],
            'consumo_watts' => ['required', 'integer', 'min:0', 'max:2000'],
            'destacado' => ['boolean'],
            'activo' => ['boolean'],
            'imagenes' => ['array', 'max:8'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ...($categoria?->tipo_componente ? CamposEspecificacion::reglas($categoria->tipo_componente) : []),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $categoria = Categoria::find($this->input('categoria_id'));
        $nombres = ['categoria_id' => 'categoría', 'marca_id' => 'marca', 'precio_anterior' => 'precio anterior', 'imagenes.*' => 'imagen'];

        if ($categoria?->tipo_componente) {
            foreach (CamposEspecificacion::de($categoria->tipo_componente) as $campo => $definicion) {
                $nombres["esp.{$campo}"] = mb_strtolower($definicion['etiqueta']);
            }
        }

        return $nombres;
    }
}
