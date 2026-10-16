<?php

namespace Database\Seeders;

use App\Models\ParametroSistema;
use Illuminate\Database\Seeder;

class ParametroSistemaSeeder extends Seeder
{
    /**
     * Parámetros del negocio editables desde el panel de administración.
     */
    public function run(): void
    {
        $parametros = [
            ['iva_porcentaje', '12', 'decimal', 'Porcentaje de IVA incluido en los precios'],
            ['costo_servicio_armado', '250.00', 'decimal', 'Costo del servicio de armado y pruebas (Q)'],
            ['minutos_vencimiento_pago_linea', '30', 'entero', 'Minutos para pagar en línea antes de cancelar el pedido'],
            ['horas_vencimiento_transferencia', '48', 'entero', 'Horas para subir el comprobante de transferencia'],
            ['intentos_maximos_login', '5', 'entero', 'Intentos fallidos antes de bloquear la cuenta'],
            ['minutos_bloqueo_cuenta', '15', 'entero', 'Minutos que dura el bloqueo temporal de la cuenta'],
            ['cuenta_bancaria_depositos', 'Banco Industrial - Monetaria 000-000000-0 - ForjaPC', 'texto', 'Cuenta para depósitos y transferencias'],
            ['telefono_contacto', '+502 2222-0000', 'texto', 'Teléfono que se muestra en la tienda'],
            ['whatsapp_contacto', '50222220000', 'texto', 'Número de WhatsApp (solo dígitos, con código de país)'],
            ['correo_contacto', 'ventas@forjapc.com', 'texto', 'Correo de contacto de la tienda'],
            ['tipo_cambio_respaldo', '7.75', 'decimal', 'Tipo de cambio (Q por US$) si no hay uno registrado del Banguat'],
            ['modo_mantenimiento', '0', 'booleano', 'Muestra la tienda en mantenimiento para los clientes'],
        ];

        // Solo agrega los que faltan: no sobrescribe valores editados desde el panel.
        foreach ($parametros as [$clave, $valor, $tipo, $descripcion]) {
            ParametroSistema::firstOrCreate(['clave' => $clave], compact('valor', 'tipo', 'descripcion'));
        }
    }
}
