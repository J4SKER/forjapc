<?php

namespace App\Support;

use App\Enums\TipoComponente;
use Illuminate\Validation\Rule;

/**
 * Definición de los campos técnicos de cada tipo de componente. Es la única fuente
 * para dibujar el formulario del panel y para validar lo que se guarda.
 */
class CamposEspecificacion
{
    /**
     * @return array<string, array{etiqueta: string, tipo: string, opciones?: list<string>, reglas: list<mixed>}>
     */
    public static function de(TipoComponente $tipo): array
    {
        $memoria = ['DDR4', 'DDR5'];
        $formatos = ['Mini-ITX', 'Micro-ATX', 'ATX', 'E-ATX'];

        return match ($tipo) {
            TipoComponente::Cpu => [
                'socket_id' => self::socket(),
                'nucleos' => self::entero('Núcleos', 1, 128),
                'hilos' => self::entero('Hilos', 1, 256),
                'frecuencia_base_ghz' => self::decimal('Frecuencia base (GHz)', 0.5, 9.9),
                'frecuencia_turbo_ghz' => self::decimal('Frecuencia turbo (GHz)', 0.5, 9.9, false),
                'tdp_w' => self::entero('TDP (W)', 1, 500),
                'tipo_memoria' => self::opcion('Memoria compatible', ['DDR4', 'DDR5', 'DDR4/DDR5']),
                'graficos_integrados' => self::booleano('Gráficos integrados'),
                'incluye_disipador' => self::booleano('Incluye disipador'),
            ],
            TipoComponente::Placa => [
                'socket_id' => self::socket(),
                'chipset' => self::texto('Chipset', 20),
                'formato' => self::opcion('Formato', $formatos),
                'tipo_memoria' => self::opcion('Tipo de memoria', $memoria),
                'ranuras_ram' => self::entero('Ranuras de RAM', 1, 8),
                'ram_max_gb' => self::entero('RAM máxima (GB)', 8, 1024),
                'slots_m2' => self::entero('Ranuras M.2', 0, 8),
                'puertos_sata' => self::entero('Puertos SATA', 0, 12),
                'wifi' => self::booleano('Wi-Fi integrado'),
            ],
            TipoComponente::Ram => [
                'tipo' => self::opcion('Tipo', $memoria),
                'capacidad_total_gb' => self::entero('Capacidad total (GB)', 1, 512),
                'modulos' => self::entero('Módulos', 1, 8),
                'velocidad_mhz' => self::entero('Velocidad (MHz)', 800, 12000),
                'latencia_cl' => self::entero('Latencia CL', 5, 60, false),
            ],
            TipoComponente::Gpu => [
                'chipset' => self::texto('Chipset', 40),
                'vram_gb' => self::entero('Memoria de video (GB)', 1, 64),
                'longitud_mm' => self::entero('Longitud (mm)', 100, 450),
                'tdp_w' => self::entero('Consumo TDP (W)', 10, 1000),
                'fuente_recomendada_w' => self::entero('Fuente recomendada (W)', 200, 2000),
                'conectores_energia' => self::texto('Conectores de energía', 40),
            ],
            TipoComponente::Almacenamiento => [
                'tipo' => self::opcion('Tipo', ['SSD NVMe', 'SSD SATA', 'HDD']),
                'formato' => self::opcion('Formato', ['M.2', '2.5"', '3.5"']),
                'capacidad_gb' => self::entero('Capacidad (GB)', 16, 100000),
                'lectura_mbps' => self::entero('Lectura (MB/s)', 1, 20000, false),
                'escritura_mbps' => self::entero('Escritura (MB/s)', 1, 20000, false),
            ],
            TipoComponente::Fuente => [
                'potencia_w' => self::entero('Potencia (W)', 200, 3000),
                'certificacion' => self::texto('Certificación', 30),
                'modular' => self::opcion('Modular', ['No', 'Semi', 'Total']),
                'formato' => self::opcion('Formato', ['ATX', 'SFX']),
            ],
            TipoComponente::Gabinete => [
                'formato_max_placa' => self::opcion('Placa máxima', $formatos),
                'gpu_max_mm' => self::entero('Largo máx. de tarjeta de video (mm)', 100, 600),
                'disipador_max_mm' => self::entero('Altura máx. de disipador (mm)', 30, 250),
                'radiador_max_mm' => self::entero('Radiador máximo (mm, 0 = no admite)', 0, 480),
                'formato_fuente' => self::opcion('Formato de fuente', ['ATX', 'SFX']),
                'bahias_25' => self::entero('Bahías 2.5"', 0, 12),
                'bahias_35' => self::entero('Bahías 3.5"', 0, 12),
            ],
            TipoComponente::Disipador => [
                'tipo' => self::opcion('Tipo', ['Aire', 'Líquida']),
                'altura_mm' => self::entero('Altura (mm, solo aire)', 0, 250),
                'radiador_mm' => self::entero('Radiador (mm, solo líquida)', 0, 480),
                'tdp_max_w' => self::entero('Disipa hasta (W)', 30, 500),
                'sockets' => ['etiqueta' => 'Sockets compatibles', 'tipo' => 'sockets', 'reglas' => ['required', 'array', 'min:1']],
            ],
            TipoComponente::Monitor => [
                'tamano_pulgadas' => self::decimal('Tamaño (pulgadas)', 10, 60),
                'resolucion' => self::texto('Resolución', 20),
                'frecuencia_hz' => self::entero('Frecuencia (Hz)', 30, 600),
                'panel' => self::opcion('Panel', ['IPS', 'VA', 'TN', 'OLED']),
                'tiempo_respuesta_ms' => self::decimal('Tiempo de respuesta (ms)', 0.1, 50),
            ],
        };
    }

