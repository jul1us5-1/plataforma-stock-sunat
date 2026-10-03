<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Services\ComprobanteService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmisionComprobanteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['sunat.envio_automatico' => false]);
    }

    public function test_boleta_calcula_igv_y_descuenta_stock(): void
    {
        $producto = Producto::factory()->create(['precio_venta' => 118, 'stock' => 10]);

        $comprobante = app(ComprobanteService::class)->emitir([
            'tipo_comprobante' => '03',
            'serie' => 'B001',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ]);

        $this->assertSame('B001-00000001', $comprobante->numero());
        $this->assertEquals(200.00, $comprobante->op_gravadas);
        $this->assertEquals(36.00, $comprobante->igv);
        $this->assertEquals(236.00, $comprobante->total);
        $this->assertSame('pendiente', $comprobante->estado_sunat);
        $this->assertEquals(8, $producto->fresh()->stock);
        $this->assertDatabaseHas('movimientos_stock', ['producto_id' => $producto->id, 'cantidad' => -2, 'comprobante_id' => $comprobante->id]);
    }

    public function test_correlativo_avanza_por_serie(): void
    {
        $producto = Producto::factory()->create(['stock' => 10]);
        $datos = ['tipo_comprobante' => '03', 'serie' => 'B001', 'items' => [['producto_id' => $producto->id, 'cantidad' => 1]]];

        app(ComprobanteService::class)->emitir($datos);
        $segundo = app(ComprobanteService::class)->emitir($datos);

        $this->assertSame(2, $segundo->correlativo);
    }

    public function test_factura_exige_cliente_con_ruc(): void
    {
        $producto = Producto::factory()->create();
        $this->expectException(ValidationException::class);

        app(ComprobanteService::class)->emitir([
            'tipo_comprobante' => '01',
            'serie' => 'F001',
            'cliente_id' => Cliente::factory()->create()->id,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);
    }

    public function test_sin_stock_no_emite_ni_consume_correlativo(): void
    {
        $producto = Producto::factory()->create(['stock' => 1]);

        try {
            app(ComprobanteService::class)->emitir([
                'tipo_comprobante' => '03',
                'serie' => 'B001',
                'items' => [['producto_id' => $producto->id, 'cantidad' => 5]],
            ]);
            $this->fail('Debió fallar por stock');
        } catch (ValidationException) {
        }

        $this->assertDatabaseCount('comprobantes', 0);
        $this->assertDatabaseHas('series', ['serie' => 'B001', 'correlativo' => 0]);
        $this->assertEquals(1, $producto->fresh()->stock);
    }

    public function test_boleta_mayor_a_700_exige_documento(): void
    {
        $producto = Producto::factory()->create(['precio_venta' => 800]);
        $this->expectException(ValidationException::class);

        app(ComprobanteService::class)->emitir([
            'tipo_comprobante' => '03',
            'serie' => 'B001',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);
    }

    public function test_recibo_interno_no_se_envia_a_sunat(): void
    {
        config(['sunat.envio_automatico' => true]);
        $producto = Producto::factory()->create(['afectacion_igv' => '20', 'precio_venta' => 50]);

        $comprobante = app(ComprobanteService::class)->emitir([
            'tipo_comprobante' => 'RI',
            'serie' => 'R001',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        $this->assertSame('no_aplica', $comprobante->estado_sunat);
        $this->assertEquals(50, $comprobante->op_exoneradas);
        $this->assertEquals(0, $comprobante->igv);
    }

    public function test_venta_desde_pantalla(): void
    {
        $producto = Producto::factory()->create();
        $cliente = Cliente::factory()->conRuc()->create();

        $this->actingAs(User::first())
            ->post(route('comprobantes.store'), [
                'tipo_comprobante' => '01',
                'serie' => 'F001',
                'cliente_id' => $cliente->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio_unitario' => 100]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comprobantes', ['serie' => 'F001', 'correlativo' => 1, 'total' => 300]);
        $this->actingAs(User::first())->get(route('comprobantes.show', 1))->assertOk()->assertSee('F001-00000001');
    }

    public function test_pantallas_requieren_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->actingAs(User::first())->get('/')->assertOk();
        $this->actingAs(User::first())->get(route('productos.index'))->assertOk();
        $this->actingAs(User::first())->get(route('comprobantes.create'))->assertOk();
    }
}
