@extends('layouts.app')
@section('titulo', 'Nueva venta')
@section('contenido')
<h1 class="text-xl font-semibold mb-4">Nueva venta</h1>
<form method="POST" action="{{ route('comprobantes.store') }}"
      x-data="venta(@js($productos->map->only(['id', 'codigo', 'nombre', 'precio_venta', 'stock', 'unidad_medida'])), @js($series->map->only(['serie', 'tipo_comprobante'])))"
      class="space-y-4">
    @csrf
    <div class="bg-white rounded-lg shadow p-4 grid sm:grid-cols-3 gap-4">
        <label class="block">Comprobante
            <select name="tipo_comprobante" x-model="tipo" class="w-full border rounded px-3 py-2">
                @foreach (\App\Models\Comprobante::TIPOS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select>
        </label>
        <label class="block">Serie
            <select name="serie" class="w-full border rounded px-3 py-2">
                <template x-for="s in seriesDelTipo" :key="s.serie"><option :value="s.serie" x-text="s.serie"></option></template>
            </select>
        </label>
        <label class="block">Cliente <span class="text-xs text-slate-500" x-show="tipo === '01'">(con RUC)</span>
            <select name="cliente_id" class="w-full border rounded px-3 py-2">
                <option value="">Clientes varios</option>
                @foreach ($clientes as $c)<option value="{{ $c->id }}">{{ $c->numero_documento }} · {{ $c->razon_social }}</option>@endforeach
            </select>
            <a href="{{ route('clientes.create') }}" class="text-xs text-blue-700 hover:underline">+ nuevo cliente</a>
        </label>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <input x-model="busqueda" placeholder="Buscar producto por nombre o código y presiona Enter" @keydown.enter.prevent="agregarPrimero()" class="w-full border rounded px-3 py-2 mb-2">
        <div class="max-h-48 overflow-y-auto" x-show="busqueda">
            <template x-for="p in filtrados" :key="p.id">
                <button type="button" @click="agregar(p)" class="block w-full text-left px-3 py-1 hover:bg-slate-100">
                    <span class="font-mono" x-text="p.codigo"></span> · <span x-text="p.nombre"></span> · S/ <span x-text="Number(p.precio_venta).toFixed(2)"></span>
                    <span class="text-slate-500" x-show="p.unidad_medida !== 'ZZ'">(stock <span x-text="Number(p.stock)"></span>)</span>
                </button>
            </template>
        </div>
        <table class="w-full text-sm mt-2">
            <thead class="text-left bg-slate-50"><tr><th class="p-2">Producto</th><th class="p-2 w-28">Cantidad</th><th class="p-2 w-32">Precio</th><th class="p-2 text-right w-28">Subtotal</th><th class="w-8"></th></tr></thead>
            <tbody>
            <template x-for="(item, i) in items" :key="item.producto_id">
                <tr class="border-t">
                    <td class="p-2" x-text="item.nombre"></td>
                    <td class="p-2"><input type="number" step="0.01" min="0.01" x-model.number="item.cantidad" :name="`items[${i}][cantidad]`" class="w-full border rounded px-2 py-1"></td>
                    <td class="p-2"><input type="number" step="0.01" min="0" x-model.number="item.precio_unitario" :name="`items[${i}][precio_unitario]`" class="w-full border rounded px-2 py-1"></td>
                    <td class="p-2 text-right" x-text="'S/ ' + (item.cantidad * item.precio_unitario).toFixed(2)"></td>
                    <td><input type="hidden" :name="`items[${i}][producto_id]`" :value="item.producto_id"><button type="button" @click="items.splice(i, 1)" class="text-red-600 px-2">✕</button></td>
                </tr>
            </template>
            </tbody>
        </table>
        <div class="text-right text-xl font-semibold mt-4">Total: S/ <span x-text="total.toFixed(2)"></span></div>
    </div>
    <button :disabled="items.length === 0" class="bg-emerald-600 disabled:opacity-50 text-white rounded px-6 py-2 font-medium">Emitir comprobante</button>
</form>
<script>
function venta(productos, series) {
    return {
        productos, series, tipo: '03', busqueda: '', items: [],
        get seriesDelTipo() { return this.series.filter(s => s.tipo_comprobante === this.tipo) },
        get filtrados() {
            const q = this.busqueda.toLowerCase();
            return this.productos.filter(p => p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q)).slice(0, 20);
        },
        get total() { return this.items.reduce((s, i) => s + i.cantidad * i.precio_unitario, 0) },
        agregar(p) {
            const existente = this.items.find(i => i.producto_id === p.id);
            if (existente) existente.cantidad++;
            else this.items.push({ producto_id: p.id, nombre: p.nombre, cantidad: 1, precio_unitario: Number(p.precio_venta) });
            this.busqueda = '';
        },
        agregarPrimero() { if (this.filtrados.length) this.agregar(this.filtrados[0]) },
    };
}
</script>
@endsection
