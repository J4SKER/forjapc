<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DireccionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tienda\CatalogoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tienda pública
|--------------------------------------------------------------------------
*/

Route::view('/', 'tienda.inicio')->name('inicio');
Route::get('/categoria/{slug}', [CatalogoController::class, 'categoria'])->name('tienda.categoria');
Route::get('/buscar', [CatalogoController::class, 'buscar'])->name('tienda.buscar');

Route::view('/arma-tu-pc', 'tienda.proximamente', [
    'titulo' => 'Arma tu PC',
    'icono' => 'herramienta',
    'mensaje' => 'El configurador de PC a la medida estará disponible muy pronto.',
])->name('tienda.configurador');

Route::view('/carrito', 'tienda.proximamente', [
    'titulo' => 'Tu carrito está vacío',
    'icono' => 'carrito',
    'mensaje' => 'Cuando el catálogo esté publicado podrás agregar productos y configuraciones a tu carrito.',
])->name('carrito');

/*
|--------------------------------------------------------------------------
| Cuenta del cliente
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mi-cuenta', [CuentaController::class, 'inicio'])->name('dashboard');

    Route::resource('mi-cuenta/direcciones', DireccionController::class)
        ->except('show')
        ->parameters(['direcciones' => 'direccion'])
        ->names('direcciones');
});

Route::middleware('auth')->group(function () {
    Route::get('/mi-cuenta/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/mi-cuenta/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/mi-cuenta/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Panel administrativo
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'rol'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\InicioController::class)->name('inicio');

    Route::middleware('rol:ADMIN')->group(function () {
        Route::resource('usuarios', Admin\UsuarioController::class)->except(['show', 'destroy']);
        Route::post('usuarios/{usuario}/desbloquear', [Admin\UsuarioController::class, 'desbloquear'])->name('usuarios.desbloquear');

        Route::get('bitacora', [Admin\BitacoraController::class, 'index'])->name('bitacora.index');

        Route::get('parametros', [Admin\ParametroController::class, 'index'])->name('parametros.index');
        Route::put('parametros', [Admin\ParametroController::class, 'update'])->name('parametros.update');
        Route::post('parametros/tipo-cambio', [Admin\ParametroController::class, 'guardarTipoCambio'])->name('parametros.tipo-cambio');
    });
});

require __DIR__.'/auth.php';
