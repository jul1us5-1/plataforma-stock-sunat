@extends('layouts.app')
@section('titulo', $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor')
@section('contenido')
<h1 class="text-xl font-semibold mb-4">{{ $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor' }}</h1>
<form method="POST" action="{{ $proveedor->exists ? route('proveedores.update', $proveedor) : route('proveedores.store') }}" x-data="consultaDocumento()" class="bg-white rounded-lg shadow p-4 grid sm:grid-cols-2 gap-4 max-w-3xl">
    @csrf @if ($proveedor->exists) @method('PUT') @endif
    @if (request('volver'))<input type="hidden" name="volver" value="{{ request('volver') }}">@endif
    <div>
        <label class="block" for="ruc">RUC</label>
        <div class="flex gap-2">
            <input id="ruc" name="ruc" x-ref="numero" value="{{ old('ruc', $proveedor->ruc) }}" maxlength="11" class="w-full border rounded px-3 py-2">
            <button type="button" @click="buscar($refs.numero.value)" :disabled="buscando" class="border rounded px-3 py-2 text-sm whitespace-nowrap" x-text="buscando ? 'Buscando…' : 'Buscar'"></button>
        </div>
        <p class="text-xs text-amber-700 mt-1" x-show="aviso" x-text="aviso"></p>
    </div>
    <label class="block">Razón social<input name="razon_social" value="{{ old('razon_social', $proveedor->razon_social) }}" required class="w-full border rounded px-3 py-2"></label>
    <label class="block">Contacto<input name="contacto" value="{{ old('contacto', $proveedor->contacto) }}" class="w-full border rounded px-3 py-2"></label>
    <label class="block">Teléfono<input name="telefono" value="{{ old('telefono', $proveedor->telefono) }}" class="w-full border rounded px-3 py-2"></label>
    <label class="block">Correo<input name="email" type="email" value="{{ old('email', $proveedor->email) }}" class="w-full border rounded px-3 py-2"></label>
    <label class="block">Dirección<input name="direccion" value="{{ old('direccion', $proveedor->direccion) }}" class="w-full border rounded px-3 py-2"></label>
    <div class="sm:col-span-2 flex gap-2"><button class="bg-slate-900 text-white rounded px-4 py-2">Guardar</button><a href="{{ route('proveedores.index') }}" class="px-4 py-2">Cancelar</a></div>
</form>
@include('partials.consulta-documento-js')
@endsection
