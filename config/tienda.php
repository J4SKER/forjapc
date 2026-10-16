<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Categorías del menú de la tienda
    |--------------------------------------------------------------------------
    |
    | Menú principal mientras el catálogo no está en la base de datos
    | (sprint de la semana 3). Cada categoría tiene un slug, un nombre y un
    | icono del componente <x-icono>.
    |
    */

    'categorias' => [
        ['slug' => 'procesadores', 'nombre' => 'Procesadores', 'icono' => 'cpu'],
        ['slug' => 'tarjetas-madre', 'nombre' => 'Tarjetas madre', 'icono' => 'placa'],
        ['slug' => 'memorias-ram', 'nombre' => 'Memorias RAM', 'icono' => 'ram'],
        ['slug' => 'tarjetas-de-video', 'nombre' => 'Tarjetas de video', 'icono' => 'gpu'],
        ['slug' => 'almacenamiento', 'nombre' => 'Almacenamiento', 'icono' => 'disco'],
        ['slug' => 'fuentes-de-poder', 'nombre' => 'Fuentes de poder', 'icono' => 'fuente'],
        ['slug' => 'gabinetes', 'nombre' => 'Gabinetes', 'icono' => 'gabinete'],
        ['slug' => 'enfriamiento', 'nombre' => 'Enfriamiento', 'icono' => 'ventilador'],
        ['slug' => 'monitores', 'nombre' => 'Monitores', 'icono' => 'monitor'],
        ['slug' => 'teclados', 'nombre' => 'Teclados', 'icono' => 'teclado'],
        ['slug' => 'mouse', 'nombre' => 'Mouse', 'icono' => 'mouse'],
        ['slug' => 'audifonos', 'nombre' => 'Audífonos', 'icono' => 'audifonos'],
    ],

];
