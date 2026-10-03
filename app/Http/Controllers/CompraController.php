<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\CompraService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $compras = Compra::with('proveedor')
            ->when($request->proveedor_id, fn ($q, $id) => $q->where('proveedor_id', $id))
            ->latest('fecha')->latest('id')->paginate(25)->withQueryString();

        return view('compras.index', [
            'compras' => $compras,
            'proveedores' => Proveedor::orderBy('razon_social')->get(['id', 'razon_social']),
        ]);
    }

    public function create()
    {
        return view('compras.create', [
            'proveedores' => Proveedor::orderBy('razon_social')->get(['id', 'ruc', 'razon_social']),
            'productos' => Producto::where('activo', true)->orderByRaw('LOWER(nombre)')->get(['id', 'codigo', 'nombre', 'precio_compra', 'stock']),
            'cajaAbierta' => Caja::abiertaDe(auth()->id()),
        ]);
    }

    public function store(Request $request, CompraService $servicio)
    {
        $datos = $request->validate([
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'tipo_documento' => ['required', Rule::in(array_keys(Compra::TIPOS_DOCUMENTO))],
            'numero_documento' => ['nullable', 'string', 'max:30'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'metodo_pago' => ['required', Rule::in(array_keys(Caja::METODOS_PAGO))],
            'pagado_desde_caja' => ['boolean'],
            'incluye_igv' => ['boolean'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.costo' => ['required', 'numeric', 'min:0'],
        ]);

        $compra = $servicio->registrar([
            ...$datos,
            'pagado_desde_caja' => $request->boolean('pagado_desde_caja'),
            'incluye_igv' => $request->boolean('incluye_igv', true),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('compras.show', $compra)->with('ok', 'Compra registrada. El stock fue actualizado.');
    }

    public function show(Compra $compra)
    {
        return view('compras.show', ['compra' => $compra->load(['proveedor', 'usuario', 'items.producto'])]);
    }

    public function anular(Compra $compra, CompraService $servicio)
    {
        $servicio->anular($compra->load('items'));

        return back()->with('ok', 'Compra anulada. Se descontó el stock que había ingresado.');
    }
}
