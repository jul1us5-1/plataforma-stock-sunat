<?php

namespace Database\Seeders;

use App\Models\Serie;
use App\Models\User;
use App\Services\ImportadorProductos;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            ['name' => 'Administrador', 'password' => env('ADMIN_PASSWORD', 'cambiar-esta-clave')],
        );

        // FC01 y BC01: notas de crédito de facturas y de boletas
        foreach ([['01', 'F001'], ['03', 'B001'], ['RI', 'R001'], ['07', 'FC01'], ['07', 'BC01']] as [$tipo, $serie]) {
            Serie::firstOrCreate(['serie' => $serie], ['tipo_comprobante' => $tipo]);
        }

        // Catálogo de productos exportado de MYPEFACT
        $catalogo = database_path('data/productos_mypefact.xlsx');
        if (! app()->runningUnitTests() && is_file($catalogo)) {
            app(ImportadorProductos::class)->importar($catalogo);
        }
    }
}
