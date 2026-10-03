@extends('layouts.app')
@section('contenido')
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Ventas de hoy</div><div class="text-2xl font-semibold">S/ {{ number_format($ventasHoy, 2) }}</div></div>
    <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Comprobantes de hoy</div><div class="text-2xl font-semibold">{{ $comprobantesHoy }}</div></div>
    <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Productos activos</div><div class="text-2xl font-semibold">{{ $totalProductos }}</div></div>
</div>
<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-2">Stock bajo</h2>
        @forelse ($stockBajo as $p)
            <div class="flex justify-between border-b py-1"><a class="hover:underline" href="{{ route('productos.movimientos', $p) }}">{{ $p->nombre }}</a><span class="text-red-600">{{ $p->stock + 0 }} / mín. {{ $p->stock_minimo + 0 }}</span></div>
        @empty <p class="text-slate-500">Todo en orden.</p> @endforelse
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-2">Pendientes o con problemas en SUNAT</h2>
        @forelse ($pendientesSunat as $c)
            <div class="flex justify-between border-b py-1"><a class="hover:underline" href="{{ route('comprobantes.show', $c) }}">{{ $c->numero() }}</a>@include('comprobantes.estado', ['estado' => $c->estado_sunat])</div>
        @empty <p class="text-slate-500">Nada pendiente.</p> @endforelse
    </div>
</div>
@endsection
