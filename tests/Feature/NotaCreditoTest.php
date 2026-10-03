<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Producto;
use App\Models\User;
use App\Services\ComprobanteService;
use App\Services\NotaCreditoService;
use App\Services\ReporteService;
use App\Services\SunatService;
use Database\Seeders\DatabaseSeeder;
use Greenter\See;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NotaCreditoTest extends TestCase
{
    use RefreshDatabase;

    private Producto $a;

    private Producto $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['sunat.envio_automatico' => false]);
        $this->a = Producto::factory()->create(['precio_venta' => 118, 'precio_compra' => 50, 'stock' => 10]);
        $this->b = Producto::factory()->create(['precio_venta' => 59, 'stock' => 10]);
    }

    private function factura(): Comprobante
    {
        $factura = app(ComprobanteService::class)->emitir([
            'tipo_comprobante' => '01', 'serie' => 'F001', 'user_id' => 1, 'metodo_pago' => 'efectivo',
            'cliente_id' => Cliente::factory()->conRuc()->create()->id,
            'items' => [['producto_id' => $this->a->id, 'cantidad' => 2], ['producto_id' => $this->b->id, 'cantidad' => 3]],
        ]);
        // Simula la aceptación de SUNAT
        $factura->update(['estado_sunat' => 'aceptado']);

        return $factura;
    }

    public function test_anulacion_total_devuelve_stock_y_resta_en_reportes(): void
    {
        Caja::create(['user_id' => 1, 'abierta_en' => now(), 'monto_apertura' => 0]);
        $factura = $this->factura();

        $nota = app(NotaCreditoService::class)->emitir($factura, '01', 'Error en la venta', [], 1);

        $this->assertSame('FC01-00000001', $nota->numero());
        $this->assertEquals(413, $nota->total);
        $this->assertEquals(10, $this->a->fresh()->stock);
        $this->assertEquals(10, $this->b->fresh()->stock);
        $this->assertEquals(0, Caja::abiertaDe(1)->efectivoEsperado());

        $resumen = app(ReporteService::class)->resumen(today(), today());
        $this->assertEquals(0, $resumen['total']);
        $this->assertEquals(0, $resumen['utilidad']);
        $this->assertSame(1, $resumen['cpe']);
        $this->assertTrue(app(ReporteService::class)->productosVendidos(today(), today())->every(fn ($p) => (float) $p->cantidad === 0.0));

        // Ya no queda nada por devolver
        $this->expectException(ValidationException::class);
        app(NotaCreditoService::class)->emitir($factura, '06', 'Otra vez', [], 1);
    }

    public function test_devolucion_por_item(): void
    {
        $factura = $this->factura();

        $nota = app(NotaCreditoService::class)->emitir($factura, '07', 'Talla equivocada', [$this->b->id => 1], 1);

        $this->assertEquals(59, $nota->total);
        $this->assertEquals(8, $this->b->fresh()->stock);
        $this->assertEquals([$this->a->id => 2.0, $this->b->id => 2.0], $factura->fresh()->cantidadesDevolvibles());

        $this->expectException(ValidationException::class);
        app(NotaCreditoService::class)->emitir($factura, '07', 'Demasiado', [$this->b->id => 3], 1);
    }

    public function test_no_se_emite_sobre_comprobante_no_aceptado(): void
    {
        $factura = $this->factura();
        $factura->update(['estado_sunat' => 'pendiente']);

        $this->expectException(ValidationException::class);
        app(NotaCreditoService::class)->emitir($factura, '01', 'x', [], 1);
    }

    public function test_xml_de_nota_de_credito(): void
    {
        $nota = app(NotaCreditoService::class)->emitir($this->factura(), '01', 'Anulación', [], 1);

        $see = new See;
        $see->setCertificate($this->certificado());
        $xml = $see->getXmlSigned(app(SunatService::class)->construirDocumento($nota));

        $this->assertStringContainsString('<CreditNote', $xml);
        $this->assertStringContainsString('<cbc:ReferenceID>F001-1</cbc:ReferenceID>', $xml);
        $this->assertStringContainsString('<cbc:ResponseCode>01</cbc:ResponseCode>', $xml);
    }

    public function test_pantallas_y_anulacion_de_recibo(): void
    {
        $usuario = User::first();
        $factura = $this->factura();

        $this->actingAs($usuario)->get(route('comprobantes.nota-credito', $factura))->assertOk();
        $this->actingAs($usuario)->post(route('comprobantes.nota-credito', $factura), [
            'motivo_codigo' => '07', 'motivo_descripcion' => 'Devolución', 'cantidades' => [$this->a->id => 1, $this->b->id => 0],
        ])->assertRedirect();
        $nota = Comprobante::where('tipo_comprobante', '07')->first();
        $this->actingAs($usuario)->get(route('comprobantes.show', $nota))->assertOk()->assertSee('F001-00000001');
        $this->actingAs($usuario)->get(route('comprobantes.show', $factura))->assertOk()->assertSee('FC01-00000001');

        $recibo = app(ComprobanteService::class)->emitir(['tipo_comprobante' => 'RI', 'serie' => 'R001',
            'items' => [['producto_id' => $this->a->id, 'cantidad' => 1]]]);
        $stock = (float) $this->a->fresh()->stock;
        $this->actingAs($usuario)->post(route('comprobantes.anular', $recibo), ['motivo' => 'Cliente se arrepintió'])->assertRedirect();
        $this->assertSame('anulado', $recibo->fresh()->estado_sunat);
        $this->assertEquals($stock + 1, $this->a->fresh()->stock);
    }

    private function certificado(): string
    {
        $clave = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_x509_export(openssl_csr_sign(openssl_csr_new(['commonName' => 'Prueba'], $clave), null, $clave, 1), $cert);
        openssl_pkey_export($clave, $pem);

        return $pem.$cert;
    }
}
