<?php

namespace App\Services;

use App\Models\ParametroSistema;
use App\Models\TipoCambio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tipo de cambio de quetzales a dólares: el del día publicado por el Banco de Guatemala
 * (servicio web TipoCambioDia), el último registrado o, como respaldo, el de parámetros.
 */
class TipoCambioService
{
    private const URL_BANGUAT = 'https://www.banguat.gob.gt/variables/ws/TipoCambio.asmx';

    public function vigente(): float
    {
        $registrado = TipoCambio::vigente();

        return $registrado ? (float) $registrado->tasa : (float) ParametroSistema::valor('tipo_cambio_respaldo', 7.75);
    }

    /**
     * Consulta el tipo de cambio de referencia del día y lo guarda. Devuelve la tasa o null si falla.
     */
    public function sincronizarBanguat(): ?float
    {
        $sobre = <<<'XML'
            <?xml version="1.0" encoding="utf-8"?>
            <soap12:Envelope xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
              <soap12:Body><TipoCambioDia xmlns="http://www.banguat.gob.gt/variables/ws/" /></soap12:Body>
            </soap12:Envelope>
            XML;

        try {
            $respuesta = Http::timeout(15)
                ->withBody(trim($sobre), 'application/soap+xml; charset=utf-8')
                ->post(self::URL_BANGUAT);

            if (! $respuesta->successful() || ! preg_match('/<referencia>([\d.]+)<\/referencia>/', $respuesta->body(), $coincidencia)) {
                return null;
            }

            $tasa = (float) $coincidencia[1];
            if ($tasa < 1 || $tasa > 20) {
                return null;
            }

            TipoCambio::updateOrCreate(['fecha' => today()->toDateString()], ['tasa' => $tasa, 'fuente' => 'BANGUAT']);

            return $tasa;
        } catch (Throwable $e) {
            Log::warning('No se pudo consultar el tipo de cambio del Banguat: '.$e->getMessage());

            return null;
        }
    }

    public function aDolares(float $quetzales): float
    {
        return round($quetzales / $this->vigente(), 2);
    }
}
