<?php

namespace App\Support\Configurador;

use App\Enums\TipoComponente;

/**
 * Resultado de una regla de compatibilidad: un error impide comprar la configuración,
 * una advertencia solo se informa.
 */
final readonly class Hallazgo
{
    public const ERROR = 'error';

    public const ADVERTENCIA = 'advertencia';

    /**
     * @param  list<TipoComponente>  $excluye  Tipos cuyos candidatos se ocultan si producen este error.
     */
    public function __construct(
        public string $codigo,
        public string $nivel,
        public string $titulo,
        public string $mensaje,
        public array $excluye = [],
    ) {}

    public function esError(): bool
    {
        return $this->nivel === self::ERROR;
    }

    /**
     * @return array{codigo: string, nivel: string, titulo: string, mensaje: string}
     */
    public function toArray(): array
    {
        return ['codigo' => $this->codigo, 'nivel' => $this->nivel, 'titulo' => $this->titulo, 'mensaje' => $this->mensaje];
    }
}
