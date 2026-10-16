<?php

namespace Database\Seeders;

use App\Enums\TipoComponente;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Socket;
use App\Models\User;
use App\Services\CompraService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Catálogo de ejemplo con productos reales y precios aproximados en quetzales (IVA incluido).
 * La existencia inicial entra al kardex mediante una compra recibida.
 */
class CatalogoSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $sockets = [];

    public function run(CompraService $compras): void
    {
        if (Producto::query()->exists()) {
            return;
        }

        $this->categorias();
        foreach (['AM4', 'AM5', 'LGA1700', 'LGA1851'] as $nombre) {
            $this->sockets[$nombre] = Socket::firstOrCreate(['nombre' => $nombre])->id;
        }

        $lineas = [];
        foreach ($this->productos() as $datos) {
            [$categoria, $marca, $nombre, $precio, $existencia, $especificacion] = $datos;
            $extra = $datos[6] ?? [];

            $producto = Producto::create([
                'categoria_id' => Categoria::query()->where('slug', $categoria)->value('id'),
                'marca_id' => Marca::firstOrCreate(['slug' => Str::slug($marca)], ['nombre' => $marca])->id,
                'sku' => strtoupper(Str::substr(Str::slug($categoria, ''), 0, 3)).'-'.str_pad((string) (Producto::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT),
                'nombre' => $nombre,
                'slug' => Str::slug($nombre),
                'descripcion' => $extra['descripcion'] ?? "{$nombre}, nuevo y con garantía en ForjaPC.",
                'precio' => $precio,
                'precio_anterior' => $extra['antes'] ?? null,
                'garantia_meses' => $extra['garantia'] ?? 12,
                'consumo_watts' => $extra['watts'] ?? 0,
                'destacado' => $extra['destacado'] ?? false,
            ]);

            $this->guardarEspecificacion($producto, $especificacion);

            if ($existencia > 0) {
                $lineas[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $existencia,
                    'costo_unitario' => round($precio / 1.12 * 0.78, 2),
                ];
            }
        }

        $proveedor = Proveedor::firstOrCreate(['nit' => '9876543-2'], [
            'nombre' => 'Distribuidora Tecnológica de Guatemala, S.A.',
            'contacto' => 'Departamento de ventas',
            'telefono' => '2300-0000',
            'email' => 'ventas@distribuidora.example',
        ]);

        $admin = User::query()->where('rol', 'ADMIN')->first();
        if ($admin) {
            Auth::setUser($admin);
        }

        $compra = $compras->guardarBorrador([
            'proveedor_id' => $proveedor->id,
            'serie_factura' => 'A',
            'numero_factura' => '1001',
            'fecha_factura' => now()->subDays(3)->toDateString(),
            'notas' => 'Inventario inicial de la tienda.',
        ], $lineas);

        $compras->recibir($compra);
    }

    private function categorias(): void
    {
        $categorias = [
            ['Procesadores', 'procesadores', 'cpu', TipoComponente::Cpu],
            ['Tarjetas madre', 'tarjetas-madre', 'placa', TipoComponente::Placa],
            ['Memorias RAM', 'memorias-ram', 'ram', TipoComponente::Ram],
            ['Tarjetas de video', 'tarjetas-de-video', 'gpu', TipoComponente::Gpu],
            ['Almacenamiento', 'almacenamiento', 'disco', TipoComponente::Almacenamiento],
            ['Fuentes de poder', 'fuentes-de-poder', 'fuente', TipoComponente::Fuente],
            ['Gabinetes', 'gabinetes', 'gabinete', TipoComponente::Gabinete],
            ['Enfriamiento', 'enfriamiento', 'ventilador', TipoComponente::Disipador],
            ['Monitores', 'monitores', 'monitor', TipoComponente::Monitor],
            ['Teclados', 'teclados', 'teclado', null],
            ['Mouse', 'mouse', 'mouse', null],
            ['Audífonos', 'audifonos', 'audifonos', null],
        ];

        foreach ($categorias as $orden => [$nombre, $slug, $icono, $tipo]) {
            Categoria::updateOrCreate(['slug' => $slug], [
                'nombre' => $nombre, 'icono' => $icono, 'tipo_componente' => $tipo, 'orden' => $orden,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $especificacion
     */
    private function guardarEspecificacion(Producto $producto, array $especificacion): void
    {
        $tipo = $producto->load('categoria')->tipoComponente();

        if ($tipo === null || $especificacion === []) {
            return;
        }

        $sockets = $especificacion['sockets'] ?? null;
        unset($especificacion['sockets']);

        if (isset($especificacion['socket'])) {
            $especificacion['socket_id'] = $this->sockets[$especificacion['socket']];
            unset($especificacion['socket']);
        }

        $modelo = $producto->{$tipo->relacion()}()->create($especificacion);

        if ($sockets !== null) {
            $modelo->sockets()->sync(array_map(fn (string $s) => $this->sockets[$s], $sockets));
        }
    }

    /**
     * [categoría, marca, nombre, precio, existencia, especificación, extra]
     *
     * @return list<array{0: string, 1: string, 2: string, 3: float, 4: int, 5: array<string, mixed>, 6?: array<string, mixed>}>
     */
    private function productos(): array
    {
        $todosLosSockets = ['AM4', 'AM5', 'LGA1700', 'LGA1851'];

        return [
            // Procesadores
            ['procesadores', 'AMD', 'AMD Ryzen 5 7600', 1750, 12, ['socket' => 'AM5', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.8, 'frecuencia_turbo_ghz' => 5.1, 'tdp_w' => 65, 'tipo_memoria' => 'DDR5', 'graficos_integrados' => true, 'incluye_disipador' => true], ['watts' => 65, 'destacado' => true, 'antes' => 1950]],
            ['procesadores', 'AMD', 'AMD Ryzen 7 7800X3D', 3650, 0, ['socket' => 'AM5', 'nucleos' => 8, 'hilos' => 16, 'frecuencia_base_ghz' => 4.2, 'frecuencia_turbo_ghz' => 5.0, 'tdp_w' => 120, 'tipo_memoria' => 'DDR5', 'graficos_integrados' => true, 'incluye_disipador' => false], ['watts' => 120, 'destacado' => true]],
            ['procesadores', 'AMD', 'AMD Ryzen 5 9600X', 2150, 6, ['socket' => 'AM5', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.9, 'frecuencia_turbo_ghz' => 5.4, 'tdp_w' => 65, 'tipo_memoria' => 'DDR5', 'graficos_integrados' => true, 'incluye_disipador' => false], ['watts' => 65]],
            ['procesadores', 'AMD', 'AMD Ryzen 5 5600', 995, 15, ['socket' => 'AM4', 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.5, 'frecuencia_turbo_ghz' => 4.4, 'tdp_w' => 65, 'tipo_memoria' => 'DDR4', 'graficos_integrados' => false, 'incluye_disipador' => true], ['watts' => 65]],
            ['procesadores', 'Intel', 'Intel Core i5-14400F', 1690, 10, ['socket' => 'LGA1700', 'nucleos' => 10, 'hilos' => 16, 'frecuencia_base_ghz' => 2.5, 'frecuencia_turbo_ghz' => 4.7, 'tdp_w' => 65, 'tipo_memoria' => 'DDR4/DDR5', 'graficos_integrados' => false, 'incluye_disipador' => true], ['watts' => 65, 'destacado' => true]],
            ['procesadores', 'Intel', 'Intel Core i7-14700K', 3450, 4, ['socket' => 'LGA1700', 'nucleos' => 20, 'hilos' => 28, 'frecuencia_base_ghz' => 3.4, 'frecuencia_turbo_ghz' => 5.6, 'tdp_w' => 125, 'tipo_memoria' => 'DDR4/DDR5', 'graficos_integrados' => true, 'incluye_disipador' => false], ['watts' => 125]],
            ['procesadores', 'Intel', 'Intel Core Ultra 5 245K', 2450, 5, ['socket' => 'LGA1851', 'nucleos' => 14, 'hilos' => 14, 'frecuencia_base_ghz' => 4.2, 'frecuencia_turbo_ghz' => 5.2, 'tdp_w' => 125, 'tipo_memoria' => 'DDR5', 'graficos_integrados' => true, 'incluye_disipador' => false], ['watts' => 125]],

            // Tarjetas madre
            ['tarjetas-madre', 'MSI', 'MSI B650 GAMING PLUS WIFI', 1690, 8, ['socket' => 'AM5', 'chipset' => 'B650', 'formato' => 'ATX', 'tipo_memoria' => 'DDR5', 'ranuras_ram' => 4, 'ram_max_gb' => 192, 'slots_m2' => 2, 'puertos_sata' => 4, 'wifi' => true], ['watts' => 50, 'destacado' => true]],
            ['tarjetas-madre', 'ASUS', 'ASUS TUF GAMING X670E-PLUS WIFI', 2790, 3, ['socket' => 'AM5', 'chipset' => 'X670E', 'formato' => 'ATX', 'tipo_memoria' => 'DDR5', 'ranuras_ram' => 4, 'ram_max_gb' => 192, 'slots_m2' => 4, 'puertos_sata' => 4, 'wifi' => true], ['watts' => 60]],
            ['tarjetas-madre', 'Gigabyte', 'Gigabyte A620M GAMING X', 850, 9, ['socket' => 'AM5', 'chipset' => 'A620', 'formato' => 'Micro-ATX', 'tipo_memoria' => 'DDR5', 'ranuras_ram' => 2, 'ram_max_gb' => 96, 'slots_m2' => 1, 'puertos_sata' => 4, 'wifi' => false], ['watts' => 40]],
            ['tarjetas-madre', 'ASRock', 'ASRock B550M Pro4', 995, 7, ['socket' => 'AM4', 'chipset' => 'B550', 'formato' => 'Micro-ATX', 'tipo_memoria' => 'DDR4', 'ranuras_ram' => 4, 'ram_max_gb' => 128, 'slots_m2' => 2, 'puertos_sata' => 4, 'wifi' => false], ['watts' => 40]],
            ['tarjetas-madre', 'MSI', 'MSI PRO B760M-A WIFI DDR4', 1250, 6, ['socket' => 'LGA1700', 'chipset' => 'B760', 'formato' => 'Micro-ATX', 'tipo_memoria' => 'DDR4', 'ranuras_ram' => 4, 'ram_max_gb' => 128, 'slots_m2' => 2, 'puertos_sata' => 4, 'wifi' => true], ['watts' => 45]],
            ['tarjetas-madre', 'ASUS', 'ASUS ROG STRIX Z790-E GAMING WIFI II', 4190, 2, ['socket' => 'LGA1700', 'chipset' => 'Z790', 'formato' => 'ATX', 'tipo_memoria' => 'DDR5', 'ranuras_ram' => 4, 'ram_max_gb' => 192, 'slots_m2' => 5, 'puertos_sata' => 4, 'wifi' => true], ['watts' => 70]],
            ['tarjetas-madre', 'Gigabyte', 'Gigabyte B860M AORUS ELITE WIFI6E', 1890, 4, ['socket' => 'LGA1851', 'chipset' => 'B860', 'formato' => 'Micro-ATX', 'tipo_memoria' => 'DDR5', 'ranuras_ram' => 4, 'ram_max_gb' => 256, 'slots_m2' => 3, 'puertos_sata' => 4, 'wifi' => true], ['watts' => 50]],
            ['tarjetas-madre', 'ASUS', 'ASUS ROG STRIX B650E-I GAMING WIFI', 2590, 2, ['socket' => 'AM5', 'chipset' => 'B650E', 'formato' => 'Mini-ITX', 'tipo_memoria' => 'DDR5', 'ranuras_ram' => 2, 'ram_max_gb' => 96, 'slots_m2' => 2, 'puertos_sata' => 2, 'wifi' => true], ['watts' => 45]],

            // Memorias RAM
            ['memorias-ram', 'Kingston', 'Kingston FURY Beast DDR5 32GB (2x16) 6000MHz', 975, 14, ['tipo' => 'DDR5', 'capacidad_total_gb' => 32, 'modulos' => 2, 'velocidad_mhz' => 6000, 'latencia_cl' => 36], ['watts' => 10, 'destacado' => true]],
            ['memorias-ram', 'Corsair', 'Corsair Vengeance DDR5 16GB (2x8) 5600MHz', 520, 10, ['tipo' => 'DDR5', 'capacidad_total_gb' => 16, 'modulos' => 2, 'velocidad_mhz' => 5600, 'latencia_cl' => 36], ['watts' => 8]],
            ['memorias-ram', 'Kingston', 'Kingston FURY Beast DDR4 16GB (2x8) 3200MHz', 385, 12, ['tipo' => 'DDR4', 'capacidad_total_gb' => 16, 'modulos' => 2, 'velocidad_mhz' => 3200, 'latencia_cl' => 16], ['watts' => 6, 'antes' => 450]],
            ['memorias-ram', 'Corsair', 'Corsair Vengeance LPX DDR4 32GB (2x16) 3600MHz', 695, 8, ['tipo' => 'DDR4', 'capacidad_total_gb' => 32, 'modulos' => 2, 'velocidad_mhz' => 3600, 'latencia_cl' => 18], ['watts' => 8]],
            ['memorias-ram', 'G.Skill', 'G.Skill Trident Z5 RGB DDR5 64GB (2x32) 6400MHz', 2150, 3, ['tipo' => 'DDR5', 'capacidad_total_gb' => 64, 'modulos' => 2, 'velocidad_mhz' => 6400, 'latencia_cl' => 32], ['watts' => 12]],

            // Tarjetas de video
            ['tarjetas-de-video', 'MSI', 'MSI GeForce RTX 4060 VENTUS 2X 8GB', 2690, 9, ['chipset' => 'NVIDIA GeForce RTX 4060', 'vram_gb' => 8, 'longitud_mm' => 199, 'tdp_w' => 115, 'fuente_recomendada_w' => 550, 'conectores_energia' => '1 × 8 pines'], ['watts' => 115, 'destacado' => true]],
            ['tarjetas-de-video', 'ASUS', 'ASUS Dual GeForce RTX 4070 SUPER 12GB', 5290, 4, ['chipset' => 'NVIDIA GeForce RTX 4070 SUPER', 'vram_gb' => 12, 'longitud_mm' => 267, 'tdp_w' => 220, 'fuente_recomendada_w' => 650, 'conectores_energia' => '1 × 16 pines (12VHPWR)'], ['watts' => 220, 'antes' => 5690]],
            ['tarjetas-de-video', 'Sapphire', 'Sapphire PULSE Radeon RX 7600 8GB', 2190, 7, ['chipset' => 'AMD Radeon RX 7600', 'vram_gb' => 8, 'longitud_mm' => 240, 'tdp_w' => 165, 'fuente_recomendada_w' => 550, 'conectores_energia' => '1 × 8 pines'], ['watts' => 165]],
            ['tarjetas-de-video', 'XFX', 'XFX Speedster MERC 319 Radeon RX 7800 XT 16GB', 4490, 2, ['chipset' => 'AMD Radeon RX 7800 XT', 'vram_gb' => 16, 'longitud_mm' => 344, 'tdp_w' => 263, 'fuente_recomendada_w' => 750, 'conectores_energia' => '2 × 8 pines'], ['watts' => 263]],
            ['tarjetas-de-video', 'Gigabyte', 'Gigabyte GeForce RTX 5070 WINDFORCE OC 12GB', 5190, 3, ['chipset' => 'NVIDIA GeForce RTX 5070', 'vram_gb' => 12, 'longitud_mm' => 261, 'tdp_w' => 250, 'fuente_recomendada_w' => 650, 'conectores_energia' => '1 × 16 pines (12V-2x6)'], ['watts' => 250, 'destacado' => true]],

            // Almacenamiento
            ['almacenamiento', 'Samsung', 'Samsung 990 PRO 1TB NVMe M.2', 995, 11, ['tipo' => 'SSD NVMe', 'formato' => 'M.2', 'capacidad_gb' => 1000, 'lectura_mbps' => 7450, 'escritura_mbps' => 6900], ['watts' => 6, 'garantia' => 60, 'destacado' => true]],
            ['almacenamiento', 'Kingston', 'Kingston NV3 1TB NVMe M.2', 560, 15, ['tipo' => 'SSD NVMe', 'formato' => 'M.2', 'capacidad_gb' => 1000, 'lectura_mbps' => 6000, 'escritura_mbps' => 4000], ['watts' => 5, 'garantia' => 36, 'antes' => 640]],
            ['almacenamiento', 'Crucial', 'Crucial MX500 1TB SATA 2.5"', 620, 8, ['tipo' => 'SSD SATA', 'formato' => '2.5"', 'capacidad_gb' => 1000, 'lectura_mbps' => 560, 'escritura_mbps' => 510], ['watts' => 4, 'garantia' => 60]],
            ['almacenamiento', 'Western Digital', 'WD Blue 2TB HDD 7200 RPM', 520, 6, ['tipo' => 'HDD', 'formato' => '3.5"', 'capacidad_gb' => 2000, 'lectura_mbps' => 215, 'escritura_mbps' => 215], ['watts' => 8, 'garantia' => 24]],
            ['almacenamiento', 'Western Digital', 'WD_BLACK SN850X 2TB NVMe M.2', 1450, 5, ['tipo' => 'SSD NVMe', 'formato' => 'M.2', 'capacidad_gb' => 2000, 'lectura_mbps' => 7300, 'escritura_mbps' => 6600], ['watts' => 7, 'garantia' => 60]],

            // Fuentes de poder
            ['fuentes-de-poder', 'EVGA', 'EVGA 600 W1 600W 80 Plus', 450, 10, ['potencia_w' => 600, 'certificacion' => '80 Plus White', 'modular' => 'No', 'formato' => 'ATX'], ['garantia' => 24]],
            ['fuentes-de-poder', 'Corsair', 'Corsair CX650 650W 80 Plus Bronze', 595, 9, ['potencia_w' => 650, 'certificacion' => '80 Plus Bronze', 'modular' => 'No', 'formato' => 'ATX'], ['garantia' => 60]],
            ['fuentes-de-poder', 'MSI', 'MSI MAG A750GL PCIE5 750W 80 Plus Gold', 895, 7, ['potencia_w' => 750, 'certificacion' => '80 Plus Gold', 'modular' => 'Total', 'formato' => 'ATX'], ['garantia' => 120, 'destacado' => true]],
            ['fuentes-de-poder', 'Corsair', 'Corsair RM850e 850W 80 Plus Gold', 1090, 5, ['potencia_w' => 850, 'certificacion' => '80 Plus Gold', 'modular' => 'Total', 'formato' => 'ATX'], ['garantia' => 84]],
            ['fuentes-de-poder', 'Cooler Master', 'Cooler Master V SFX Gold 750W', 1150, 3, ['potencia_w' => 750, 'certificacion' => '80 Plus Gold', 'modular' => 'Total', 'formato' => 'SFX'], ['garantia' => 120]],
            ['fuentes-de-poder', 'be quiet!', 'be quiet! Pure Power 12 1000W 80 Plus Gold', 1450, 2, ['potencia_w' => 1000, 'certificacion' => '80 Plus Gold', 'modular' => 'Total', 'formato' => 'ATX'], ['garantia' => 120]],

            // Gabinetes
            ['gabinetes', 'Lian Li', 'Lian Li LANCOOL 216', 995, 6, ['formato_max_placa' => 'E-ATX', 'gpu_max_mm' => 392, 'disipador_max_mm' => 180, 'radiador_max_mm' => 360, 'formato_fuente' => 'ATX', 'bahias_25' => 4, 'bahias_35' => 2], ['watts' => 10, 'destacado' => true]],
            ['gabinetes', 'NZXT', 'NZXT H5 Flow', 795, 7, ['formato_max_placa' => 'ATX', 'gpu_max_mm' => 365, 'disipador_max_mm' => 165, 'radiador_max_mm' => 280, 'formato_fuente' => 'ATX', 'bahias_25' => 2, 'bahias_35' => 1], ['watts' => 6]],
            ['gabinetes', 'Cooler Master', 'Cooler Master MasterBox Q300L', 430, 8, ['formato_max_placa' => 'Micro-ATX', 'gpu_max_mm' => 360, 'disipador_max_mm' => 159, 'radiador_max_mm' => 240, 'formato_fuente' => 'ATX', 'bahias_25' => 2, 'bahias_35' => 1], ['watts' => 3]],
            ['gabinetes', 'Cooler Master', 'Cooler Master MasterBox NR200P', 795, 3, ['formato_max_placa' => 'Mini-ITX', 'gpu_max_mm' => 330, 'disipador_max_mm' => 155, 'radiador_max_mm' => 280, 'formato_fuente' => 'SFX', 'bahias_25' => 3, 'bahias_35' => 1], ['watts' => 6]],
            ['gabinetes', 'Corsair', 'Corsair 4000D Airflow', 895, 5, ['formato_max_placa' => 'ATX', 'gpu_max_mm' => 360, 'disipador_max_mm' => 170, 'radiador_max_mm' => 360, 'formato_fuente' => 'ATX', 'bahias_25' => 2, 'bahias_35' => 2], ['watts' => 6, 'antes' => 995]],

            // Enfriamiento
            ['enfriamiento', 'Thermalright', 'Thermalright Peerless Assassin 120 SE', 365, 12, ['tipo' => 'Aire', 'altura_mm' => 155, 'radiador_mm' => 0, 'tdp_max_w' => 245, 'sockets' => $todosLosSockets], ['watts' => 5, 'destacado' => true]],
            ['enfriamiento', 'DeepCool', 'DeepCool AK400', 295, 10, ['tipo' => 'Aire', 'altura_mm' => 155, 'radiador_mm' => 0, 'tdp_max_w' => 220, 'sockets' => $todosLosSockets], ['watts' => 4]],
            ['enfriamiento', 'Noctua', 'Noctua NH-D15 G2', 1250, 2, ['tipo' => 'Aire', 'altura_mm' => 168, 'radiador_mm' => 0, 'tdp_max_w' => 280, 'sockets' => $todosLosSockets], ['watts' => 6, 'garantia' => 72]],
            ['enfriamiento', 'Arctic', 'Arctic Liquid Freezer III 360', 995, 4, ['tipo' => 'Líquida', 'altura_mm' => 0, 'radiador_mm' => 360, 'tdp_max_w' => 350, 'sockets' => $todosLosSockets], ['watts' => 15, 'garantia' => 72]],
            ['enfriamiento', 'Corsair', 'Corsair iCUE H100i ELITE 240', 1190, 3, ['tipo' => 'Líquida', 'altura_mm' => 0, 'radiador_mm' => 240, 'tdp_max_w' => 280, 'sockets' => ['AM4', 'AM5', 'LGA1700']], ['watts' => 12, 'garantia' => 60]],

            // Monitores
            ['monitores', 'LG', 'LG UltraGear 24GS60F 24" FHD 180Hz', 1150, 9, ['tamano_pulgadas' => 23.8, 'resolucion' => '1920 × 1080', 'frecuencia_hz' => 180, 'panel' => 'IPS', 'tiempo_respuesta_ms' => 1], ['destacado' => true, 'antes' => 1290]],
            ['monitores', 'Samsung', 'Samsung Odyssey G5 27" QHD 165Hz', 2190, 4, ['tamano_pulgadas' => 27, 'resolucion' => '2560 × 1440', 'frecuencia_hz' => 165, 'panel' => 'VA', 'tiempo_respuesta_ms' => 1], []],
            ['monitores', 'ASUS', 'ASUS TUF Gaming VG27AQ3A 27" QHD 180Hz', 2050, 5, ['tamano_pulgadas' => 27, 'resolucion' => '2560 × 1440', 'frecuencia_hz' => 180, 'panel' => 'IPS', 'tiempo_respuesta_ms' => 1], []],
            ['monitores', 'Dell', 'Dell S2725QS 27" 4K 120Hz', 2590, 3, ['tamano_pulgadas' => 27, 'resolucion' => '3840 × 2160', 'frecuencia_hz' => 120, 'panel' => 'IPS', 'tiempo_respuesta_ms' => 4], ['garantia' => 36]],

            // Periféricos
            ['mouse', 'Logitech', 'Logitech G502 HERO', 395, 15, [], ['destacado' => true, 'descripcion' => 'Mouse gamer con sensor HERO 25K, 11 botones programables y pesos ajustables.']],
            ['mouse', 'Razer', 'Razer DeathAdder Essential', 195, 12, [], ['descripcion' => 'Mouse ergonómico con sensor óptico de 6400 DPI y 5 botones.']],
            ['teclados', 'Redragon', 'Redragon K552 Kumara', 295, 10, [], ['descripcion' => 'Teclado mecánico TKL con switches rojos y retroiluminación.']],
            ['teclados', 'Logitech', 'Logitech G PRO X TKL', 1290, 3, [], ['descripcion' => 'Teclado mecánico inalámbrico LIGHTSPEED para esports.', 'garantia' => 24]],
            ['audifonos', 'HyperX', 'HyperX Cloud III', 795, 6, [], ['descripcion' => 'Audífonos gamer con micrófono desmontable y drivers de 53 mm.', 'garantia' => 24]],
            ['audifonos', 'Logitech', 'Logitech G435 LIGHTSPEED', 595, 0, [], ['descripcion' => 'Audífonos inalámbricos livianos con Bluetooth y LIGHTSPEED.']],
        ];
    }
}
