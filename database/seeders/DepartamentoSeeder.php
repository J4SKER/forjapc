<?php

namespace Database\Seeders;

use App\Models\Departamento;
use Illuminate\Database\Seeder;

class DepartamentoSeeder extends Seeder
{
    /**
     * Los 22 departamentos de Guatemala con tarifa de envío (Q) y días estimados de entrega.
     */
    public function run(): void
    {
        $departamentos = [
            ['Guatemala', 25, 1], ['Sacatepéquez', 35, 2], ['Chimaltenango', 35, 2], ['Escuintla', 40, 2],
            ['El Progreso', 40, 2], ['Santa Rosa', 40, 2], ['Jalapa', 45, 3], ['Jutiapa', 45, 3],
            ['Baja Verapaz', 45, 3], ['Alta Verapaz', 50, 3], ['Chiquimula', 45, 3], ['Zacapa', 45, 3],
            ['Izabal', 55, 3], ['Petén', 60, 4], ['Quiché', 50, 3], ['Sololá', 45, 3],
            ['Totonicapán', 45, 3], ['Quetzaltenango', 45, 3], ['San Marcos', 50, 3], ['Huehuetenango', 55, 4],
            ['Retalhuleu', 50, 3], ['Suchitepéquez', 45, 3],
        ];

        foreach ($departamentos as [$nombre, $tarifa, $dias]) {
            Departamento::updateOrCreate(['nombre' => $nombre], ['tarifa_envio' => $tarifa, 'dias_entrega' => $dias]);
        }
    }
}
