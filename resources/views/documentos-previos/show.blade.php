@extends('layouts.app')
@section('titulo', $documento->codigo())
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4">
    <a href="{{ route('previos.index', $documento->ruta()) }}" class="text-blue-700 hover:underline mr-auto">← {{ \App\Models\DocumentoPrevio::TIPOS[$documento->tipo]['plural'] }}</a>
    @if ($documento->pendiente())
        <form method="POST" action="{{ route('previos.anular', [$documento->ruta(), $documento]) }}" onsubmit="return confirm('¿Anular?')">@csrf<button class="border border-red-300 text-red-700 bg-white rounded px-3 py-1.5">Anular</button></form>
        <a href="{{ route('comprobantes.create', ['previo' => $documento->id]) }}" class="bg-emerald-600 text-white rounded px-3 py-1.5">Convertir en venta</a>
    @endif
    <a href="{{ route('previos.pdf', [$documento->ruta(), $documento]) }}" target="_blank" class="bg-slate-900 text-white rounded px-3 py-1.5">PDF</a>
</div>
<div class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
    <div class="flex justify-between mb-4">
        <h1 class="text-xl font-semibold">{{ $documento->nombreTipo() }} {{ $documento->codigo() }}</h1>
        @include('documentos-previos.estado', ['estado' => $documento->estado])
    </div>
    <div class="text-sm grid grid-cols-2 gap-1 mb-4">
        <div><strong>Cliente:</strong> {{ $documento->cliente?->razon_social ?? 'Clientes varios' }}</div>
        <div><strong>Fecha:</strong> {{ $documento->fecha->format('d/m/Y') }}</div>
        @if ($documento->fecha_limite)<div><strong>{{ \App\Models\DocumentoPrevio::TIPOS[$documento->tipo]['limite'] }}:</strong> {{ $documento->fecha_limite->format('d/m/Y') }}</div>@endif
        @if ($documento->vendedor)<div><strong>Vendedor:</strong> {{ $documento->vendedor->name }}</div>@endif
        @if ($documento->comprobante)<div><strong>Venta:</strong> <a class="text-blue-700 hover:underline" href="{{ route('comprobantes.show', $documento->comprobante) }}">{{ $documento->comprobante->numero() }}</a></div>@endif
    </div>
    <table class="w-full text-sm mb-4">
        <thead class="border-b-2 text-left"><tr><th class="py-1">Cant.</th><th>Descripción</th><th class="text-right">P. unit.</th><th class="text-right">Importe</th></tr></thead>
        <tbody>
        @foreach ($documento->items as $item)
            <tr class="border-b"><td class="py-1">{{ $item->cantidad + 0 }}</td><td>{{ $item->descripcion }}</td><td class="text-right">{{ number_format($item->precio_unitario, 2) }}</td><td class="text-right">{{ number_format($item->total, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="text-right text-lg font-semibold">Total: S/ {{ number_format($documento->total, 2) }}</div>
    @if ($documento->observaciones)<p class="text-sm mt-4"><strong>Observaciones:</strong> {{ $documento->observaciones }}</p>@endif
</div>
@endsection
