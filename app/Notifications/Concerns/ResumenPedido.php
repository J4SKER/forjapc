<?php

namespace App\Notifications\Concerns;

use App\Models\ParametroSistema;
use App\Models\Pedido;
use App\Models\PedidoItem;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Resumen del pedido para los correos: qué compró el cliente, cuánto y cómo lo recibe.
 */
trait ResumenPedido
{
    protected function agregarResumen(MailMessage $mensaje, Pedido $pedido): MailMessage
    {
        $mensaje->line("**Resumen del pedido {$pedido->numero}**");

        foreach ($pedido->loadMissing('items')->itemsAgrupados() as $lineas) {
            $mensaje->line('• '.$this->describirLinea($lineas->first(), $lineas->count(), (float) $lineas->sum('subtotal')));
        }

        if ((float) $pedido->costo_armado > 0) {
            $mensaje->line('• Servicio de armado y pruebas — '.$this->q($pedido->costo_armado));
        }

        if (! $pedido->esEnvio()) {
            $entrega = 'Recoger en tienda — sin costo';
        } else {
            $entrega = 'Envío a domicilio — '.((float) $pedido->costo_envio > 0 ? $this->q($pedido->costo_envio) : 'sin costo');
        }

        return $mensaje->line("• {$entrega}")
            ->line("**Total: {$this->q($pedido->total)}** (IVA incluido)");
    }

    /**
     * Los correos salen de una dirección que no recibe respuestas: se indica a dónde escribir.
     */
    protected function contacto(string $pregunta = '¿Tienes alguna duda?'): string
    {
        return "{$pregunta} Escríbenos a ".ParametroSistema::valor('correo_contacto', 'ventas@forjapc.com')
            .' o llámanos al '.ParametroSistema::valor('telefono_contacto', '+502 2222-0000').'.';
    }

    private function describirLinea(PedidoItem $item, int $piezas, float $subtotal): string
    {
        if (! $item->grupo_configuracion) {
            return "{$item->cantidad} × {$item->descripcion} — ".$this->q($item->subtotal);
        }

        $detalle = "PC a la medida, {$piezas} piezas".($item->con_armado ? ', armada y probada' : '');

        return "{$item->nombre_configuracion} ({$detalle}) — ".$this->q($subtotal);
    }

    private function q(mixed $monto): string
    {
        return 'Q'.number_format((float) $monto, 2);
    }
}
