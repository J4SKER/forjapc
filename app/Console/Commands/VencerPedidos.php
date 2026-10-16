<?php

namespace App\Console\Commands;

use App\Services\PedidoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('forjapc:vencer-pedidos')]
#[Description('Cancela los pedidos no pagados a tiempo y libera sus reservas de inventario')]
class VencerPedidos extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PedidoService $pedidos): int
    {
        $cantidad = $pedidos->vencerPendientes();
        $this->info("Pedidos vencidos: {$cantidad}");

        return self::SUCCESS;
    }
}
