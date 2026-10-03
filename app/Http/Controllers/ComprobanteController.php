<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Producto;
use App\Models\Serie;
use App\Services\ComprobantePdf;
use App\Services\ComprobanteService;
use App\Services\NotaCreditoService;
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
            'series' => Serie::where('activo', true)->whereIn('tipo_comprobante', Comprobante::TIPOS_VENTA)->orderBy('serie')->get(),
            'clientes' => Cliente::orderBy('razon_social')->get(['id', 'tipo_documento', 'numero_documento', 'razon_social']),
            'productos' => Producto::where('activo', true)->orderByRaw('LOWER(nombre)')->get(),
            'cajaAbierta' => Caja::abiertaDe(auth()->id()),
        ]);
    }

    public function store(Request $request, ComprobanteService $servicio)
    {
        $datos = $request->validate([
            'tipo_comprobante' => ['required', Rule::in(Comprobante::TIPOS_VENTA)],
            'serie' => ['required', 'exists:series,serie'],
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'metodo_pago' => ['required', Rule::in(array_keys(Caja::METODOS_PAGO))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ]);

        $comprobante = $servicio->emitir([...$datos, 'user_id' => $request->user()->id]);

        return redirect()->route('comprobantes.show', $comprobante)->with('ok', "{$comprobante->nombreTipo()} {$comprobante->numero()} emitida.");
    }

    public function show(Comprobante $comprobante)
    {
        $comprobante->load(['items', 'cliente', 'vendedor', 'referencia', 'notasCredito']);

        return view('comprobantes.show', compact('comprobante'));
    }

    public function notaCredito(Comprobante $comprobante)
    {
        abort_unless($comprobante->anulable() && $comprobante->tipo_comprobante !== Comprobante::RECIBO, 404);
        $comprobante->load('items');

        return view('comprobantes.nota-credito', [
            'comprobante' => $comprobante,
            'devolvibles' => $comprobante->cantidadesDevolvibles(),
        ]);
    }

    public function emitirNotaCredito(Request $request, Comprobante $comprobante, NotaCreditoService $servicio)
    {
        $datos = $request->validate([
            'motivo_codigo' => ['required', Rule::in(array_keys(Comprobante::MOTIVOS_NC))],
            'motivo_descripcion' => ['required', 'string', 'max:250'],
            'cantidades' => ['array'],
            'cantidades.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $nota = $servicio->emitir($comprobante, $datos['motivo_codigo'], $datos['motivo_descripcion'], $datos['cantidades'] ?? [], $request->user()->id);

        return redirect()->route('comprobantes.show', $nota)->with('ok', "Nota de crédito {$nota->numero()} emitida.");
    }

    public function anular(Request $request, Comprobante $comprobante, NotaCreditoService $servicio)
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'max:250']]);
        $servicio->anularRecibo($comprobante, $datos['motivo'], $request->user()->id);

        return back()->with('ok', "Recibo {$comprobante->numero()} anulado. El stock fue devuelto.");
    }

    public function reenviar(Comprobante $comprobante, SunatService $sunat)
    {
        abort_unless($comprobante->seEnviaASunat() && in_array($comprobante->estado_sunat, ['pendiente', 'error'], true), 422);
        $sunat->enviar($comprobante);

        return back()->with('ok', 'Reenvío a SUNAT: '.$comprobante->estado_sunat);
    }

    public function pdf(Comprobante $comprobante, string $formato, ComprobantePdf $pdf, Request $request)
    {
        $documento = $pdf->generar($comprobante, $formato);
        $nombre = $pdf->nombreArchivo($comprobante);

        return $request->boolean('descargar') ? $documento->download($nombre) : $documento->stream($nombre);
    }

    public function pdfPublico(Comprobante $comprobante, string $formato, ComprobantePdf $pdf)
    {
        return $pdf->generar($comprobante, $formato)->stream($pdf->nombreArchivo($comprobante));
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
