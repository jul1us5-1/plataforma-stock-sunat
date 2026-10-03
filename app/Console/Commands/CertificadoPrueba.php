<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Genera un certificado autofirmado para probar el envío en el entorno beta de SUNAT.
 * No sirve para producción: ahí se usa el certificado digital real de la empresa.
 */
class CertificadoPrueba extends Command
{
    protected $signature = 'sunat:certificado-prueba {--forzar : Sobrescribe el certificado existente}';

    protected $description = 'Crea un certificado de prueba para el entorno beta de SUNAT';

    public function handle(): int
    {
        if (config('sunat.entorno') === 'produccion') {
            $this->error('Estás en producción. Usa el certificado digital real de tu empresa.');

            return self::FAILURE;
        }

        $ruta = config('sunat.certificado');
        if (is_file($ruta) && ! $this->option('forzar')) {
            $this->warn("Ya existe un certificado en {$ruta}. Usa --forzar para reemplazarlo.");

            return self::SUCCESS;
        }

        $clave = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => config('sunat.empresa.razon_social'), 'organizationName' => config('sunat.empresa.ruc')], $clave);
        openssl_x509_export(openssl_csr_sign($csr, null, $clave, 365), $certificado);
        openssl_pkey_export($clave, $privada);

        if (! is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0755, true);
        }
        file_put_contents($ruta, $privada.$certificado);

        $this->info("Certificado de prueba creado en {$ruta}.");

        return self::SUCCESS;
    }
}
