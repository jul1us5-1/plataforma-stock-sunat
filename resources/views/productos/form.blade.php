@extends('layouts.app')
@section('titulo', $producto->exists ? 'Editar producto' : 'Nuevo producto')
@section('contenido')
<h1 class="text-xl font-semibold mb-4">{{ $producto->exists ? 'Editar producto' : 'Nuevo producto' }}</h1>
<form method="POST" action="{{ $producto->exists ? route('productos.update', $producto) : route('productos.store') }}" class="bg-white rounded-lg shadow p-4 grid sm:grid-cols-2 gap-4 max-w-3xl">
    @csrf @if ($producto->exists) @method('PUT') @endif
    <label class="block">Código<input name="codigo" value="{{ old('codigo', $producto->codigo) }}" required class="w-full border rounded px-3 py-2"></label>
    <label class="block">Nombre<input name="nombre" value="{{ old('nombre', $producto->nombre) }}" required class="w-full border rounded px-3 py-2"></label>
    <label class="block">Categoría<input name="categoria" value="{{ old('categoria', $producto->categoria) }}" class="w-full border rounded px-3 py-2"></label>
    <label class="block">Precio de venta (IGV incluido)<input name="precio_venta" type="number" step="0.01" min="0" value="{{ old('precio_venta', $producto->precio_venta) }}" required class="w-full border rounded px-3 py-2"></label>
    <label class="block">Unidad
        <select name="unidad_medida" class="w-full border rounded px-3 py-2">
            @foreach (['NIU' => 'Unidad', 'ZZ' => 'Servicio', 'KGM' => 'Kilogramo', 'LTR' => 'Litro', 'MTR' => 'Metro', 'BX' => 'Caja', 'DZN' => 'Docena'] as $k => $v)
                <option value="{{ $k }}" @selected(old('unidad_medida', $producto->unidad_medida) === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">IGV
        <select name="afectacion_igv" class="w-full border rounded px-3 py-2">
            @foreach (['10' => 'Gravado (18%)', '20' => 'Exonerado', '30' => 'Inafecto'] as $k => $v)
                <option value="{{ $k }}" @selected(old('afectacion_igv', $producto->afectacion_igv) === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </label>
    @unless ($producto->exists)
        <label class="block">Stock inicial<input name="stock" type="number" step="0.01" value="{{ old('stock', 0) }}" class="w-full border rounded px-3 py-2"></label>
    @endunless
    <label class="block">Stock mínimo<input name="stock_minimo" type="number" step="0.01" min="0" value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}" class="w-full border rounded px-3 py-2"></label>
    <label class="block sm:col-span-2">Descripción<textarea name="descripcion" class="w-full border rounded px-3 py-2">{{ old('descripcion', $producto->descripcion) }}</textarea></label>
    <input type="hidden" name="activo" value="0">
    <label class="flex items-center gap-2"><input type="checkbox" name="activo" value="1" @checked(old('activo', $producto->activo ?? true))> Activo</label>
    <div class="sm:col-span-2 flex gap-2"><button class="bg-slate-900 text-white rounded px-4 py-2">Guardar</button><a href="{{ route('productos.index') }}" class="px-4 py-2">Cancelar</a></div>
</form>
@endsection
