<?php

namespace App\Services;

use App\Enums\TipoComponente as T;
use App\Models\Producto;
use App\Support\Configurador\Configurador;
use App\Support\Configurador\Hallazgo;
use App\Support\Configurador\Seleccion;

/**
 * Motor de compatibilidad del configurador de PC: 18 reglas que se informan como
 * errores (impiden comprar) o advertencias, más el cálculo de consumo eléctrico y
 * de la potencia de fuente recomendada.
 */
class CompatibilidadService
{
    /**
     * Consumo base de ventiladores, USB y otros periféricos internos (W).
     */
    private const CONSUMO_BASE = 50;

    /**
     * Margen sobre el consumo estimado para recomendar la fuente.
     */
    private const MARGEN_FUENTE = 1.3;

    private const ORDEN_FORMATOS = ['Mini-ITX' => 1, 'Micro-ATX' => 2, 'ATX' => 3, 'E-ATX' => 4];

    /**
     * Descripción de las reglas para mostrarlas en la ayuda del configurador.
     */
    public const REGLAS = [
        'R01' => 'El socket del procesador coincide con el de la tarjeta madre.',
        'R02' => 'La memoria RAM es del tipo que admite la tarjeta madre.',
        'R03' => 'La memoria RAM es del tipo que admite el procesador.',
        'R04' => 'Los módulos de RAM caben en las ranuras de la tarjeta madre.',
        'R05' => 'La RAM total no supera la capacidad máxima de la tarjeta madre.',
        'R06' => 'La tarjeta madre cabe en el gabinete.',
        'R07' => 'La tarjeta de video cabe a lo largo del gabinete.',
        'R08' => 'Si el procesador no tiene gráficos integrados, se necesita tarjeta de video.',
        'R09' => 'Si el procesador no incluye disipador, se necesita uno.',
        'R10' => 'El disipador es compatible con el socket del procesador.',
        'R11' => 'El disipador de aire cabe en altura dentro del gabinete.',
        'R12' => 'El radiador del enfriamiento líquido cabe en el gabinete.',
        'R13' => 'El disipador soporta el calor (TDP) del procesador.',
        'R14' => 'La fuente cubre el consumo estimado del equipo.',
        'R15' => 'La fuente tiene el margen recomendado y cumple lo que pide la tarjeta de video.',
        'R16' => 'El formato de la fuente (ATX o SFX) es compatible con el gabinete.',
        'R17' => 'Las unidades M.2 caben en las ranuras M.2 de la tarjeta madre.',
        'R18' => 'Hay puertos SATA y bahías suficientes para los discos.',
    ];

    /**
     * @return list<Hallazgo>
     */
    public function evaluar(Seleccion $s): array
    {
        return array_values(array_filter([
            $this->socketCpuPlaca($s),
            $this->memoriaPlaca($s),
            $this->memoriaCpu($s),
            $this->ranurasRam($s),
            $this->capacidadRam($s),
            $this->formatoPlacaGabinete($s),
            $this->largoGpu($s),
            $this->requiereGpu($s),
            $this->requiereDisipador($s),
            $this->socketDisipador($s),
            $this->alturaDisipador($s),
            $this->radiador($s),
            $this->capacidadTermica($s),
            $this->potenciaMinima($s),
            $this->potenciaRecomendada($s),
            $this->formatoFuente($s),
            $this->ranurasM2($s),
            $this->sataYBahias($s),
        ]));
    }

    /**
     * Componentes obligatorios que todavía faltan.
     *
     * @return list<T>
     */
    public function faltantes(Seleccion $s): array
    {
        return array_values(array_filter(Configurador::OBLIGATORIOS, fn (T $t) => ! $s->tiene($t)));
    }

    /**
     * Productos seleccionados sin unidades disponibles suficientes.
     *
     * @return list<Producto>
     */
    public function sinExistencia(Seleccion $s): array
    {
        return $s->lineas()
            ->filter(fn ($l) => $l['producto']->disponible() < $l['cantidad'] || ! $l['producto']->activo)
            ->map(fn ($l) => $l['producto'])
            ->values()
            ->all();
    }

    public function puedeComprarse(Seleccion $s): bool
    {
        return $this->faltantes($s) === []
            && $this->sinExistencia($s) === []
            && collect($this->evaluar($s))->doesntContain(fn (Hallazgo $h) => $h->esError());
    }

