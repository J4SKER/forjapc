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
            ->assertSee('Arma tu PC a la medida')
            ->assertSee('Compra por categoría');
    }

    public function test_las_categorias_del_menu_existen_y_las_demas_dan_404(): void
    {
        $this->get('/categoria/procesadores')->assertOk()->assertSee('Procesadores');
        $this->get('/categoria/no-existe')->assertNotFound();
    }

    public function test_la_busqueda_muestra_el_termino_escapado(): void
    {
        $this->get('/buscar?q=<script>rtx</script>')
            ->assertOk()
            ->assertDontSee('<script>rtx</script>', false);
    }
}
