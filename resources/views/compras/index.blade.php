@extends('layouts.app')
@section('titulo', 'Compras')
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4">
    <h1 class="text-xl font-semibold mr-auto">Compras</h1>
    <form>
        <select name="proveedor_id" class="border rounded px-3 py-1.5 text-sm" onchange="this.form.submit()">
            <option value="">Todos los proveedores</option>
            @foreach ($proveedores as $p)<option value="{{ $p->id }}" @selected(request('proveedor_id') == $p->id)>{{ $p->razon_social }}</option>@endforeach
        </select>
    </form>
    <a href="{{ route('compras.create') }}" class="bg-blue-600 text-white rounded px-3 py-1.5 text-sm">+ Registrar compra</a>
</div>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">Fecha</th><th class="p-2">Documento</th><th class="p-2">Proveedor</th><th class="p-2 text-right">Total</th><th class="p-2">Estado</th></tr></thead>
    <tbody>
    @forelse ($compras as $c)
        <tr class="border-t {{ $c->estado === 'anulada' ? 'opacity-50' : '' }}">
            <td class="p-2">{{ $c->fecha->format('d/m/Y') }}</td>
            <td class="p-2"><a class="text-blue-700 hover:underline" href="{{ route('compras.show', $c) }}">{{ $c->descripcion() }}</a></td>
            <td class="p-2">{{ $c->proveedor->razon_social }}</td>
            <td class="p-2 text-right">S/ {{ number_format($c->total, 2) }}</td>
            <td class="p-2">{{ ucfirst($c->estado) }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="p-4 text-center text-slate-500">Aún no hay compras registradas.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $compras->links() }}</div>
@endsection