    /**
     * Indica si un candidato es compatible con lo ya elegido (para filtrar la lista del paso).
     */
    public function esCompatible(Seleccion $s, Producto $candidato): bool
    {
        $tipo = $candidato->tipoComponente();

        return collect($this->evaluar($s->con($candidato)))
            ->doesntContain(fn (Hallazgo $h) => $h->esError() && in_array($tipo, $h->excluye, true));
    }

    public function consumoEstimado(Seleccion $s): int
    {
        if ($s->vacia()) {
            return 0;
        }

        return self::CONSUMO_BASE + (int) $s->lineas()
            ->reject(fn ($l) => $l['tipo'] === T::Fuente)
            ->sum(fn ($l) => $l['producto']->consumo_watts * $l['cantidad']);
    }

    public function fuenteRecomendada(Seleccion $s): int
    {
        if ($s->vacia()) {
            return 0;
        }

        $porMargen = (int) (ceil($this->consumoEstimado($s) * self::MARGEN_FUENTE / 50) * 50);
        $porGpu = (int) ($s->producto(T::Gpu)?->especificacionGpu?->fuente_recomendada_w ?? 0);

        return max($porMargen, $porGpu);
    }

    private function socketCpuPlaca(Seleccion $s): ?Hallazgo
    {
        $cpu = $s->producto(T::Cpu)?->especificacionCpu;
        $placa = $s->producto(T::Placa)?->especificacionPlaca;

        if (! $cpu || ! $placa || $cpu->socket_id === $placa->socket_id) {
            return null;
        }

        return $this->error('R01', 'Socket incompatible', "El procesador usa socket {$cpu->socket->nombre} y la tarjeta madre {$placa->socket->nombre}.", [T::Cpu, T::Placa]);
    }

    private function memoriaPlaca(Seleccion $s): ?Hallazgo
    {
        $ram = $s->producto(T::Ram)?->especificacionRam;
        $placa = $s->producto(T::Placa)?->especificacionPlaca;

        if (! $ram || ! $placa || $ram->tipo === $placa->tipo_memoria) {
            return null;
        }

        return $this->error('R02', 'Memoria incompatible con la tarjeta madre', "La tarjeta madre usa {$placa->tipo_memoria} y la memoria elegida es {$ram->tipo}.", [T::Ram, T::Placa]);
    }

    private function memoriaCpu(Seleccion $s): ?Hallazgo
    {
        $ram = $s->producto(T::Ram)?->especificacionRam;
        $cpu = $s->producto(T::Cpu)?->especificacionCpu;

        if (! $ram || ! $cpu || str_contains($cpu->tipo_memoria, $ram->tipo)) {
            return null;
        }

        return $this->error('R03', 'Memoria incompatible con el procesador', "El procesador solo admite {$cpu->tipo_memoria} y la memoria elegida es {$ram->tipo}.", [T::Ram, T::Cpu]);
    }

    private function ranurasRam(Seleccion $s): ?Hallazgo
    {
        $ram = $s->producto(T::Ram)?->especificacionRam;
        $placa = $s->producto(T::Placa)?->especificacionPlaca;

        if (! $ram || ! $placa) {
            return null;
        }

        $modulos = $ram->modulos * $s->cantidad(T::Ram);

        return $modulos > $placa->ranuras_ram
            ? $this->error('R04', 'No hay ranuras suficientes', "Elegiste {$modulos} módulos de RAM y la tarjeta madre tiene {$placa->ranuras_ram} ranuras.", [T::Ram, T::Placa])
            : null;
    }

    private function capacidadRam(Seleccion $s): ?Hallazgo
    {
        $ram = $s->producto(T::Ram)?->especificacionRam;
        $placa = $s->producto(T::Placa)?->especificacionPlaca;

        if (! $ram || ! $placa) {
            return null;
        }

        $total = $ram->capacidad_total_gb * $s->cantidad(T::Ram);

        return $total > $placa->ram_max_gb
            ? $this->error('R05', 'Demasiada memoria para la tarjeta madre', "La tarjeta madre admite hasta {$placa->ram_max_gb} GB y elegiste {$total} GB.", [T::Ram, T::Placa])
            : null;
    }

    private function formatoPlacaGabinete(Seleccion $s): ?Hallazgo
    {
        $placa = $s->producto(T::Placa)?->especificacionPlaca;
        $gabinete = $s->producto(T::Gabinete)?->especificacionGabinete;

        if (! $placa || ! $gabinete || self::ORDEN_FORMATOS[$placa->formato] <= self::ORDEN_FORMATOS[$gabinete->formato_max_placa]) {
            return null;
        }

        return $this->error('R06', 'La tarjeta madre no cabe', "La tarjeta madre es {$placa->formato} y el gabinete admite hasta {$gabinete->formato_max_placa}.", [T::Placa, T::Gabinete]);
    }

