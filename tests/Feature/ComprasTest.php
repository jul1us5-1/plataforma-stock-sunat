<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_compra_con_factura_suma_stock_y_actualiza_costo(): void
    {
        $usuario = User::first();
        Caja::create(['user_id' => $usuario->id, 'abierta_en' => now(), 'monto_apertura' => 500]);
        $producto = Producto::factory()->create(['stock' => 2, 'precio_compra' => null]);

        $this->actingAs($usuario)->post(route('proveedores.store'), ['ruc' => '20512345678', 'razon_social' => 'ORTOPEDIA MAYORISTA SAC'])->assertRedirect();
        $this->actingAs($usuario)->get(route('compras.create'))->assertOk();
        $this->actingAs($usuario)->post(route('compras.store'), [
            'proveedor_id' => Proveedor::first()->id, 'tipo_documento' => '01', 'numero_documento' => 'F001-555',
            'fecha' => today()->format('Y-m-d'), 'metodo_pago' => 'efectivo', 'pagado_desde_caja' => 1, 'incluye_igv' => 1,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 10, 'costo' => 23.60]],
        ])->assertRedirect();

        $compra = Compra::first();
        $this->assertEquals(236, $compra->total);
        $this->assertEquals(200, $compra->subtotal);
        $this->assertEquals(36, $compra->igv);
        $producto->refresh();
        $this->assertEquals(12, $producto->stock);
        $this->assertEquals(20, $producto->precio_compra);
        $this->assertEquals(264, Caja::abiertaDe($usuario->id)->efectivoEsperado());

        $this->actingAs($usuario)->get(route('compras.show', $compra))->assertOk()->assertSee('F001-555');
        $this->actingAs($usuario)->get(route('productos.movimientos', $producto))->assertOk()->assertSee('Compra Factura F001-555');

        $this->actingAs($usuario)->post(route('compras.anular', $compra))->assertRedirect();
        $this->assertEquals(2, $producto->fresh()->stock);
        $this->assertSame('anulada', $compra->fresh()->estado);
    }

    public function test_compra_con_boleta_el_igv_es_parte_del_costo(): void
    {
        $usuario = User::first();
        $producto = Producto::factory()->create(['stock' => 0]);
        $proveedor = Proveedor::create(['razon_social' => 'Bodega']);

        $this->actingAs($usuario)->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id, 'tipo_documento' => '03', 'fecha' => today()->format('Y-m-d'), 'metodo_pago' => 'yape',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 4, 'costo' => 15]],
        ])->assertRedirect();

        $this->assertEquals(15, $producto->fresh()->precio_compra);
        $this->assertEquals(0, Compra::first()->igv);
        $this->assertEquals(60, Compra::first()->total);
    }

    public function test_pagar_desde_caja_sin_caja_abierta_falla(): void
    {
        $producto = Producto::factory()->create();
        $proveedor = Proveedor::create(['razon_social' => 'X']);

        $this->actingAs(User::first())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id, 'tipo_documento' => '01', 'fecha' => today()->format('Y-m-d'), 'metodo_pago' => 'efectivo',
            'pagado_desde_caja' => 1, 'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'costo' => 1]],
        ])->assertSessionHasErrors('pagado_desde_caja');
        $this->assertDatabaseCount('compras', 0);
    }
}
