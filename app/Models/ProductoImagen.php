<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Table('producto_imagenes')]
#[Fillable(['producto_id', 'ruta', 'orden'])]
class ProductoImagen extends Model
{
    protected static function booted(): void
    {
        static::deleted(fn (self $imagen) => Storage::disk('public')->delete($imagen->ruta));
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
