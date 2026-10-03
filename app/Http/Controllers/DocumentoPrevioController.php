<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DocumentoPrevio;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentoPrevioController extends Controller
{
    public function index(Request $request, string $ruta)
    {
        $tipo = DocumentoPrevio::tipoDeRuta($ruta);
        $documentos = DocumentoPrevio::with('cliente')->where('tipo', $tipo)
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->latest('id')->paginate(25)->withQueryString();

        return view('documentos-previos.index', compact('tipo', 'documentos'));
    }

    public function create(string $ruta)
    {
        $tipo = DocumentoPrevio::tipoDeRuta($ruta);
        return view('documentos-previos.create', [
            'tipo' => $tipo,
            'clientes' => Cliente::orderBy('razon_social')->get(['id', 'tipo_documento', 'numero_documento', 'razon_social']),
            'productos' => Producto::where('activo', true)->orderByRaw('LOWER(nombre)')->get(),
        ]);
    }

    public function store(Request $request, string $ruta)
    {
        $tipo = DocumentoPrevio::tipoDeRuta($ruta);
        $datos = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'fecha_limite' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        $documento = DB::transaction(function () use ($datos, $tipo, $request) {
            $documento = DocumentoPrevio::create([
                'tipo' => $tipo,
                'numero' => DocumentoPrevio::siguienteNumero($tipo),
                'cliente_id' => $datos['cliente_id'] ?? null,
                'user_id' => $request->user()->id,
                'fecha' => today(),
                'fecha_limite' => $datos['fecha_limite'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $productos = Producto::findMany(collect($datos['items'])->pluck('producto_id'))->keyBy('id');
            foreach ($datos['items'] as $linea) {
                $cantidad = round((float) $linea['cantidad'], 2);
                $precio = round((float) $linea['precio_unitario'], 2);
                $documento->items()->create([
                    'producto_id' => $linea['producto_id'],
                    'descripcion' => $productos[$linea['producto_id']]->nombre,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'total' => round($cantidad * $precio, 2),
                ]);
            }
            $documento->update(['total' => $documento->items()->sum('total')]);

            return $documento;
        });

        return redirect()->route('previos.show', [$ruta, $documento])->with('ok', "{$documento->nombreTipo()} {$documento->codigo()} registrado.");
    }

    public function show(string $ruta, DocumentoPrevio $documento)
    {
        abort_unless($documento->ruta() === $ruta, 404);

        return view('documentos-previos.show', ['documento' => $documento->load(['items', 'cliente', 'vendedor', 'comprobante'])]);
    }

    public function anular(string $ruta, DocumentoPrevio $documento)
    {
        abort_unless($documento->ruta() === $ruta && $documento->pendiente(), 422);
        $documento->update(['estado' => 'anulado']);

        return back()->with('ok', "{$documento->codigo()} anulado.");
    }

    public function pdf(string $ruta, DocumentoPrevio $documento)
    {
        abort_unless($documento->ruta() === $ruta, 404);
        $documento->load(['items', 'cliente', 'vendedor']);

        return Pdf::loadView('pdf.documento-previo', ['d' => $documento, 'empresa' => config('sunat.empresa')])
            ->setPaper('a4')->stream($documento->codigo().'.pdf');
    }
}
