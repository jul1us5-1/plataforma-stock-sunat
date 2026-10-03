@extends('layouts.app')
@section('titulo', 'Proveedores')
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4">
    <h1 class="text-xl font-semibold mr-auto">Proveedores</h1>
    <form><input name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o RUC" class="border rounded px-3 py-1.5"></form>
    <a href="{{ route('proveedores.create') }}" class="bg-blue-600 text-white rounded px-3 py-1.5 text-sm">+ Proveedor</a>
</div>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">RUC</th><th class="p-2">Razón social</th><th class="p-2">Contacto</th><th class="p-2">Teléfono</th><th class="p-2 text-right">Compras</th><th class="p-2"></th></tr></thead>
    <tbody>
    @forelse ($proveedores as $p)
        <tr class="border-t">
            <td class="p-2 font-mono">{{ $p->ruc }}</td><td class="p-2">{{ $p->razon_social }}</td><td class="p-2">{{ $p->contacto }}</td><td class="p-2">{{ $p->telefono }}</td>
            <td class="p-2 text-right"><a class="text-blue-700 hover:underline" href="{{ route('compras.index', ['proveedor_id' => $p->id]) }}">{{ $p->compras_count }}</a></td>
            <td class="p-2 text-right"><a href="{{ route('proveedores.edit', $p) }}" class="text-blue-700 hover:underline">Editar</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-center text-slate-500">Aún no hay proveedores.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $proveedores->links() }}</div>
@endsection
