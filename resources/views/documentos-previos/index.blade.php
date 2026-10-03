@extends('layouts.app')
@php($info = \App\Models\DocumentoPrevio::TIPOS[$tipo])
@section('titulo', $info['plural'])
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4">
    <h1 class="text-xl font-semibold mr-auto">{{ $info['plural'] }}</h1>
    <form class="flex gap-2">
        <select name="estado" class="border rounded px-3 py-1.5 text-sm" onchange="this.form.submit()">
            <option value="">Todos</option>
            @foreach (['pendiente' => 'Pendientes', 'convertido' => 'Convertidos en venta', 'anulado' => 'Anulados'] as $k => $v)<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>@endforeach
        </select>
    </form>
    <a href="{{ route('previos.create', $info['ruta']) }}" class="bg-blue-600 text-white rounded px-3 py-1.5 text-sm">+ Nuevo</a>
</div>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">Número</th><th class="p-2">Fecha</th><th class="p-2">Cliente</th><th class="p-2">{{ $info['limite'] }}</th><th class="p-2 text-right">Total</th><th class="p-2">Estado</th></tr></thead>
    <tbody>
    @forelse ($documentos as $d)
        <tr class="border-t">
            <td class="p-2 font-mono"><a class="text-blue-700 hover:underline" href="{{ route('previos.show', [$d->ruta(), $d]) }}">{{ $d->codigo() }}</a></td>
            <td class="p-2">{{ $d->fecha->format('d/m/Y') }}</td>
            <td class="p-2">{{ $d->cliente?->razon_social ?? 'Clientes varios' }}</td>
            <td class="p-2">{{ $d->fecha_limite?->format('d/m/Y') }}</td>
            <td class="p-2 text-right">S/ {{ number_format($d->total, 2) }}</td>
            <td class="p-2">@include('documentos-previos.estado', ['estado' => $d->estado])</td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-center text-slate-500">Aún no hay {{ mb_strtolower($info['plural']) }}.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $documentos->links() }}</div>
@endsection
