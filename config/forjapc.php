<?php

/**
 * Ajustes de seguridad propios de ForjaPC.
 */
return [

    // Segundo factor: código de 6 dígitos por correo para todo el personal al iniciar sesión.
    'dos_pasos' => [
        'activo' => (bool) env('FORJAPC_DOS_PASOS', true),
        'minutos_validez' => 10,
        'intentos_maximos' => 5,
        'dias_recordar_equipo' => 30,
    ],

    // Minutos sin actividad tras los cuales se cierra la sesión del personal.
    'inactividad_personal' => (int) env('FORJAPC_INACTIVIDAD_PERSONAL', 30),

];
