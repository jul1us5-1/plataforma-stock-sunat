@extends('layouts.app')
@section('titulo', 'Caja')
@section('contenido')
<h1 class="text-xl font-semibold mb-4">Caja</h1>
@if (! $caja)
    <form method="POST" action="{{ route('caja.abrir') }}" class="bg-white rounded-lg shadow p-4 flex flex-wrap gap-2 items-end max-w-md">
        @csrf
        <label class="flex-1">Efectivo inicial<input name="monto_apertura" type="number" step="0.01" min="0" value="0" required class="block w-full border rounded px-3 py-2"></label>
        <button class="bg-emerald-600 text-white rounded px-4 py-2">Abrir caja</button>
    </form>
@else
    <div class="grid md:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Abierta desde</div><div class="text-lg font-semibold">{{ $caja->abierta_en->format('d/m/Y H:i') }}</div></div>
        <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Efectivo inicial</div><div class="text-lg font-semibold">S/ {{ number_format($caja->monto_apertura, 2) }}</div></div>
        <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Efectivo esperado</div><div class="text-lg font-semibold">S/ {{ number_format($caja->efectivoEsperado(), 2) }}</div></div>
    </div>
    <div class="grid md:grid-cols-2 gap-4 mb-4">
        <form method="POST" action="{{ route('caja.movimiento') }}" class="bg-white rounded-lg shadow p-4 grid grid-cols-2 gap-2">
            @csrf
            <h2 class="col-span-2 font-semibold">Ingreso o gasto</h2>
            <select name="tipo" class="border rounded px-3 py-2"><option value="egreso">Gasto / retiro</option><option value="ingreso">Ingreso</option></select>
            <select name="metodo_pago" class="border rounded px-3 py-2">@foreach (\App\Models\Caja::METODOS_PAGO as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
            <input name="concepto" placeholder="Concepto" required class="border rounded px-3 py-2">
            <input name="monto" type="number" step="0.01" min="0.01" placeholder="Monto" required class="border rounded px-3 py-2">
            <button class="col-span-2 bg-slate-900 text-white rounded px-4 py-2">Registrar</button>
        </form>
        <form method="POST" action="{{ route('caja.cerrar') }}" class="bg-white rounded-lg shadow p-4 space-y-2" onsubmit="return confirm('¿Cerrar la caja?')">
            @csrf
            <h2 class="font-semibold">Cerrar caja</h2>
            <label class="block">Efectivo contado<input name="efectivo_contado" type="number" step="0.01" min="0" required class="block w-full border rounded px-3 py-2"></label>
            <input name="observaciones" placeholder="Observaciones" class="block w-full border rounded px-3 py-2">
            <button class="w-full bg-red-600 text-white rounded px-4 py-2">Cerrar caja</button>
        </form>
    </div>
    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">@include('caja.resumen')</div>
        <div class="bg-white rounded-lg shadow p-4 md:col-span-2 overflow-x-auto">@include('caja.movimientos', ['movimientos' => $caja->movimientos])</div>
    </div>
@endif

<h2 class="font-semibold mt-6 mb-2">Cajas cerradas</h2>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">Usuario</th><th class="p-2">Apertura</th><th class="p-2">Cierre</th><th class="p-2 text-right">Esperado</th><th class="p-2 text-right">Contado</th><th class="p-2 text-right">Diferencia</th></tr></thead>
    <tbody>
    @forelse ($historial as $c)
        <tr class="border-t">
            <td class="p-2"><a class="text-blue-700 hover:underline" href="{{ route('caja.show', $c) }}">{{ $c->usuario->name }}</a></td>
            <td class="p-2">{{ $c->abierta_en->format('d/m/Y H:i') }}</td>
            <td class="p-2">{{ $c->cerrada_en->format('d/m/Y H:i') }}</td>
            <td class="p-2 text-right">S/ {{ number_format($c->efectivo_esperado, 2) }}</td>
            <td class="p-2 text-right">S/ {{ number_format($c->efectivo_contado, 2) }}</td>
            <td class="p-2 text-right {{ $c->diferencia < 0 ? 'text-red-600' : ($c->diferencia > 0 ? 'text-amber-600' : '') }}">S/ {{ number_format($c->diferencia, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-center text-slate-500">Aún no hay cajas cerradas.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $historial->links() }}</div>
@endsection
