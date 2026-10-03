@extends('layouts.app')
@section('titulo', 'Registrar compra')
@section('contenido')
@if ($proveedores->isEmpty())
    <div class="mb-4 rounded bg-amber-100 border border-amber-300 px-4 py-2">Primero registra un proveedor. <a class="underline" href="{{ route('proveedores.create', ['volver' => route('compras.create')]) }}">Nuevo proveedor</a></div>
@endif
<form method="POST" action="{{ route('compras.store') }}"
      x-data="compra(@js($productos), {{ (float) config('sunat.igv') }})"
      @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
      class="bg-white rounded-lg shadow overflow-hidden">
    @csrf
    <div class="bg-blue-600 text-white px-5 py-3 text-lg">Registrar compra</div>
    <div class="p-5 space-y-5">
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
            <label class="block text-blue-700 sm:col-span-2">Proveedor <a href="{{ route('proveedores.create', ['volver' => route('compras.create')]) }}" class="text-slate-400 hover:text-blue-700">[+ Nuevo]</a>
                <select name="proveedor_id" required class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
                    @foreach ($proveedores as $p)<option value="{{ $p->id }}">{{ $p->ruc }} {{ $p->razon_social }}</option>@endforeach
                </select>
            </label>
            <label class="block text-blue-700">Documento
                <select name="tipo_documento" x-model="tipo" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
                    @foreach (\App\Models\Compra::TIPOS_DOCUMENTO as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </label>
            <label class="block text-blue-700">Serie y número<input name="numero_documento" placeholder="F001-123" class="mt-1 w-full border rounded px-3 py-2 text-slate-800"></label>
            <label class="block text-blue-700">Fecha<input type="date" name="fecha" value="{{ today()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required class="mt-1 w-full border rounded px-3 py-2 text-slate-800"></label>
            <label class="block text-blue-700">Método de pago
                <select name="metodo_pago" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
                    @foreach (\App\Models\Caja::METODOS_PAGO as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </label>
            <div class="flex flex-col justify-end gap-1 sm:col-span-2">
                <label class="flex items-center gap-2" x-show="tipo === '01'"><input type="hidden" name="incluye_igv" value="0"><input type="checkbox" name="incluye_igv" value="1" x-model="incluyeIgv"> Los costos incluyen IGV</label>
                @if ($cajaAbierta)
                    <label class="flex items-center gap-2"><input type="hidden" name="pagado_desde_caja" value="0"><input type="checkbox" name="pagado_desde_caja" value="1"> Pagado con dinero de la caja</label>
                @endif
            </div>
        </div>

        <div class="relative max-w-xl" @click.outside="busqueda = ''">
            <input x-model="busqueda" x-ref="buscador" placeholder="Agregar producto: nombre o código, Enter agrega el primero" @keydown.enter.prevent="agregarPrimero()" class="w-full border rounded px-3 py-2 text-sm">
            <div x-show="busqueda" class="absolute z-10 mt-1 w-full bg-white border rounded shadow max-h-72 overflow-y-auto">
                <template x-for="p in filtrados" :key="p.id">
                    <button type="button" @click="agregar(p)" class="flex w-full text-left px-3 py-1.5 hover:bg-slate-100 text-sm gap-2">
                        <span class="font-mono text-slate-500" x-text="p.codigo"></span><span class="flex-1" x-text="p.nombre"></span><span class="text-slate-400" x-text="'stock ' + Number(p.stock)"></span>
                    </button>
                </template>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="text-left border-b"><tr><th class="py-2">Producto</th><th class="w-28">Cantidad</th><th class="w-36">Costo unitario</th><th class="text-right w-28">Total</th><th class="w-8"></th></tr></thead>
            <tbody>
            <template x-for="(item, i) in items" :key="item.producto_id">
                <tr class="border-b">
                    <td class="py-2" x-text="item.nombre"></td>
                    <td class="pr-2"><input type="number" step="0.01" min="0.01" x-model.number="item.cantidad" :name="`items[${i}][cantidad]`" class="w-full border rounded px-2 py-1"></td>
                    <td class="pr-2"><input type="number" step="0.0001" min="0" x-model.number="item.costo" :name="`items[${i}][costo]`" class="w-full border rounded px-2 py-1"></td>
                    <td class="text-right" x-text="totalLinea(item).toFixed(2)"></td>
                    <td class="text-right"><input type="hidden" :name="`items[${i}][producto_id]`" :value="item.producto_id"><button type="button" @click="items.splice(i, 1)" class="text-red-600 px-2">✕</button></td>
                </tr>
            </template>
            </tbody>
        </table>
        <div class="text-right text-xl font-semibold">Total: S/ <span x-text="total.toFixed(2)"></span></div>
        <label class="block text-sm text-blue-700">Observaciones<textarea name="observaciones" rows="2" class="mt-1 w-full border rounded px-3 py-2 text-slate-800"></textarea></label>
        <button :disabled="items.length === 0" class="bg-blue-600 disabled:opacity-50 text-white rounded px-6 py-2">Registrar compra</button>
    </div>
</form>
<script>
function compra(productos, igv) {
    return {
        productos, busqueda: '', items: [], tipo: '01', incluyeIgv: true,
        get filtrados() {
            const q = this.busqueda.toLowerCase();
            return this.productos.filter(p => p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q)).slice(0, 30);
        },
        totalLinea(i) { return i.cantidad * i.costo * (this.tipo === '01' && ! this.incluyeIgv ? 1 + igv : 1) },
        get total() { return this.items.reduce((s, i) => s + this.totalLinea(i), 0) },
        agregar(p) {
            const existente = this.items.find(i => i.producto_id === p.id);
            if (existente) existente.cantidad++;
            // Sugiere el último costo (guardado sin IGV) llevado a con IGV
            else this.items.push({ producto_id: p.id, nombre: p.nombre, cantidad: 1, costo: p.precio_compra ? Math.round(p.precio_compra * (1 + igv) * 100) / 100 : 0 });
            this.busqueda = '';
            this.$refs.buscador.focus();
        },
        agregarPrimero() { if (this.filtrados.length) this.agregar(this.filtrados[0]) },
    };
}
</script>
@endsection
