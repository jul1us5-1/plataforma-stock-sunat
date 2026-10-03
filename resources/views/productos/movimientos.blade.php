@extends('layouts.app')
@section('titulo', 'Kardex '.$producto->nombre)
@section('contenido')
<h1 class="text-xl font-semibold">{{ $producto->nombre }} <span class="text-slate-500 font-mono text-base">{{ $producto->codigo }}</span></h1>
<p class="mb-4">Stock actual: <strong>{{ $producto->stock + 0 }}</strong></p>
<form method="POST" action="{{ route('productos.stock', $producto) }}" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap gap-2 items-end">
    @csrf
    <label>Tipo<select name="tipo" class="block border rounded px-3 py-2"><option value="entrada">Entrada (compra)</option><option value="salida">Salida (merma)</option><option value="ajuste">Ajuste (+/-)</option></select></label>
    <label>Cantidad<input name="cantidad" type="number" step="0.01" required class="block border rounded px-3 py-2"></label>
    <label class="flex-1">Motivo<input name="motivo" class="block w-full border rounded px-3 py-2"></label>
    <button class="bg-slate-900 text-white rounded px-4 py-2">Registrar</button>
</form>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">Fecha</th><th class="p-2">Tipo</th><th class="p-2">Motivo</th><th class="p-2 text-right">Cantidad</th><th class="p-2 text-right">Saldo</th></tr></thead>
    <tbody>
    @foreach ($movimientos as $m)
        <tr class="border-t">
            <td class="p-2">{{ $m->created_at->format('d/m/Y H:i') }}</td>
            <td class="p-2 capitalize">{{ $m->tipo }}</td>
            <td class="p-2">@if ($m->comprobante)<a class="text-blue-700 hover:underline" href="{{ route('comprobantes.show', $m->comprobante) }}">{{ $m->motivo }}</a>@else{{ $m->motivo }}@endif</td>
            <td class="p-2 text-right {{ $m->cantidad < 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ $m->cantidad + 0 }}</td>
            <td class="p-2 text-right">{{ $m->stock_resultante + 0 }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="mt-4">{{ $movimientos->links() }}</div>
@endsection
