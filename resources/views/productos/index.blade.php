@extends('layouts.app')
@section('titulo', 'Productos')
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4" x-data="{ importar: false }">
    <h1 class="text-xl font-semibold mr-auto">Productos</h1>
    <a href="{{ route('productos.exportar') }}" class="bg-blue-600 hover:bg-blue-500 text-white rounded px-3 py-1.5 text-sm">Exportar</a>
    <button type="button" @click="importar = !importar" class="bg-blue-600 hover:bg-blue-500 text-white rounded px-3 py-1.5 text-sm">Importar</button>
    <a href="{{ route('productos.create') }}" class="bg-blue-600 hover:bg-blue-500 text-white rounded px-3 py-1.5 text-sm">+ Nuevo</a>
    <div x-show="importar" x-cloak class="w-full bg-white rounded-lg shadow p-4">
        <p class="text-sm text-slate-600 mb-2">Sube un Excel (.xlsx) o CSV con las columnas <code>codigo, nombre, precio_venta</code> y opcionales <code>stock, stock_minimo, precio_compra, categoria, unidad_medida, afectacion_igv, descripcion</code>. Si el código ya existe se actualiza. También acepta tal cual el reporte de productos exportado de MYPEFACT.</p>
        <form method="POST" action="{{ route('productos.importar') }}" enctype="multipart/form-data" class="flex flex-wrap gap-2">
            @csrf <input type="file" name="archivo" accept=".xlsx,.csv,.txt" required class="text-sm"> <button class="bg-slate-900 text-white rounded px-3 py-1.5 text-sm">Importar</button>
        </form>
    </div>
</div>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="bg-blue-600 text-white px-5 py-3 text-lg">Listado de productos</div>
    <form class="flex flex-wrap items-center gap-2 p-4">
        <label class="text-sm text-slate-600">Filtrar por</label>
        <select name="campo" class="border rounded px-3 py-1.5 text-sm">
            <option value="nombre" @selected(request('campo') !== 'codigo')>Nombre</option>
            <option value="codigo" @selected(request('campo') === 'codigo')>Código interno</option>
        </select>
        <input name="q" value="{{ request('q') }}" placeholder="Buscar" class="border rounded px-3 py-1.5 text-sm flex-1 min-w-48">
        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="stock_bajo" value="1" @checked(request('stock_bajo'))> Solo stock bajo</label>
        <button class="border rounded px-3 py-1.5 text-sm">Buscar</button>
        <span class="text-sm text-slate-500 ml-auto">{{ $productos->total() }} productos</span>
    </form>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-600 border-y"><tr><th class="p-2">#</th><th class="p-2">Cód. interno</th><th class="p-2">Unidad</th><th class="p-2">Nombre</th><th class="p-2 text-center">Historial</th><th class="p-2 text-right">Stock</th><th class="p-2 text-right">P. unitario (venta)</th><th class="p-2 text-center">Tiene IGV</th><th class="p-2"></th></tr></thead>
        <tbody>
        @forelse ($productos as $p)
            <tr class="border-b hover:bg-slate-50 {{ $p->activo ? '' : 'opacity-50' }}">
                <td class="p-2 text-slate-500">{{ $productos->firstItem() + $loop->index }}</td>
                <td class="p-2 font-mono">{{ $p->codigo }}</td>
                <td class="p-2">{{ $p->unidad_medida }}</td>
                <td class="p-2">{{ $p->nombre }}</td>
                <td class="p-2 text-center"><a href="{{ route('productos.movimientos', $p) }}" class="inline-block bg-blue-600 text-white rounded px-2 py-0.5 text-xs" title="Kardex">Kardex</a></td>
                <td class="p-2 text-right {{ $p->stockBajo() ? 'text-red-600 font-semibold' : '' }}">{{ $p->esServicio() ? '—' : $p->stock + 0 }}</td>
                <td class="p-2 text-right">S/ {{ number_format($p->precio_venta, 2) }}</td>
                <td class="p-2 text-center">{{ $p->afectacion_igv === '10' ? 'Sí' : 'No' }}</td>
                <td class="p-2 text-right"><a href="{{ route('productos.edit', $p) }}" class="text-blue-700 hover:underline">Editar</a></td>
            </tr>
        @empty
            <tr><td colspan="9" class="p-4 text-center text-slate-500">No hay productos con ese filtro.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="p-4">{{ $productos->links() }}</div>
</div>
@endsection
