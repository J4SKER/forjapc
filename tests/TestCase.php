<?php

namespace Tests;

use Database\Seeders\NomenclaturaSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Todas las pruebas parten con la nomenclatura contable cargada, porque las compras,
     * ventas y ajustes registran sus partidas automáticamente.
     */
    protected bool $seed = true;

    protected string $seeder = NomenclaturaSeeder::class;
}
