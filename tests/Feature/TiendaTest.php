<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_inicio_muestra_la_tienda(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Arma la PC de tus sueños')
            ->assertSee('Explora por componente');
    }

    public function test_la_busqueda_muestra_el_termino_escapado(): void
    {
        $this->get('/buscar?q=<script>rtx</script>')
            ->assertOk()
            ->assertDontSee('<script>rtx</script>', false);
    }
}
