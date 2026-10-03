@extends('layouts.app')
@section('titulo', 'Dashboard')
@section('contenido')
<h1 class="text-xl font-semibold mb-4">Dashboard</h1>
@include('partials.filtro-periodo')
@include('partials.tarjetas-resumen')
<div class="grid lg:grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-lg shadow p-4 lg:col-span-2">
        <h2 class="font-semibold mb-2">Ventas {{ $periodo->desde->isSameDay($periodo->hasta) ? 'por hora' : 'por día' }}</h2>
        @include('partials.grafico-ventas', ['id' => 'grafico-dashboard'])
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-2">Por método de pago</h2>
        @forelse ($metodos as $m)
            <div class="flex justify-between border-b py-1.5 text-sm"><span>{{ \App\Models\Caja::METODOS_PAGO[$m->metodo_pago] ?? $m->metodo_pago }} <span class="text-slate-400">({{ $m->cantidad }})</span></span><span class="font-medium">S/ {{ number_format($m->total, 2) }}</span></div>
        @empty <p class="text-slate-500 text-sm">Sin ventas en el periodo.</p> @endforelse
        <h2 class="font-semibold mt-4 mb-2">Más vendidos</h2>
        @forelse ($masVendidos as $p)
            <div class="flex justify-between border-b py-1.5 text-sm"><span class="truncate mr-2">{{ $p->descripcion }}</span><span class="whitespace-nowrap">{{ $p->cantidad + 0 }} u.</span></div>
        @empty <p class="text-slate-500 text-sm">Sin ventas en el periodo.</p> @endforelse
    </div>
</div>
<div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex justify-between mb-2"><h2 class="font-semibold">Stock bajo</h2>@if ($totalStockBajo)<a href="{{ route('productos.index', ['stock_bajo' => 1]) }}" class="text-sm text-blue-700 hover:underline">Ver los {{ $totalStockBajo }}</a>@endif</div>
        @forelse ($stockBajo as $p)
            <div class="flex justify-between border-b py-1 text-sm"><a class="hover:underline truncate mr-2" href="{{ route('productos.movimientos', $p) }}">{{ $p->nombre }}</a><span class="text-red-600 whitespace-nowrap">{{ $p->stock + 0 }} / mín. {{ $p->stock_minimo + 0 }}</span></div>
        @empty <p class="text-slate-500 text-sm">Todo en orden.</p> @endforelse
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-2">Pendientes o con problemas en SUNAT</h2>
        @forelse ($pendientesSunat as $c)
            <div class="flex justify-between border-b py-1 text-sm"><a class="hover:underline" href="{{ route('comprobantes.show', $c) }}">{{ $c->numero() }}</a>@include('comprobantes.estado', ['estado' => $c->estado_sunat])</div>
        @empty <p class="text-slate-500 text-sm">Nada pendiente.</p> @endforelse
    </div>
</div>
@endsection
