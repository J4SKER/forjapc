<?php

// Solo se sobrescriben estas opciones; el resto viene de la configuración del paquete.
return [
    // Versión CSP de Alpine: evalúa las expresiones de las vistas sin eval(), lo que permite
    // quitar 'unsafe-eval' de la política de contenido (ver CabecerasSeguridad).
    'csp_safe' => true,
];
