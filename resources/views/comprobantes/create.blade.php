@extends('layouts.app')
@section('titulo', 'Nuevo comprobante')
@section('contenido')
@unless ($cajaAbierta)
    <div class="mb-4 rounded bg-amber-100 border border-amber-300 px-4 py-2">No tienes una caja abierta, así que esta venta no quedará en ningún cierre. <a href="{{ route('caja.index') }}" class="underline">Abrir caja</a></div>
@endunless
<form method="POST" action="{{ route('comprobantes.store') }}"
      x-data="venta(@js($productos->map->only(['id', 'codigo', 'nombre', 'precio_venta', 'stock', 'unidad_medida', 'afectacion_igv'])), @js($series->map->only(['serie', 'tipo_comprobante'])), @js($clientes), {{ (float) config('sunat.igv') }})"
      @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
      x-init="cargarPrevio(@js($previo ? ['cliente_id' => $previo->cliente_id, 'items' => $previo->items->map(fn ($i) => ['producto_id' => $i->producto_id, 'cantidad' => (float) $i->cantidad, 'precio_unitario' => (float) $i->precio_unitario])] : null))"
      class="bg-white rounded-lg shadow overflow-hidden">
    @csrf
    <div class="bg-blue-600 text-white px-5 py-3 text-lg">Nuevo comprobante @if ($previo)<span class="text-blue-100 text-sm">desde {{ $previo->nombreTipo() }} {{ $previo->codigo() }}</span>@endif</div>
    @if ($previo)<input type="hidden" name="documento_previo_id" value="{{ $previo->id }}">@endif
    <div class="p-5 grid lg:grid-cols-4 gap-6">
        <div class="lg:col-span-3 space-y-5">
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
                <label class="block text-blue-700">Tipo comprobante
                    <select name="tipo_comprobante" x-model="tipo" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
                        @foreach (\App\Models\Comprobante::TIPOS_VENTA as $k)<option value="{{ $k }}">{{ \App\Models\Comprobante::TIPOS[$k] }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-blue-700">Serie
                    <select name="serie" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
                        <template x-for="s in seriesDelTipo" :key="s.serie"><option :value="s.serie" x-text="s.serie"></option></template>
                    </select>
                </label>
                <label class="block text-blue-700">Fecha de emisión
                    <input value="{{ now()->format('d/m/Y') }}" disabled class="mt-1 w-full border rounded px-3 py-2 bg-slate-50 text-slate-800">
                </label>
                <label class="block text-blue-700">Moneda
                    <input value="Soles (S/)" disabled class="mt-1 w-full border rounded px-3 py-2 bg-slate-50 text-slate-800">
                </label>

                <div class="sm:col-span-2 relative">
                    <span class="text-blue-700 font-medium">Cliente</span>
                    <a href="{{ route('clientes.create') }}" class="text-slate-400 hover:text-blue-700">[+ Nuevo]</a>
                    <span class="text-xs text-slate-500" x-show="tipo === '01'">(con RUC)</span>
                    <input type="hidden" name="cliente_id" :value="cliente?.id ?? ''">
                    <input x-model="buscaCliente" @focus="abiertoCliente = true" @click.outside="abiertoCliente = false"
                           :placeholder="cliente ? '' : 'Escriba el nombre o número de documento del cliente'"
                           class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
                    <div x-show="abiertoCliente" class="absolute z-10 mt-1 w-full bg-white border rounded shadow max-h-60 overflow-y-auto">
                        <button type="button" @click="elegirCliente(null)" class="block w-full text-left px-3 py-1.5 hover:bg-slate-100">Clientes varios</button>
                        <template x-for="c in clientesFiltrados" :key="c.id">
                            <button type="button" @click="elegirCliente(c)" class="block w-full text-left px-3 py-1.5 hover:bg-slate-100">
                                <span class="font-mono" x-text="c.numero_documento"></span> · <span x-text="c.razon_social"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <label class="block text-blue-700">Método de pago
                    <select name="metodo_pago" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">
                        @foreach (\App\Models\Caja::METODOS_PAGO as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-blue-700">Vendedor
                    <input value="{{ auth()->user()->name }}" disabled class="mt-1 w-full border rounded px-3 py-2 bg-slate-50 text-slate-800">
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-slate-600 border-b">
                        <tr><th class="py-2 w-8">#</th><th>Descripción</th><th class="w-16">Unidad</th><th class="w-24">Cantidad</th><th class="text-right w-28">Valor unitario</th><th class="w-28">Precio unitario</th><th class="text-right w-24">Subtotal</th><th class="text-right w-24">Total</th><th class="w-8"></th></tr>
                    </thead>
                    <tbody>
                    <template x-for="(item, i) in items" :key="item.producto_id">
                        <tr class="border-b">
                            <td class="py-2" x-text="i + 1"></td>
                            <td x-text="item.nombre"></td>
                            <td x-text="item.unidad"></td>
                            <td class="pr-2"><input type="number" step="0.01" min="0.01" x-model.number="item.cantidad" :name="`items[${i}][cantidad]`" class="w-full border rounded px-2 py-1"></td>
                            <td class="text-right pr-2" x-text="valorUnitario(item).toFixed(2)"></td>
                            <td class="pr-2"><input type="number" step="0.01" min="0" x-model.number="item.precio_unitario" :name="`items[${i}][precio_unitario]`" class="w-full border rounded px-2 py-1"></td>
                            <td class="text-right" x-text="subtotal(item).toFixed(2)"></td>
                            <td class="text-right font-medium" x-text="total(item).toFixed(2)"></td>
                            <td class="text-right"><input type="hidden" :name="`items[${i}][producto_id]`" :value="item.producto_id"><button type="button" @click="items.splice(i, 1)" class="text-red-600 px-2" title="Quitar">✕</button></td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            <div class="relative max-w-xl" @click.outside="busqueda = ''">
                <div class="flex gap-2">
                    <span class="bg-blue-600 text-white rounded px-4 py-2 text-sm whitespace-nowrap">+ Agregar producto</span>
                    <input x-model="busqueda" x-ref="buscador" placeholder="Nombre o código, Enter agrega el primero" @keydown.enter.prevent="agregarPrimero()" class="flex-1 border rounded px-3 py-2 text-sm">
                </div>
                <div x-show="busqueda" class="absolute z-10 mt-1 w-full bg-white border rounded shadow max-h-72 overflow-y-auto">
                    <template x-for="p in filtrados" :key="p.id">
                        <button type="button" @click="agregar(p)" class="flex w-full text-left px-3 py-1.5 hover:bg-slate-100 text-sm gap-2">
                            <span class="font-mono text-slate-500" x-text="p.codigo"></span>
                            <span class="flex-1" x-text="p.nombre"></span>
                            <span x-text="'S/ ' + Number(p.precio_venta).toFixed(2)"></span>
                            <span class="text-slate-400 w-16 text-right" x-show="p.unidad_medida !== 'ZZ'" x-text="'stock ' + Number(p.stock)"></span>
                        </button>
                    </template>
                    <div x-show="filtrados.length === 0" class="px-3 py-2 text-sm text-slate-500">Sin resultados.</div>
                </div>
            </div>
        </div>

        <div class="space-y-4 lg:border-l lg:pl-6">
            <label class="block text-sm text-blue-700">Observaciones
                <textarea name="observaciones" rows="3" class="mt-1 w-full border rounded px-3 py-2 text-slate-800">{{ old('observaciones', $previo?->observaciones) }}</textarea>
            </label>
            <div class="bg-slate-50 rounded p-4 text-sm space-y-1">
                <div class="flex justify-between" x-show="totales.gravadas"><span>Op. gravadas</span><span x-text="'S/ ' + totales.gravadas.toFixed(2)"></span></div>
                <div class="flex justify-between" x-show="totales.exoneradas"><span>Op. exoneradas</span><span x-text="'S/ ' + totales.exoneradas.toFixed(2)"></span></div>
                <div class="flex justify-between" x-show="totales.inafectas"><span>Op. inafectas</span><span x-text="'S/ ' + totales.inafectas.toFixed(2)"></span></div>
                <div class="flex justify-between"><span>IGV</span><span x-text="'S/ ' + totales.igv.toFixed(2)"></span></div>
                <div class="flex justify-between text-xl font-semibold border-t pt-2 mt-2"><span>Total</span><span x-text="'S/ ' + totales.total.toFixed(2)"></span></div>
            </div>
            <button :disabled="items.length === 0" class="w-full bg-emerald-600 disabled:opacity-50 text-white rounded px-6 py-3 font-medium">Emitir comprobante</button>
            <a href="{{ route('comprobantes.index') }}" class="block text-center border rounded px-6 py-2">Cancelar</a>
        </div>
    </div>
</form>
<script>
function venta(productos, series, clientes, igv) {
    const r2 = n => Math.round(n * 100) / 100;
    return {
        productos, series, clientes, tipo: '03', busqueda: '', items: [],
        cliente: null, buscaCliente: '', abiertoCliente: false,
        get seriesDelTipo() { return this.series.filter(s => s.tipo_comprobante === this.tipo) },
        get filtrados() {
            const q = this.busqueda.toLowerCase();
            return this.productos.filter(p => p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q)).slice(0, 30);
        },
        get clientesFiltrados() {
            const q = this.cliente ? '' : this.buscaCliente.toLowerCase();
            return this.clientes.filter(c => c.razon_social.toLowerCase().includes(q) || c.numero_documento.includes(q))
                .filter(c => this.tipo !== '01' || c.tipo_documento === '6').slice(0, 30);
        },
        elegirCliente(c) {
            this.cliente = c;
            this.buscaCliente = c ? `${c.numero_documento} · ${c.razon_social}` : 'Clientes varios';
            this.abiertoCliente = false;
        },
        tasa(item) { return item.afectacion === '10' ? igv : 0 },
        valorUnitario(item) { return item.precio_unitario / (1 + this.tasa(item)) },
        total(item) { return r2(item.cantidad * item.precio_unitario) },
        subtotal(item) { return r2(this.total(item) / (1 + this.tasa(item))) },
        get totales() {
            const t = { gravadas: 0, exoneradas: 0, inafectas: 0, igv: 0, total: 0 };
            for (const i of this.items) {
                const clave = { '10': 'gravadas', '20': 'exoneradas', '30': 'inafectas' }[i.afectacion];
                t[clave] += this.subtotal(i);
                t.igv += this.total(i) - this.subtotal(i);
                t.total += this.total(i);
            }
            return t;
        },
        agregar(p) {
            const existente = this.items.find(i => i.producto_id === p.id);
            if (existente) existente.cantidad++;
            else this.items.push({ producto_id: p.id, nombre: p.nombre, unidad: p.unidad_medida, afectacion: p.afectacion_igv, cantidad: 1, precio_unitario: Number(p.precio_venta) });
            this.busqueda = '';
            this.$refs.buscador.focus();
        },
        agregarPrimero() { if (this.filtrados.length) this.agregar(this.filtrados[0]) },
        cargarPrevio(previo) {
            if (! previo) return;
            const cliente = this.clientes.find(c => c.id === previo.cliente_id);
            if (cliente) { this.elegirCliente(cliente); if (cliente.tipo_documento === '6') this.tipo = '01'; }
            for (const linea of previo.items) {
                const p = this.productos.find(p => p.id === linea.producto_id);
                if (p) this.items.push({ producto_id: p.id, nombre: p.nombre, unidad: p.unidad_medida, afectacion: p.afectacion_igv, cantidad: linea.cantidad, precio_unitario: linea.precio_unitario });
            }
        },
    };
}
</script>
@endsection
