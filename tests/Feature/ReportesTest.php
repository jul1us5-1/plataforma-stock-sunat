<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use App\Services\ComprobanteService;
use App\Services\ReporteService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['sunat.envio_automatico' => false]);
    }

    public function test_resumen_y_utilidad(): void
    {
        $conCosto = Producto::factory()->create(['precio_venta' => 118, 'precio_compra' => 60, 'stock' => 10]);
        $sinCosto = Producto::factory()->create(['precio_venta' => 50, 'afectacion_igv' => '20', 'stock' => 10]);
        $servicio = app(ComprobanteService::class);

        $servicio->emitir(['tipo_comprobante' => '03', 'serie' => 'B001', 'metodo_pago' => 'yape',
            'items' => [['producto_id' => $conCosto->id, 'cantidad' => 2]]]);
        $servicio->emitir(['tipo_comprobante' => 'RI', 'serie' => 'R001',
            'items' => [['producto_id' => $sinCosto->id, 'cantidad' => 1]]]);

        $resumen = app(ReporteService::class)->resumen(today(), today());

        $this->assertSame(1, $resumen['cpe']);
        $this->assertEquals(236, $resumen['monto_cpe']);
        $this->assertEquals(50, $resumen['monto_recibos']);
        $this->assertEquals(286, $resumen['total']);
        // 200 de valor venta sin IGV - 120 de costo
        $this->assertEquals(80, $resumen['utilidad']);
        $this->assertSame(1, $resumen['items_sin_costo']);

        $serie = app(ReporteService::class)->serie(today(), today());
        $this->assertCount(24, $serie);
        $this->assertEquals(286, $serie->sum(fn ($f) => $f['cpe'] + $f['recibos']));
    }

    public function test_pantallas_de_reportes(): void
    {
        $producto = Producto::factory()->create();
        app(ComprobanteService::class)->emitir(['tipo_comprobante' => '03', 'serie' => 'B001',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]]]);
        $usuario = User::first();

        $this->actingAs($usuario)->get(route('dashboard', ['periodo' => 'semana']))->assertOk()->assertSee('CPE emitidos');
        $this->actingAs($usuario)->get(route('reportes.index', ['periodo' => 'rango', 'desde' => today()->subDays(3)->format('Y-m-d'), 'hasta' => today()->format('Y-m-d')]))
            ->assertOk()->assertSee($producto->nombre);
        $csv = $this->actingAs($usuario)->get(route('reportes.ventas'))->assertOk()->streamedContent();
        $this->assertStringContainsString('B001-00000001', $csv);
    }
}
