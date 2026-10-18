<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Respaldo completo de la base de datos (tablas, vistas, triggers y rutinas) comprimido con gzip.
 * Las credenciales viajan en un archivo temporal con permisos 600, nunca en la línea de comandos.
 */
#[Signature('forjapc:respaldar {--conservar=14 : Cuántos respaldos guardar}')]
#[Description('Genera un respaldo comprimido de la base de datos y elimina los más antiguos')]
class Respaldar extends Command
{
    public const CARPETA = 'app/respaldos';

    public function handle(): int
    {
        $conexion = config('database.connections.'.config('database.default'));
        $carpeta = storage_path(self::CARPETA);
        File::ensureDirectoryExists($carpeta, 0750);

        $archivo = $carpeta.'/forjapc-'.now()->format('Ymd-His').'.sql.gz';
        $credenciales = tempnam(sys_get_temp_dir(), 'respaldo');
        chmod($credenciales, 0600);
        file_put_contents($credenciales, sprintf("[client]\nuser=%s\npassword=\"%s\"\nhost=%s\nport=%s\n",
            $conexion['username'], addcslashes((string) $conexion['password'], '"\\'), $conexion['host'], $conexion['port']));

        try {
            $comando = sprintf(
                'set -o pipefail; mysqldump --defaults-extra-file=%s --single-transaction --quick --routines --triggers --events --no-tablespaces %s | gzip -9 > %s',
                escapeshellarg($credenciales), escapeshellarg($conexion['database']), escapeshellarg($archivo),
            );
            $proceso = Process::fromShellCommandline('bash -c '.escapeshellarg($comando))->setTimeout(600);
            $proceso->run();

            if (! $proceso->isSuccessful() || ! is_file($archivo) || filesize($archivo) < 1024) {
                @unlink($archivo);
                $this->error('No se pudo generar el respaldo: '.trim($proceso->getErrorOutput()));
                Log::error('Falló el respaldo de la base de datos', ['error' => $proceso->getErrorOutput()]);

                return self::FAILURE;
            }
        } finally {
            @unlink($credenciales);
        }

        chmod($archivo, 0640);
        $this->info('Respaldo creado: '.basename($archivo).' ('.number_format(filesize($archivo) / 1024, 1).' KB)');

        $this->eliminarAntiguos($carpeta, max(1, (int) $this->option('conservar')));

        return self::SUCCESS;
    }

    private function eliminarAntiguos(string $carpeta, int $conservar): void
    {
        $respaldos = collect(File::glob($carpeta.'/forjapc-*.sql.gz'))->sortDesc()->values();

        foreach ($respaldos->slice($conservar) as $viejo) {
            File::delete($viejo);
            $this->line('Eliminado: '.basename($viejo));
        }
    }
}
