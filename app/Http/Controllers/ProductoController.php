<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\ImportadorProductos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $productos = Producto::query()
            ->when($request->q, fn ($q, $texto) => $q->where($request->campo === 'codigo' ? 'codigo' : 'nombre', 'like', "%{$texto}%"))
            ->when($request->boolean('stock_bajo'), fn ($q) => $q->whereColumn('stock', '<=', 'stock_minimo')->where('unidad_medida', '!=', 'ZZ'))
            ->orderByRaw('LOWER(nombre)')
            ->paginate(25)
            ->withQueryString();

        return view('productos.index', compact('productos'));
    }

    public function exportar()
    {
        return response()->streamDownload(function () {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel lea las tildes
            $columnas = ['codigo', 'nombre', 'precio_venta', 'precio_compra', 'stock', 'stock_minimo', 'categoria', 'unidad_medida', 'afectacion_igv', 'descripcion'];
            fputcsv($salida, $columnas, ';', '"', '');
            foreach (Producto::orderBy('nombre')->lazy() as $producto) {
                fputcsv($salida, array_map(fn ($c) => $producto->getRawOriginal($c), $columnas), ';', '"', '');
            }
            fclose($salida);
        }, 'productos_'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create()
    {
        return view('productos.form', ['producto' => new Producto(['unidad_medida' => 'NIU', 'afectacion_igv' => '10'])]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        DB::transaction(function () use ($datos) {
            $stockInicial = (float) ($datos['stock'] ?? 0);
            $producto = Producto::create([...$datos, 'stock' => 0]);
            if ($stockInicial != 0) {
                $producto->moverStock($stockInicial, 'entrada', 'Stock inicial');
            }
        });

        return redirect()->route('productos.index')->with('ok', 'Producto creado.');
    }

    public function edit(Producto $producto)
    {
        return view('productos.form', compact('producto'));
    }

    public function update(Request $request, Producto $producto)
    {
        // El stock solo cambia con movimientos, no al editar
        $producto->update(collect($this->validar($request, $producto))->except('stock')->all());

        return redirect()->route('productos.index')->with('ok', 'Producto actualizado.');
    }

    public function destroy(Producto $producto)
    {
        $producto->update(['activo' => false]);

        return back()->with('ok', 'Producto desactivado.');
    }

    public function movimientos(Producto $producto)
    {
        $movimientos = $producto->movimientos()->with(['comprobante', 'compra'])->latest()->paginate(30);

        return view('productos.movimientos', compact('producto', 'movimientos'));
    }

    public function ajustarStock(Request $request, Producto $producto)
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['entrada', 'salida', 'ajuste'])],
            'cantidad' => ['required', 'numeric', 'not_in:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $cantidad = abs((float) $datos['cantidad']);
        $cantidad = match ($datos['tipo']) {
            'salida' => -$cantidad,
            'ajuste' => (float) $datos['cantidad'],
            default => $cantidad,
        };

        DB::transaction(fn () => $producto->moverStock($cantidad, $datos['tipo'], $datos['motivo'] ?? null));

        return back()->with('ok', 'Stock actualizado.');
    }

    public function importar(Request $request, ImportadorProductos $importador)
    {
        $request->validate(['archivo' => ['required', 'file', 'extensions:csv,txt,xlsx']]);
        $archivo = $request->file('archivo');
        $resultado = $importador->importar($archivo->getRealPath(), $archivo->getClientOriginalName());

        return redirect()->route('productos.index')->with('ok',
            "Importación lista: {$resultado['creados']} creados, {$resultado['actualizados']} actualizados."
            .($resultado['errores'] ? ' Errores: '.implode('; ', array_slice($resultado['errores'], 0, 5)) : ''));
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos')->ignore($producto)],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'unidad_medida' => ['required', Rule::in(['NIU', 'ZZ', 'KGM', 'LTR', 'MTR', 'BX', 'DZN'])],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'afectacion_igv' => ['required', Rule::in(['10', '20', '30'])],
            'stock' => ['nullable', 'numeric'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['boolean'],
        ]) + ['activo' => $request->boolean('activo', true), 'stock_minimo' => $request->input('stock_minimo', 0)];
    }
}
