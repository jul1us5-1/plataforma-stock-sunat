<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Services\ImportadorProductos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportarProductosTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_y_actualiza_por_codigo(): void
    {
        Producto::factory()->create(['codigo' => 'A1', 'stock' => 3]);
        $ruta = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($ruta, "\xEF\xBB\xBFcodigo;nombre;precio_venta;stock;categoria\nA1;Arroz 5kg;\"25,90\";10;Abarrotes\nB2;Aceite 1L;12.50;4;Abarrotes\n;sin codigo;1;1;\n");

        $resultado = app(ImportadorProductos::class)->importar($ruta);

        $this->assertSame(1, $resultado['creados']);
        $this->assertSame(1, $resultado['actualizados']);
        $this->assertCount(1, $resultado['errores']);
        $this->assertEquals(25.90, Producto::where('codigo', 'A1')->first()->precio_venta);
        $this->assertEquals(10, Producto::where('codigo', 'A1')->first()->stock);
        $this->assertDatabaseHas('movimientos_stock', ['cantidad' => 7, 'tipo' => 'ajuste']);
    }

    public function test_importa_reporte_de_mypefact(): void
    {
        $resultado = app(ImportadorProductos::class)->importar(database_path('data/productos_mypefact.xlsx'));

        $this->assertSame(566, $resultado['creados']);
        $this->assertSame([], $resultado['errores']);
        $rodillera = Producto::where('codigo', '00003')->first();
        $this->assertSame('Rodillera con abertura S', $rodillera->nombre);
        $this->assertEquals(30, $rodillera->precio_venta);
        $this->assertSame('10', $rodillera->afectacion_igv);
        $this->assertNotNull(Producto::where('codigo', '100568')->first());

        // Reimportar no duplica
        $this->assertSame(566, app(ImportadorProductos::class)->importar(database_path('data/productos_mypefact.xlsx'))['actualizados']);
        $this->assertSame(566, Producto::count());
    }
}
