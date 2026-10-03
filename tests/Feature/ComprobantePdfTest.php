<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Services\ComprobantePdf;
use App\Services\ComprobanteService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ComprobantePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['sunat.envio_automatico' => false]);
    }

    private function boleta()
    {
        $producto = Producto::factory()->create(['precio_venta' => 100]);
        $cliente = Cliente::factory()->create(['numero_documento' => '12345678', 'telefono' => '987 654 321']);

        return app(ComprobanteService::class)->emitir([
            'tipo_comprobante' => '03', 'serie' => 'B001', 'cliente_id' => $cliente->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);
    }

    public function test_contenido_del_qr(): void
    {
        $boleta = $this->boleta();
        $boleta->update(['hash' => 'abc=']);

        $this->assertSame(
            config('sunat.empresa.ruc').'|03|B001|1|15.25|100.00|'.$boleta->fecha_emision->format('Y-m-d').'|1|12345678|abc=|',
            app(ComprobantePdf::class)->contenidoQr($boleta)
        );
    }

    public function test_genera_pdf_a4_y_ticket(): void
    {
        $boleta = $this->boleta();
        $usuario = User::first();

        foreach (['a4', 'ticket'] as $formato) {
            $respuesta = $this->actingAs($usuario)->get(route('comprobantes.pdf', [$boleta, $formato]))->assertOk();
            $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        }

        $this->actingAs($usuario)->get(route('comprobantes.show', $boleta))->assertOk()->assertSee('wa.me/51987654321', false);
    }

    public function test_enlace_publico_requiere_firma(): void
    {
        $boleta = $this->boleta();

        $this->get(route('comprobantes.publico', [$boleta, 'a4']))->assertForbidden();
        $this->get(URL::signedRoute('comprobantes.publico', [$boleta, 'a4']))->assertOk();
    }
}