    private function largoGpu(Seleccion $s): ?Hallazgo
    {
        $gpu = $s->producto(T::Gpu)?->especificacionGpu;
        $gabinete = $s->producto(T::Gabinete)?->especificacionGabinete;

        if (! $gpu || ! $gabinete || $gpu->longitud_mm <= $gabinete->gpu_max_mm) {
            return null;
        }

        return $this->error('R07', 'La tarjeta de video no cabe', "La tarjeta mide {$gpu->longitud_mm} mm y el gabinete admite hasta {$gabinete->gpu_max_mm} mm.", [T::Gpu, T::Gabinete]);
    }

    private function requiereGpu(Seleccion $s): ?Hallazgo
    {
        $cpu = $s->producto(T::Cpu)?->especificacionCpu;

        if (! $cpu || $cpu->graficos_integrados || $s->tiene(T::Gpu)) {
            return null;
        }

        return $this->error('R08', 'Falta tarjeta de video', 'El procesador no tiene gráficos integrados: sin tarjeta de video la PC no dará imagen.');
    }

    private function requiereDisipador(Seleccion $s): ?Hallazgo
    {
        $cpu = $s->producto(T::Cpu)?->especificacionCpu;

        if (! $cpu || $cpu->incluye_disipador || $s->tiene(T::Disipador)) {
            return null;
        }

        return $this->error('R09', 'Falta disipador', 'El procesador no incluye disipador de fábrica: elige uno en el paso de enfriamiento.');
    }

    private function socketDisipador(Seleccion $s): ?Hallazgo
    {
        $disipador = $s->producto(T::Disipador)?->especificacionDisipador;
        $cpu = $s->producto(T::Cpu)?->especificacionCpu;

        if (! $disipador || ! $cpu || $disipador->sockets->contains('id', $cpu->socket_id)) {
            return null;
        }

        return $this->error('R10', 'Disipador incompatible con el socket', "El disipador no trae soporte para el socket {$cpu->socket->nombre}.", [T::Disipador, T::Cpu]);
    }

    private function alturaDisipador(Seleccion $s): ?Hallazgo
    {
        $disipador = $s->producto(T::Disipador)?->especificacionDisipador;
        $gabinete = $s->producto(T::Gabinete)?->especificacionGabinete;

        if (! $disipador || ! $gabinete || $disipador->tipo !== 'Aire' || $disipador->altura_mm <= $gabinete->disipador_max_mm) {
            return null;
        }

        return $this->error('R11', 'El disipador es muy alto', "El disipador mide {$disipador->altura_mm} mm y el gabinete admite hasta {$gabinete->disipador_max_mm} mm.", [T::Disipador, T::Gabinete]);
    }

    private function radiador(Seleccion $s): ?Hallazgo
    {
        $disipador = $s->producto(T::Disipador)?->especificacionDisipador;
        $gabinete = $s->producto(T::Gabinete)?->especificacionGabinete;

        if (! $disipador || ! $gabinete || $disipador->tipo !== 'Líquida' || $disipador->radiador_mm <= $gabinete->radiador_max_mm) {
            return null;
        }

        $mensaje = $gabinete->radiador_max_mm === 0
            ? 'El gabinete no admite radiadores de enfriamiento líquido.'
            : "El radiador es de {$disipador->radiador_mm} mm y el gabinete admite hasta {$gabinete->radiador_max_mm} mm.";

        return $this->error('R12', 'El radiador no cabe', $mensaje, [T::Disipador, T::Gabinete]);
    }

    private function capacidadTermica(Seleccion $s): ?Hallazgo
    {
        $disipador = $s->producto(T::Disipador)?->especificacionDisipador;
        $cpu = $s->producto(T::Cpu)?->especificacionCpu;

        if (! $disipador || ! $cpu || $disipador->tdp_max_w >= $cpu->tdp_w) {
            return null;
        }

        return $this->advertencia('R13', 'Disipador justo para el procesador', "El disipador está diseñado para {$disipador->tdp_max_w} W y el procesador tiene un TDP de {$cpu->tdp_w} W; podría calentarse bajo carga.");
    }

