<?php

namespace Tests\Feature\Tienda;

use App\Livewire\Tienda\Catalogo;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Socket;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogoTiendaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogoSeeder::class);
    }

    public function test_la_categoria_muestra_sus_productos_con_precio_y_disponibilidad(): void
    {
        $this->get('/categoria/procesadores')
            ->assertOk()
            ->assertSee('AMD Ryzen 5 7600')
            ->assertSee('Q1,750.00')
            ->assertSee('Agotado'); // Ryzen 7 7800X3D se cargó sin existencia
    }

    public function test_filtra_por_socket_y_por_disponibilidad(): void
    {
        $categoria = Categoria::query()->where('slug', 'tarjetas-madre')->firstOrFail();
        $am4 = Socket::query()->where('nombre', 'AM4')->value('id');

        Livewire::test(Catalogo::class, ['categoria' => $categoria])
            ->set('tecnicos.socket_id', (string) $am4)
            ->assertSee('ASRock B550M Pro4')
            ->assertDontSee('MSI B650 GAMING PLUS WIFI');

        Livewire::test(Catalogo::class, ['categoria' => Categoria::query()->where('slug', 'procesadores')->first()])
            ->set('soloDisponibles', true)
            ->assertDontSee('AMD Ryzen 7 7800X3D')
            ->assertSee('AMD Ryzen 5 7600');
    }

    public function test_filtra_por_marca_y_rango_de_precio(): void
    {
        $categoria = Categoria::query()->where('slug', 'tarjetas-de-video')->firstOrFail();
        $msi = Producto::query()->where('slug', 'msi-geforce-rtx-4060-ventus-2x-8gb')->value('marca_id');

        Livewire::test(Catalogo::class, ['categoria' => $categoria])
            ->set('marcas', [$msi])
            ->assertSee('RTX 4060')
            ->assertDontSee('Sapphire PULSE Radeon RX 7600');

        Livewire::test(Catalogo::class, ['categoria' => $categoria])
            ->set('max', '3000')
            ->assertSee('RTX 4060')
            ->assertDontSee('RTX 4070 SUPER');
    }

    public function test_la_busqueda_encuentra_por_nombre_y_sugiere_si_hay_error_de_escritura(): void
    {
        $this->get('/buscar?q=ryzen')->assertOk()->assertSee('AMD Ryzen 5 5600');
        $this->get('/buscar?q=i5-14400f')->assertOk()->assertSee('Intel Core i5-14400F');

        $this->get('/buscar?q=rizen')->assertOk()->assertSee('Quisiste decir')->assertSee('ryzen');
    }

    public function test_la_ficha_muestra_especificaciones_y_productos_relacionados(): void
    {
        $this->get('/producto/amd-ryzen-5-7600')
            ->assertOk()
            ->assertSee('Socket')
            ->assertSee('AM5')
            ->assertSee('6 / 12')
            ->assertSee('También te puede interesar');
    }

    public function test_un_producto_inactivo_no_se_muestra(): void
    {
        $producto = Producto::query()->where('slug', 'amd-ryzen-5-7600')->firstOrFail();
        $producto->update(['activo' => false]);

        $this->get('/producto/amd-ryzen-5-7600')->assertNotFound();
        $this->get('/categoria/procesadores')->assertDontSee('AMD Ryzen 5 7600');
    }

    public function test_el_inicio_muestra_ofertas_y_destacados(): void
    {
        $this->get('/')->assertOk()->assertSee('OFERTAS')->assertSee('Productos destacados')->assertSee('Logitech G502 HERO');
    }
}
