<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga de reportes en CSV compatible con Excel (UTF-8 con BOM).
 */
class Csv
{
    /**
     * @param  list<string>  $encabezados
     * @param  iterable<int, array<int, mixed>>  $filas
     */
    public static function descargar(string $nombre, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas) {
            $archivo = fopen('php://output', 'w');
            fwrite($archivo, "\xEF\xBB\xBF");
            fputcsv($archivo, $encabezados);

            foreach ($filas as $fila) {
                fputcsv($archivo, array_map(self::celda(...), $fila));
            }

            fclose($archivo);
        }, $nombre.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Evita la inyección de fórmulas al abrir el archivo en Excel: un texto que empieza
     * con =, +, -, @, tabulador o retorno se antepone con un apóstrofo.
     */
    public static function celda(mixed $valor): mixed
    {
        if (! is_string($valor) || $valor === '' || is_numeric($valor)) {
            return $valor;
        }

        return in_array($valor[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$valor : $valor;
    }
}
