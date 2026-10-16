<?php

namespace App\Console\Commands;

use App\Services\TipoCambioService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('forjapc:tipo-cambio')]
#[Description('Consulta el tipo de cambio del día en el Banco de Guatemala y lo guarda')]
class SincronizarTipoCambio extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TipoCambioService $tipoCambio): int
    {
        $tasa = $tipoCambio->sincronizarBanguat();

        if ($tasa === null) {
            $this->warn('No se pudo obtener el tipo de cambio; se usará el último registrado.');

            return self::FAILURE;
        }

        $this->info("Tipo de cambio del día: Q{$tasa} por US$1");

        return self::SUCCESS;
    }
}
