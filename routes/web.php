<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DireccionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tienda\CatalogoController;
use App\Http\Controllers\Tienda\InicioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tienda pública
|--------------------------------------------------------------------------
*/

Route::get('/', InicioController::class)->name('inicio');
Route::get('/categoria/{slug}', [CatalogoController::class, 'categoria'])->name('tienda.categoria');
Route::get('/buscar', [CatalogoController::class, 'buscar'])->name('tienda.buscar');
Route::get('/producto/{slug}', [CatalogoController::class, 'producto'])->name('tienda.producto');

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

    Route::post('vista-previa', [Admin\VistaPreviaController::class, 'store'])->name('vista-previa.store');
    Route::delete('vista-previa', [Admin\VistaPreviaController::class, 'destroy'])->name('vista-previa.destroy');

    // Consulta de inventario y compras (contabilidad incluida)
    Route::middleware('rol:ADMIN,VENDEDOR,CONTADOR')->group(function () {
        Route::get('inventario', [Admin\InventarioController::class, 'index'])->name('inventario.index');
        Route::get('inventario/{producto}/kardex', [Admin\InventarioController::class, 'kardex'])->name('inventario.kardex');
        Route::get('compras', [Admin\CompraController::class, 'index'])->name('compras.index');
        Route::get('compras/{compra}', [Admin\CompraController::class, 'show'])->name('compras.show')->whereNumber('compra');
    });

    // Catálogo, compras e inventario (gestión)
    Route::middleware('rol:ADMIN,VENDEDOR')->group(function () {
        Route::resource('productos', Admin\ProductoController::class)->except('show');
        Route::delete('productos/{producto}/imagenes/{imagen}', [Admin\ProductoController::class, 'eliminarImagen'])->name('productos.imagenes.destroy');
        Route::resource('categorias', Admin\CategoriaController::class)->only(['index', 'store', 'update'])->parameters(['categorias' => 'categoria']);
        Route::resource('marcas', Admin\MarcaController::class)->only(['index', 'store', 'update']);
        Route::get('resenas', [Admin\ResenaController::class, 'index'])->name('resenas.index');
        Route::put('resenas/{resena}', [Admin\ResenaController::class, 'update'])->name('resenas.update');

        Route::resource('proveedores', Admin\ProveedorController::class)->except(['show', 'destroy'])->parameters(['proveedores' => 'proveedor']);
        Route::resource('compras', Admin\CompraController::class)->only(['create', 'store', 'edit', 'update']);
        Route::post('compras/{compra}/recibir', [Admin\CompraController::class, 'recibir'])->name('compras.recibir');
        Route::post('compras/{compra}/anular', [Admin\CompraController::class, 'anular'])->name('compras.anular');
        Route::post('inventario/{producto}/ajuste', [Admin\InventarioController::class, 'ajustar'])->name('inventario.ajustar');
    });

    Route::middleware('rol:ADMIN')->group(function () {
        Route::get('usuarios/exportar', [Admin\UsuarioController::class, 'exportar'])->name('usuarios.exportar');
        Route::resource('usuarios', Admin\UsuarioController::class)->except(['show', 'destroy']);
        Route::post('usuarios/{usuario}/desbloquear', [Admin\UsuarioController::class, 'desbloquear'])->name('usuarios.desbloquear');

        Route::get('bitacora', [Admin\BitacoraController::class, 'index'])->name('bitacora.index');

        Route::get('parametros', [Admin\ParametroController::class, 'index'])->name('parametros.index');
        Route::put('parametros', [Admin\ParametroController::class, 'update'])->name('parametros.update');
        Route::post('parametros/tipo-cambio', [Admin\ParametroController::class, 'guardarTipoCambio'])->name('parametros.tipo-cambio');
    });
});

require __DIR__.'/auth.php';
