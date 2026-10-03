@extends('layouts.app')
@section('titulo', 'Clientes')
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4">
    <h1 class="text-xl font-semibold mr-auto">Clientes</h1>
    <form><input name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o documento" class="border rounded px-3 py-1.5"></form>
    <a href="{{ route('clientes.create') }}" class="bg-slate-900 text-white rounded px-3 py-1.5">+ Cliente</a>
</div>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">Documento</th><th class="p-2">Nombre / Razón social</th><th class="p-2">Dirección</th><th class="p-2"></th></tr></thead>
    <tbody>
    @forelse ($clientes as $c)
        <tr class="border-t">
            <td class="p-2">{{ \App\Models\Cliente::TIPOS_DOCUMENTO[$c->tipo_documento] ?? '' }} {{ $c->numero_documento }}</td>
            <td class="p-2">{{ $c->razon_social }}</td>
            <td class="p-2">{{ $c->direccion }}</td>
            <td class="p-2 text-right"><a href="{{ route('clientes.edit', $c) }}" class="text-blue-700 hover:underline">Editar</a></td>
        </tr>
    @empty
        <tr><td colspan="4" class="p-4 text-center text-slate-500">Aún no hay clientes.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $clientes->links() }}</div>
@endsection
