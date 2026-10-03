@extends('layouts.app')
@section('titulo', 'Productos')
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4">
    <h1 class="text-xl font-semibold mr-auto">Productos</h1>
    <form class="flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o código" class="border rounded px-3 py-1.5">
        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="stock_bajo" value="1" @checked(request('stock_bajo'))> Stock bajo</label>
        <button class="border rounded px-3 py-1.5 bg-white">Filtrar</button>
    </form>
    <a href="{{ route('productos.create') }}" class="bg-slate-900 text-white rounded px-3 py-1.5">+ Producto</a>
</div>
<details class="mb-4 bg-white rounded-lg shadow p-4">
    <summary class="cursor-pointer font-medium">Importar productos desde CSV</summary>
    <p class="text-sm text-slate-600 my-2">Columnas: <code>codigo, nombre, precio_venta, stock</code> y opcionales <code>stock_minimo, categoria, unidad_medida, afectacion_igv, descripcion</code>. Si el código ya existe se actualiza.</p>
    <form method="POST" action="{{ route('productos.importar') }}" enctype="multipart/form-data" class="flex gap-2">
        @csrf <input type="file" name="archivo" accept=".csv,.txt" required> <button class="bg-slate-900 text-white rounded px-3 py-1.5">Importar</button>
    </form>
</details>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">Código</th><th class="p-2">Nombre</th><th class="p-2">Categoría</th><th class="p-2 text-right">Precio</th><th class="p-2 text-right">Stock</th><th class="p-2"></th></tr></thead>
    <tbody>
    @forelse ($productos as $p)
        <tr class="border-t {{ $p->activo ? '' : 'opacity-50' }}">
            <td class="p-2 font-mono">{{ $p->codigo }}</td>
            <td class="p-2">{{ $p->nombre }}</td>
            <td class="p-2">{{ $p->categoria }}</td>
            <td class="p-2 text-right">S/ {{ number_format($p->precio_venta, 2) }}</td>
            <td class="p-2 text-right {{ $p->stockBajo() ? 'text-red-600 font-semibold' : '' }}">{{ $p->esServicio() ? '—' : $p->stock + 0 }}</td>
            <td class="p-2 text-right whitespace-nowrap">
                <a href="{{ route('productos.movimientos', $p) }}" class="text-blue-700 hover:underline">Kardex</a> ·
                <a href="{{ route('productos.edit', $p) }}" class="text-blue-700 hover:underline">Editar</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-center text-slate-500">Aún no hay productos. Créalos o impórtalos desde CSV.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $productos->links() }}</div>
@endsection
