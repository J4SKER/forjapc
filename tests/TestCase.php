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

    /**
     * Las pruebas funcionales parten con la contraseña recién confirmada; las que verifican
     * la confirmación de acciones sensibles la quitan con sinConfirmarPassword().
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    protected function sinConfirmarPassword(): static
    {
        $this->app['session']->forget('auth.password_confirmed_at');

        return $this;
    }
}
