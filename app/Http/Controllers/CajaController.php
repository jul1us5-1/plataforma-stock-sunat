<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        $caja = Caja::abiertaDe($request->user()->id);

        return view('caja.index', [
            'caja' => $caja?->load(['movimientos' => fn ($q) => $q->latest('id')]),
            'historial' => Caja::with('usuario')->whereNotNull('cerrada_en')->latest('id')->paginate(15),
        ]);
    }

    public function abrir(Request $request)
    {
        $datos = $request->validate(['monto_apertura' => ['required', 'numeric', 'min:0']]);

        if (Caja::abiertaDe($request->user()->id)) {
            return back()->withErrors(['caja' => 'Ya tienes una caja abierta.']);
        }

        Caja::create(['user_id' => $request->user()->id, 'abierta_en' => now(), ...$datos]);

        return redirect()->route('caja.index')->with('ok', 'Caja abierta.');
    }

    public function movimiento(Request $request)
    {
        $caja = Caja::abiertaDe($request->user()->id);
        abort_unless($caja, 422, 'No tienes una caja abierta.');

        $caja->movimientos()->create($request->validate([
            'tipo' => ['required', Rule::in(['ingreso', 'egreso'])],
            'concepto' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'metodo_pago' => ['required', Rule::in(array_keys(Caja::METODOS_PAGO))],
        ]));

        return back()->with('ok', 'Movimiento registrado.');
    }

    public function cerrar(Request $request)
    {
        $caja = Caja::abiertaDe($request->user()->id);
        abort_unless($caja, 422, 'No tienes una caja abierta.');

        $datos = $request->validate([
            'efectivo_contado' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);
        $caja->cerrar((float) $datos['efectivo_contado'], $datos['observaciones'] ?? null);

        return redirect()->route('caja.show', $caja)->with('ok', 'Caja cerrada.');
    }

    public function show(Caja $caja)
    {
        return view('caja.show', ['caja' => $caja->load(['usuario', 'movimientos.comprobante'])]);
    }
}
