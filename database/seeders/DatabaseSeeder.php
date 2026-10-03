<?php

namespace Database\Seeders;

use App\Models\Serie;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            ['name' => 'Administrador', 'password' => env('ADMIN_PASSWORD', 'cambiar-esta-clave')],
        );

        foreach ([['01', 'F001'], ['03', 'B001'], ['RI', 'R001']] as [$tipo, $serie]) {
            Serie::firstOrCreate(['serie' => $serie], ['tipo_comprobante' => $tipo]);
        }
    }
}
