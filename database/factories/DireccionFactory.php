<?php

namespace Database\Factories;

use App\Models\Departamento;
use App\Models\Direccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Direccion>
 */
class DireccionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'alias' => fake()->randomElement(['Casa', 'Trabajo', 'Oficina']),
            'nombre_receptor' => fake()->name(),
            'telefono' => fake()->numerify('########'),
            'departamento_id' => fn () => Departamento::query()->value('id')
                ?? Departamento::create(['nombre' => 'Guatemala', 'tarifa_envio' => 25])->id,
            'municipio' => 'Guatemala',
            'direccion' => fake()->streetAddress(),
            'referencias' => null,
            'es_principal' => false,
        ];
    }
}
