<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Services\ComprobanteService;
use App\Services\SunatService;
use Database\Seeders\DatabaseSeeder;
use Greenter\See;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SunatXmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_xml_ubl_firmado(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['sunat.envio_automatico' => false]);

        $producto = Producto::factory()->create(['precio_venta' => 118]);
        $comprobante = app(ComprobanteService::class)->emitir([
            'tipo_comprobante' => '01',
            'serie' => 'F001',
            'cliente_id' => Cliente::factory()->conRuc()->create()->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        $see = new See;
        $see->setCertificate($this->certificadoDePrueba());
        $xml = $see->getXmlSigned(app(SunatService::class)->construirDocumento($comprobante));

        $this->assertStringContainsString('<cbc:ID>F001-1</cbc:ID>', $xml);
        $this->assertStringContainsString('<cbc:PayableAmount currencyID="PEN">118.00</cbc:PayableAmount>', $xml);
        $this->assertStringContainsString('CIENTO DIECIOCHO CON 00/100 SOLES', $xml);
        $this->assertStringContainsString('<ds:SignatureValue>', $xml);
    }

    private function certificadoDePrueba(): string
    {
        $clave = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['commonName' => 'Prueba'], $clave);
        openssl_x509_export(openssl_csr_sign($csr, null, $clave, 1), $cert);
        openssl_pkey_export($clave, $pem);

        return $pem.$cert;
    }
}
