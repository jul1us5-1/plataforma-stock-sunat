<?php

namespace App\Console\Commands;

use App\Services\ImportadorProductos;
use Illuminate\Console\Command;

class ImportarProductos extends Command
{
    protected $signature = 'productos:importar {archivo : Ruta al .xlsx o .csv}';

    protected $description = 'Importa o actualiza productos desde un Excel o CSV';

    public function handle(ImportadorProductos $importador): int
    {
        $resultado = $importador->importar($this->argument('archivo'));

        $this->info("Creados: {$resultado['creados']} · Actualizados: {$resultado['actualizados']}");
        foreach ($resultado['errores'] as $error) {
            $this->warn($error);
        }

        return self::SUCCESS;
    }
}
