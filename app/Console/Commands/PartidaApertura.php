<?php

namespace App\Console\Commands;

use App\Models\CuentaContable;
use App\Services\ContabilidadService;
use Database\Seeders\NomenclaturaSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('forjapc:partida-apertura')]
#[Description('Registra la partida de apertura con el valor del inventario que aún no está en contabilidad')]
class PartidaApertura extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ContabilidadService $contabilidad): int
    {
        $this->call('db:seed', ['--class' => NomenclaturaSeeder::class, '--force' => true]);

        $valorInventario = round((float) DB::table('v_existencias')->sum('valor'), 2);
        $cuenta = CuentaContable::query()->where('codigo', NomenclaturaSeeder::INVENTARIO)->firstOrFail();
        $saldoContable = round((float) DB::table('v_libro_mayor')->where('cuenta_id', $cuenta->id)->sum('efecto'), 2);
        $diferencia = round($valorInventario - $saldoContable, 2);

        if ($diferencia <= 0.01) {
            $this->info("El inventario (Q{$valorInventario}) ya está registrado en contabilidad.");

            return self::SUCCESS;
        }

        $partida = $contabilidad->apertura($diferencia);
        $this->info("Partida de apertura {$partida->referencia()} por Q{$diferencia}.");

        return self::SUCCESS;
    }
}
