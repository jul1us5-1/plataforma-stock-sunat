<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DocumentoPrevio;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentosPreviosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['sunat.envio_automatico' => false]);
    }

    public function test_cotizacion_se_convierte_en_venta(): void
    {
        $usuario = User::first();
        $producto = Producto::factory()->create(['precio_venta' => 50, 'stock' => 5]);
        $cliente = Cliente::factory()->conRuc()->create();

        $this->actingAs($usuario)->get(route('previos.create', 'cotizaciones'))->assertOk();
        $this->actingAs($usuario)->post(route('previos.store', 'cotizaciones'), [
            'cliente_id' => $cliente->id, 'fecha_limite' => today()->addWeek()->format('Y-m-d'),
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 45]],
        ])->assertRedirect();

        $cotizacion = DocumentoPrevio::first();
        $this->assertSame('COT-00001', $cotizacion->codigo());
        $this->assertEquals(90, $cotizacion->total);
        $this->assertEquals(5, $producto->fresh()->stock); // una cotización no mueve stock

        $this->actingAs($usuario)->get(route('previos.index', 'cotizaciones'))->assertOk()->assertSee('COT-00001');
        $this->actingAs($usuario)->get(route('previos.show', ['cotizaciones', $cotizacion]))->assertOk();
        $this->actingAs($usuario)->get(route('previos.pdf', ['cotizaciones', $cotizacion]))->assertOk();
        $this->actingAs($usuario)->get(route('previos.show', ['pedidos', $cotizacion]))->assertNotFound();
        $this->actingAs($usuario)->get(route('comprobantes.create', ['previo' => $cotizacion->id]))->assertOk()->assertSee('COT-00001');

        $this->actingAs($usuario)->post(route('comprobantes.store'), [
            'tipo_comprobante' => '01', 'serie' => 'F001', 'cliente_id' => $cliente->id, 'metodo_pago' => 'transferencia',
            'documento_previo_id' => $cotizacion->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 45]],
        ])->assertRedirect();

        $cotizacion->refresh();
        $this->assertSame('convertido', $cotizacion->estado);
        $this->assertNotNull($cotizacion->comprobante_id);
        $this->assertEquals(3, $producto->fresh()->stock);
    }

    public function test_pedido_se_numera_aparte_y_se_anula(): void
    {
        $usuario = User::first();
        $producto = Producto::factory()->create();
        foreach (['cotizaciones', 'pedidos'] as $ruta) {
            $this->actingAs($usuario)->post(route('previos.store', $ruta), [
                'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 10]],
            ])->assertRedirect();
        }

        $pedido = DocumentoPrevio::where('tipo', 'pedido')->first();
        $this->assertSame('PED-00001', $pedido->codigo());

        $this->actingAs($usuario)->post(route('previos.anular', ['pedidos', $pedido]))->assertRedirect();
        $this->assertSame('anulado', $pedido->fresh()->estado);
    }
}
