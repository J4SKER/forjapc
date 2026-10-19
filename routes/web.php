<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DireccionController;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tienda\CatalogoController;
use App\Http\Controllers\Tienda\CheckoutController;
use App\Http\Controllers\Tienda\InicioController;
use App\Http\Controllers\Tienda\ResenaController as ResenaTiendaController;
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

Route::get('/arma-tu-pc', [ConfiguracionController::class, 'configurador'])->name('tienda.configurador');
Route::get('/arma-tu-pc/c/{configuracion}', [ConfiguracionController::class, 'ver'])->name('tienda.configurador.ver');

Route::view('/carrito', 'tienda.carrito')->name('carrito');

Route::post('/webhooks/paypal', [PaypalController::class, 'webhook'])->name('webhooks.paypal');

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

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:acciones-cliente')->name('checkout.store');

    Route::get('/mi-cuenta/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/mi-cuenta/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    Route::post('/mi-cuenta/pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelar'])->name('pedidos.cancelar');
    Route::post('/mi-cuenta/pedidos/{pedido}/transferencia', [PedidoController::class, 'transferencia'])->middleware('throttle:acciones-cliente')->name('pedidos.transferencia');
    Route::post('/mi-cuenta/pedidos/{pedido}/paypal/orden', [PaypalController::class, 'crearOrden'])->middleware('throttle:pagos-paypal')->name('pedidos.paypal.orden');
    Route::post('/mi-cuenta/pedidos/{pedido}/paypal/capturar', [PaypalController::class, 'capturar'])->middleware('throttle:pagos-paypal')->name('pedidos.paypal.capturar');
    Route::get('/pagos/{pago}/comprobante', [PedidoController::class, 'comprobante'])->name('pagos.comprobante');

    Route::post('/producto/{slug}/resenas', [ResenaTiendaController::class, 'store'])->middleware('throttle:acciones-cliente')->name('tienda.resenas.store');

    Route::get('/mi-cuenta/configuraciones', [ConfiguracionController::class, 'index'])->name('configuraciones.index');
    Route::delete('/mi-cuenta/configuraciones/{configuracion}', [ConfiguracionController::class, 'destroy'])->name('configuraciones.destroy');
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

    // Pedidos y pagos: consulta (contabilidad incluida) y gestión (ventas)
    Route::middleware('rol:ADMIN,VENDEDOR,CONTADOR')->group(function () {
        Route::get('pedidos', [Admin\PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('pedidos/{pedido}', [Admin\PedidoController::class, 'show'])->name('pedidos.show');
        Route::get('pagos', [Admin\PagoController::class, 'index'])->name('pagos.index');
    });

    Route::middleware('rol:ADMIN,VENDEDOR')->group(function () {
        Route::post('pedidos/{pedido}/estado', [Admin\PedidoController::class, 'cambiarEstado'])->name('pedidos.estado');
        Route::post('pedidos/{pedido}/envio', [Admin\PedidoController::class, 'envio'])->name('pedidos.envio');
        Route::post('pedidos/{pedido}/cancelar', [Admin\PedidoController::class, 'cancelar'])->name('pedidos.cancelar');
        Route::post('pedidos/{pedido}/pagos/{pago}/reembolso', [Admin\PedidoController::class, 'reembolsar'])->middleware('confirmar.password')->name('pedidos.reembolsar');
        Route::post('pagos/{pago}/aprobar', [Admin\PagoController::class, 'aprobar'])->name('pagos.aprobar');
        Route::post('pagos/{pago}/rechazar', [Admin\PagoController::class, 'rechazar'])->name('pagos.rechazar');
    });

    // Reportes de ventas e inventario (ventas, contabilidad y administración)
    Route::middleware('rol:ADMIN,VENDEDOR,CONTADOR')->prefix('reportes')->name('reportes.')->group(function () {
        Route::get('ventas', [Admin\ReporteController::class, 'ventas'])->name('ventas');
        Route::get('inventario', [Admin\ReporteController::class, 'inventario'])->name('inventario');
    });

    // Contabilidad y estados financieros (contabilidad y administración)
    Route::middleware('rol:ADMIN,CONTADOR')->group(function () {
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('libro-diario', [Admin\ReporteController::class, 'libroDiario'])->name('libro-diario');
            Route::get('libro-mayor', [Admin\ReporteController::class, 'libroMayor'])->name('libro-mayor');
            Route::get('balance-comprobacion', [Admin\ReporteController::class, 'balance'])->name('balance');
            Route::get('estado-resultados', [Admin\ReporteController::class, 'resultados'])->name('resultados');
        });

        Route::prefix('contabilidad')->name('contabilidad.')->group(function () {
            Route::get('partidas', [Admin\ContabilidadController::class, 'partidas'])->name('partidas.index');
            Route::get('partidas/nueva', [Admin\ContabilidadController::class, 'create'])->name('partidas.create');
            Route::post('partidas', [Admin\ContabilidadController::class, 'store'])->middleware('confirmar.password')->name('partidas.store');
            Route::get('partidas/{partida}', [Admin\ContabilidadController::class, 'show'])->name('partidas.show');
            Route::post('partidas/{partida}/revertir', [Admin\ContabilidadController::class, 'revertir'])->middleware('confirmar.password')->name('partidas.revertir');
            Route::get('periodos', [Admin\ContabilidadController::class, 'periodos'])->name('periodos.index');
            Route::post('periodos/{periodo}/cerrar', [Admin\ContabilidadController::class, 'cerrarPeriodo'])->middleware('confirmar.password')->name('periodos.cerrar');
            Route::get('cuentas', [Admin\ContabilidadController::class, 'cuentas'])->name('cuentas.index');
            Route::post('cuentas', [Admin\ContabilidadController::class, 'guardarCuenta'])->middleware('confirmar.password')->name('cuentas.store');
        });
    });

    // Taller de armado
    Route::middleware('rol:ADMIN,TECNICO,VENDEDOR')->group(function () {
        Route::get('taller', [Admin\TallerController::class, 'index'])->name('taller.index');
        Route::get('taller/{orden}', [Admin\TallerController::class, 'show'])->name('taller.show');
    });

    Route::middleware('rol:ADMIN,TECNICO')->group(function () {
        Route::post('taller/{orden}/tomar', [Admin\TallerController::class, 'tomar'])->name('taller.tomar');
        Route::post('taller/{orden}/checklist', [Admin\TallerController::class, 'checklist'])->name('taller.checklist');
        Route::post('taller/{orden}/pruebas', [Admin\TallerController::class, 'pruebas'])->name('taller.pruebas');
        Route::post('taller/{orden}/completar', [Admin\TallerController::class, 'completar'])->name('taller.completar');
        Route::post('taller/{orden}/incidencia', [Admin\TallerController::class, 'incidencia'])->name('taller.incidencia');
    });

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
        Route::get('usuarios/exportar', [Admin\UsuarioController::class, 'exportar'])->middleware('confirmar.password')->name('usuarios.exportar');
        Route::resource('usuarios', Admin\UsuarioController::class)->except(['show', 'destroy'])->middleware('confirmar.password');
        Route::post('usuarios/{usuario}/desbloquear', [Admin\UsuarioController::class, 'desbloquear'])->middleware('confirmar.password')->name('usuarios.desbloquear');
        Route::post('usuarios/{usuario}/restablecer', [Admin\UsuarioController::class, 'enviarRestablecimiento'])->middleware('confirmar.password')->name('usuarios.restablecer');

        Route::get('bitacora', [Admin\BitacoraController::class, 'index'])->name('bitacora.index');

        Route::get('parametros', [Admin\ParametroController::class, 'index'])->name('parametros.index');
        Route::put('parametros', [Admin\ParametroController::class, 'update'])->middleware('confirmar.password')->name('parametros.update');
        Route::post('parametros/tipo-cambio', [Admin\ParametroController::class, 'guardarTipoCambio'])->middleware('confirmar.password')->name('parametros.tipo-cambio');
    });
});

require __DIR__.'/auth.php';