    /**
     * Reglas de validación con el prefijo del arreglo del formulario.
     *
     * @return array<string, list<mixed>>
     */
    public static function reglas(TipoComponente $tipo, string $prefijo = 'esp'): array
    {
        $reglas = [];
        foreach (self::de($tipo) as $campo => $definicion) {
            $reglas["{$prefijo}.{$campo}"] = $definicion['reglas'];
            if ($definicion['tipo'] === 'sockets') {
                $reglas["{$prefijo}.{$campo}.*"] = ['integer', Rule::exists('sockets', 'id')];
            }
        }

        return $reglas;
    }

    /**
     * @return array{etiqueta: string, tipo: string, reglas: list<mixed>}
     */
    private static function socket(): array
    {
        return ['etiqueta' => 'Socket', 'tipo' => 'socket', 'reglas' => ['required', 'integer', Rule::exists('sockets', 'id')]];
    }

    /**
     * @return array{etiqueta: string, tipo: string, reglas: list<mixed>}
     */
    private static function entero(string $etiqueta, int $min, int $max, bool $requerido = true): array
    {
        return ['etiqueta' => $etiqueta, 'tipo' => 'entero', 'reglas' => [$requerido ? 'required' : 'nullable', 'integer', "min:{$min}", "max:{$max}"]];
    }

    /**
     * @return array{etiqueta: string, tipo: string, reglas: list<mixed>}
     */
    private static function decimal(string $etiqueta, float $min, float $max, bool $requerido = true): array
    {
        return ['etiqueta' => $etiqueta, 'tipo' => 'decimal', 'reglas' => [$requerido ? 'required' : 'nullable', 'numeric', "min:{$min}", "max:{$max}"]];
    }

    /**
     * @return array{etiqueta: string, tipo: string, reglas: list<mixed>}
     */
    private static function texto(string $etiqueta, int $max): array
    {
        return ['etiqueta' => $etiqueta, 'tipo' => 'texto', 'reglas' => ['required', 'string', "max:{$max}"]];
    }

    /**
     * @param  list<string>  $opciones
     * @return array{etiqueta: string, tipo: string, opciones: list<string>, reglas: list<mixed>}
     */
    private static function opcion(string $etiqueta, array $opciones): array
    {
        return ['etiqueta' => $etiqueta, 'tipo' => 'opcion', 'opciones' => $opciones, 'reglas' => ['required', Rule::in($opciones)]];
    }

    /**
     * @return array{etiqueta: string, tipo: string, reglas: list<mixed>}
     */
    private static function booleano(string $etiqueta): array
    {
        return ['etiqueta' => $etiqueta, 'tipo' => 'booleano', 'reglas' => ['boolean']];
    }
}