    private function potenciaMinima(Seleccion $s): ?Hallazgo
    {
        $fuente = $s->producto(T::Fuente)?->especificacionFuente;
        $consumo = $this->consumoEstimado($s);

        if (! $fuente || $fuente->potencia_w >= $consumo) {
            return null;
        }

        return $this->error('R14', 'La fuente es insuficiente', "El equipo consume unos {$consumo} W y la fuente entrega {$fuente->potencia_w} W.", [T::Fuente]);
    }

    private function potenciaRecomendada(Seleccion $s): ?Hallazgo
    {
        $fuente = $s->producto(T::Fuente)?->especificacionFuente;
        $recomendada = $this->fuenteRecomendada($s);

        if (! $fuente || $fuente->potencia_w >= $recomendada || $fuente->potencia_w < $this->consumoEstimado($s)) {
            return null;
        }

        return $this->advertencia('R15', 'Fuente con poco margen', "Funciona, pero recomendamos al menos {$recomendada} W para trabajar con holgura y soportar picos de consumo.");
    }

    private function formatoFuente(Seleccion $s): ?Hallazgo
    {
        $fuente = $s->producto(T::Fuente)?->especificacionFuente;
        $gabinete = $s->producto(T::Gabinete)?->especificacionGabinete;

        if (! $fuente || ! $gabinete || $fuente->formato === $gabinete->formato_fuente) {
            return null;
        }

        if ($gabinete->formato_fuente === 'SFX') {
            return $this->error('R16', 'La fuente no cabe', 'El gabinete solo admite fuentes SFX y la elegida es ATX.', [T::Fuente, T::Gabinete]);
        }

        return $this->advertencia('R16', 'Fuente SFX en gabinete ATX', 'La fuente SFX cabe en el gabinete, pero puede requerir un adaptador de montaje.');
    }

    private function ranurasM2(Seleccion $s): ?Hallazgo
    {
        $placa = $s->producto(T::Placa)?->especificacionPlaca;

        if (! $placa) {
            return null;
        }

        $m2 = $this->unidades($s, fn ($e) => $e->formato === 'M.2');

        return $m2 > $placa->slots_m2
            ? $this->error('R17', 'Faltan ranuras M.2', "Elegiste {$m2} unidades M.2 y la tarjeta madre tiene {$placa->slots_m2} ranuras.", [T::Almacenamiento, T::Placa])
            : null;
    }

    private function sataYBahias(Seleccion $s): ?Hallazgo
    {
        $placa = $s->producto(T::Placa)?->especificacionPlaca;
        $gabinete = $s->producto(T::Gabinete)?->especificacionGabinete;
        $sata = $this->unidades($s, fn ($e) => $e->tipo !== 'SSD NVMe');
        $discos35 = $this->unidades($s, fn ($e) => $e->formato === '3.5"');
        $discos25 = $this->unidades($s, fn ($e) => $e->formato === '2.5"');

        if ($placa && $sata > $placa->puertos_sata) {
            return $this->error('R18', 'Faltan puertos SATA', "Elegiste {$sata} unidades SATA y la tarjeta madre tiene {$placa->puertos_sata} puertos.", [T::Almacenamiento, T::Placa]);
        }

        if ($gabinete && $discos35 > $gabinete->bahias_35) {
            return $this->error('R18', 'Faltan bahías de 3.5"', "El gabinete tiene {$gabinete->bahias_35} bahías de 3.5\" para {$discos35} disco(s) duro(s).", [T::Almacenamiento, T::Gabinete]);
        }

        if ($gabinete && $discos25 > $gabinete->bahias_25 + max(0, $gabinete->bahias_35 - $discos35)) {
            return $this->advertencia('R18', 'Pocas bahías de 2.5"', 'Las unidades de 2.5" podrían necesitar un soporte adicional en este gabinete.');
        }

        return null;
    }

    /**
     * Suma las unidades de almacenamiento que cumplen la condición.
     */
    private function unidades(Seleccion $s, callable $condicion): int
    {
        return (int) collect($s->de(T::Almacenamiento))
            ->filter(fn ($l) => $l['producto']->especificacionAlmacenamiento && $condicion($l['producto']->especificacionAlmacenamiento))
            ->sum('cantidad');
    }

    /**
     * @param  list<T>  $excluye
     */
    private function error(string $codigo, string $titulo, string $mensaje, array $excluye = []): Hallazgo
    {
        return new Hallazgo($codigo, Hallazgo::ERROR, $titulo, $mensaje, $excluye);
    }

    private function advertencia(string $codigo, string $titulo, string $mensaje): Hallazgo
    {
        return new Hallazgo($codigo, Hallazgo::ADVERTENCIA, $titulo, $mensaje);
    }
}
