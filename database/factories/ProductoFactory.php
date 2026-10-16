<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = 'Producto '.fake()->unique()->bothify('??-####');

        return [
            'categoria_id' => fn () => Categoria::query()->value('id')
                ?? Categoria::create(['nombre' => 'Teclados', 'slug' => 'teclados'])->id,
            'marca_id' => fn () => Marca::query()->value('id')
                ?? Marca::create(['nombre' => 'Genérica', 'slug' => 'generica'])->id,
            'sku' => strtoupper(Str::random(8)),
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'descripcion' => fake()->sentence(),
            'precio' => fake()->randomFloat(2, 100, 5000),
            'garantia_meses' => 12,
            'stock_minimo' => 2,
        ];
    }
}
