<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['sunat.envio_automatico' => false]);
    }

    public function test_flujo_completo_de_caja(): void
    {
        $usuario = User::first();
        $producto = Producto::factory()->create(['precio_venta' => 50, 'stock' => 10]);
        $vender = fn (string $metodo) => $this->actingAs($usuario)->post(route('comprobantes.store'), [
            'tipo_comprobante' => '03', 'serie' => 'B001', 'metodo_pago' => $metodo,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertRedirect();

        $this->actingAs($usuario)->post(route('caja.abrir'), ['monto_apertura' => 100])->assertRedirect();
        $this->actingAs($usuario)->post(route('caja.abrir'), ['monto_apertura' => 100])->assertSessionHasErrors('caja');

        $vender('efectivo');
        $vender('efectivo');
        $vender('yape');
        $this->actingAs($usuario)->post(route('caja.movimiento'), [
            'tipo' => 'egreso', 'concepto' => 'Bolsas', 'monto' => 15, 'metodo_pago' => 'efectivo',
        ])->assertRedirect();

        $caja = Caja::abiertaDe($usuario->id);
        $this->assertCount(4, $caja->movimientos);
        $this->assertEquals(185, $caja->efectivoEsperado());
        $this->assertEquals(['ingresos' => 50.0, 'egresos' => 0.0], $caja->resumenPorMetodo()['yape']);
        $this->actingAs($usuario)->get(route('caja.index'))->assertOk()->assertSee('185.00');

        $this->actingAs($usuario)->post(route('caja.cerrar'), ['efectivo_contado' => 180])->assertRedirect(route('caja.show', $caja));

        $caja->refresh();
        $this->assertFalse($caja->estaAbierta());
        $this->assertEquals(-5, $caja->diferencia);
        $this->actingAs($usuario)->get(route('caja.show', $caja))->assertOk();
        $this->assertNull(Caja::abiertaDe($usuario->id));
    }

    public function test_venta_sin_caja_abierta_se_emite_igual(): void
    {
        $producto = Producto::factory()->create();

        $this->actingAs(User::first())->post(route('comprobantes.store'), [
            'tipo_comprobante' => '03', 'serie' => 'B001', 'metodo_pago' => 'efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ])->assertRedirect();

        $this->assertDatabaseCount('comprobantes', 1);
        $this->assertDatabaseCount('movimientos_caja', 0);
        $this->actingAs(User::first())->get(route('comprobantes.create'))->assertSee('No tienes una caja abierta');
    }
}
