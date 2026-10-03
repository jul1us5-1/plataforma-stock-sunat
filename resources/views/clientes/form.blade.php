@extends('layouts.app')
@section('titulo', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('contenido')
<h1 class="text-xl font-semibold mb-4">{{ $cliente->exists ? 'Editar cliente' : 'Nuevo cliente' }}</h1>
<form method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}" x-data="consultaDocumento()" class="bg-white rounded-lg shadow p-4 grid sm:grid-cols-2 gap-4 max-w-3xl">
    @csrf @if ($cliente->exists) @method('PUT') @endif
    @if (request('volver'))<input type="hidden" name="volver" value="{{ request('volver') }}">@endif
    <label class="block">Tipo de documento
        <select name="tipo_documento" class="w-full border rounded px-3 py-2">
            @foreach (\App\Models\Cliente::TIPOS_DOCUMENTO as $k => $v)
                <option value="{{ $k }}" @selected(old('tipo_documento', $cliente->tipo_documento) == $k)>{{ $v }}</option>
            @endforeach
        </select>
    </label>
    <div>
        <label class="block" for="numero_documento">Número</label>
        <div class="flex gap-2">
            <input id="numero_documento" name="numero_documento" x-ref="numero" value="{{ old('numero_documento', $cliente->numero_documento) }}" required class="w-full border rounded px-3 py-2">
            <button type="button" @click="buscar($refs.numero.value)" :disabled="buscando" class="border rounded px-3 py-2 text-sm whitespace-nowrap" x-text="buscando ? 'Buscando…' : 'Buscar'"></button>
        </div>
        <p class="text-xs text-amber-700 mt-1" x-show="aviso" x-text="aviso"></p>
    </div>
    <label class="block sm:col-span-2">Nombre o razón social<input name="razon_social" value="{{ old('razon_social', $cliente->razon_social) }}" required class="w-full border rounded px-3 py-2"></label>
    <label class="block sm:col-span-2">Dirección<input name="direccion" value="{{ old('direccion', $cliente->direccion) }}" class="w-full border rounded px-3 py-2"></label>
    <label class="block">Correo<input name="email" type="email" value="{{ old('email', $cliente->email) }}" class="w-full border rounded px-3 py-2"></label>
    <label class="block">Teléfono<input name="telefono" value="{{ old('telefono', $cliente->telefono) }}" class="w-full border rounded px-3 py-2"></label>
    <div class="sm:col-span-2 flex gap-2"><button class="bg-slate-900 text-white rounded px-4 py-2">Guardar</button><a href="{{ route('clientes.index') }}" class="px-4 py-2">Cancelar</a></div>
</form>
@include('partials.consulta-documento-js')
@endsection
