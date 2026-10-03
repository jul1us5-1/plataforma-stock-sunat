@extends('layouts.app')
@section('titulo', 'Comprobantes')
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4">
    <h1 class="text-xl font-semibold mr-auto">Comprobantes</h1>
    <form class="flex gap-2">
        <select name="tipo" class="border rounded px-3 py-1.5"><option value="">Todos los tipos</option>
            @foreach (\App\Models\Comprobante::TIPOS as $k => $v)<option value="{{ $k }}" @selected(request('tipo') === $k)>{{ $v }}</option>@endforeach
        </select>
        <select name="estado" class="border rounded px-3 py-1.5"><option value="">Todos los estados</option>
            @foreach (['pendiente', 'aceptado', 'observado', 'rechazado', 'error', 'no_aplica'] as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ $e }}</option>@endforeach
        </select>
        <button class="border rounded px-3 py-1.5 bg-white">Filtrar</button>
    </form>
</div>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">Número</th><th class="p-2">Tipo</th><th class="p-2">Fecha</th><th class="p-2">Cliente</th><th class="p-2 text-right">Total</th><th class="p-2">SUNAT</th></tr></thead>
    <tbody>
    @forelse ($comprobantes as $c)
        <tr class="border-t">
            <td class="p-2 font-mono"><a class="text-blue-700 hover:underline" href="{{ route('comprobantes.show', $c) }}">{{ $c->numero() }}</a></td>
            <td class="p-2">{{ $c->nombreTipo() }}</td>
            <td class="p-2">{{ $c->fecha_emision->format('d/m/Y H:i') }}</td>
            <td class="p-2">{{ $c->cliente?->razon_social ?? 'Clientes varios' }}</td>
            <td class="p-2 text-right">S/ {{ number_format($c->total, 2) }}</td>
            <td class="p-2">@include('comprobantes.estado', ['estado' => $c->estado_sunat])</td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-center text-slate-500">Aún no hay comprobantes.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $comprobantes->links() }}</div>
@endsection
