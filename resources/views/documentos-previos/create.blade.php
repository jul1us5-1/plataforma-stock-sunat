@extends('layouts.app')
@php($info = \App\Models\DocumentoPrevio::TIPOS[$tipo])
@section('titulo', 'Nuevo '.mb_strtolower($info['nombre']))
@section('contenido')
<form method="POST" action="{{ route('previos.store', $info['ruta']) }}"
      x-data="previo(@js($productos->map->only(['id', 'codigo', 'nombre', 'precio_venta', 'stock', 'unidad_medida'])))"
      @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
      class="bg-white rounded-lg shadow overflow-hidden">
    @csrf
    <div class="bg-blue-600 text-white px-5 py-3 text-lg">{{ $tipo === "pedido" ? "Nuevo" : "Nueva" }} {{ mb_strtolower($info["nombre"]) }}</div>
    <div class="p-5 space-y-5">
        <div class="grid sm:grid-cols-3 gap-4 text-sm">
            <label class="block text-blue-700 sm:col-span-2">Cliente
                <select name="cliente_id" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
                    <option value="">Clientes varios</option>
                    @foreach ($clientes as $c)<option value="{{ $c->id }}">{{ $c->numero_documento }} · {{ $c->razon_social }}</option>@endforeach
                </select>
            </label>
            <label class="block text-blue-700">{{ $info['limite'] }}
                <input type="date" name="fecha_limite" value="{{ old('fecha_limite', today()->addDays($tipo === 'cotizacion' ? 7 : 2)->format('Y-m-d')) }}" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
            </label>
        </div>
        <div class="relative max-w-xl" @click.outside="busqueda = ''">
            <input x-model="busqueda" x-ref="buscador" placeholder="Agregar producto: nombre o código, Enter agrega el primero" @keydown.enter.prevent="agregarPrimero()" class="w-full border rounded px-3 py-2 text-sm">
            <div x-show="busqueda" class="absolute z-10 mt-1 w-full bg-white border rounded shadow max-h-72 overflow-y-auto">
                <template x-for="p in filtrados" :key="p.id">
                    <button type="button" @click="agregar(p)" class="flex w-full text-left px-3 py-1.5 hover:bg-slate-100 text-sm gap-2">
                        <span class="font-mono text-slate-500" x-text="p.codigo"></span><span class="flex-1" x-text="p.nombre"></span><span x-text="'S/ ' + Number(p.precio_venta).toFixed(2)"></span>
                    </button>
                </template>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="text-left border-b"><tr><th class="py-2">Producto</th><th class="w-28">Cantidad</th><th class="w-32">Precio</th><th class="text-right w-28">Total</th><th class="w-8"></th></tr></thead>
            <tbody>
            <template x-for="(item, i) in items" :key="item.producto_id">
                <tr class="border-b">
                    <td class="py-2" x-text="item.nombre"></td>
                    <td class="pr-2"><input type="number" step="0.01" min="0.01" x-model.number="item.cantidad" :name="`items[${i}][cantidad]`" class="w-full border rounded px-2 py-1"></td>
                    <td class="pr-2"><input type="number" step="0.01" min="0" x-model.number="item.precio_unitario" :name="`items[${i}][precio_unitario]`" class="w-full border rounded px-2 py-1"></td>
                    <td class="text-right" x-text="(item.cantidad * item.precio_unitario).toFixed(2)"></td>
                    <td class="text-right"><input type="hidden" :name="`items[${i}][producto_id]`" :value="item.producto_id"><button type="button" @click="items.splice(i, 1)" class="text-red-600 px-2">✕</button></td>
                </tr>
            </template>
            </tbody>
        </table>
        <div class="text-right text-xl font-semibold">Total: S/ <span x-text="total.toFixed(2)"></span></div>
        <label class="block text-sm text-blue-700">Observaciones
            <textarea name="observaciones" rows="2" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">{{ old('observaciones') }}</textarea>
        </label>
        <button :disabled="items.length === 0" class="bg-blue-600 disabled:opacity-50 text-white rounded px-6 py-2">Guardar {{ mb_strtolower($info['nombre']) }}</button>
    </div>
</form>
<script>
function previo(productos) {
    return {
        productos, busqueda: '', items: [],
        get filtrados() {
            const q = this.busqueda.toLowerCase();
            return this.productos.filter(p => p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q)).slice(0, 30);
        },
        get total() { return this.items.reduce((s, i) => s + i.cantidad * i.precio_unitario, 0) },
        agregar(p) {
            const existente = this.items.find(i => i.producto_id === p.id);
            if (existente) existente.cantidad++;
            else this.items.push({ producto_id: p.id, nombre: p.nombre, cantidad: 1, precio_unitario: Number(p.precio_venta) });
            this.busqueda = '';
            this.$refs.buscador.focus();
        },
        agregarPrimero() { if (this.filtrados.length) this.agregar(this.filtrados[0]) },
    };
}
</script>
@endsection
