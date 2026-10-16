<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('tipos_cambio')]
#[Fillable(['fecha', 'tasa', 'fuente'])]
class TipoCambio extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'tasa' => 'decimal:5',
        ];
    }

    /**
     * Tipo de cambio vigente: el de la fecha indicada o el último anterior.
     */
    public static function vigente(?string $fecha = null): ?self
    {
        return static::query()
            ->whereDate('fecha', '<=', $fecha ?? today())
            ->latest('fecha')
            ->first();
    }
}
