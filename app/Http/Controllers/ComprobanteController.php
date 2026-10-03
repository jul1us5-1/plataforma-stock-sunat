<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Producto;
use App\Models\Serie;
use App\Services\ComprobanteService;
use App\Services\SunatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ComprobanteController extends Controller
{
    public function index(Request $request)
    {
        $comprobantes = Comprobante::with('cliente')
            ->when($request->tipo, fn ($q, $tipo) => $q->where('tipo_comprobante', $tipo))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado_sunat', $estado))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('comprobantes.index', compact('comprobantes'));
    }

    public function create()
    {
        return view('comprobantes.create', [
            'series' => Serie::where('activo', true)->orderBy('serie')->get(),
            'clientes' => Cliente::orderBy('razon_social')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, ComprobanteService $servicio)
    {
        $datos = $request->validate([
            'tipo_comprobante' => ['required', Rule::in(array_keys(Comprobante::TIPOS))],
            'serie' => ['required', 'exists:series,serie'],
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ]);

        $comprobante = $servicio->emitir($datos);

        return redirect()->route('comprobantes.show', $comprobante)->with('ok', "{$comprobante->nombreTipo()} {$comprobante->numero()} emitida.");
    }

    public function show(Comprobante $comprobante)
    {
        $comprobante->load(['items', 'cliente']);

        return view('comprobantes.show', compact('comprobante'));
    }

    public function reenviar(Comprobante $comprobante, SunatService $sunat)
    {
        abort_unless($comprobante->seEnviaASunat() && in_array($comprobante->estado_sunat, ['pendiente', 'error'], true), 422);
        $sunat->enviar($comprobante);

        return back()->with('ok', 'Reenvío a SUNAT: '.$comprobante->estado_sunat);
    }

    public function descargar(Comprobante $comprobante, string $archivo)
    {
        $ruta = match ($archivo) {
            'xml' => $comprobante->xml_path,
            'cdr' => $comprobante->cdr_path,
            default => null,
        };
        abort_unless($ruta && Storage::exists($ruta), 404);

        return Storage::download($ruta);
    }
}
