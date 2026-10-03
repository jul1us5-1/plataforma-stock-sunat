@extends('layouts.app')
@section('titulo', 'Reportes')
@section('contenido')
<div class="flex items-center mb-4">
    <h1 class="text-xl font-semibold mr-auto">Reportes</h1>
    <a href="{{ route('reportes.ventas', request()->query()) }}" class="bg-white border rounded px-3 py-1.5 text-sm">Exportar ventas (Excel/CSV)</a>
</div>
@include('partials.filtro-periodo')
@include('partials.tarjetas-resumen')

<div class="bg-white rounded-lg shadow p-4 mb-4">
    <h2 class="font-semibold mb-2">Ventas {{ $periodo->desde->isSameDay($periodo->hasta) ? 'por hora' : 'por día' }}</h2>
    @include('partials.grafico-ventas', ['id' => 'grafico-reportes'])
</div>

<div class="grid lg:grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-lg shadow p-4 overflow-x-auto">
        <h2 class="font-semibold mb-2">Por tipo de comprobante</h2>
        <table class="w-full text-sm">
            <thead class="text-left bg-slate-50"><tr><th class="p-2">Tipo</th><th class="p-2 text-right">Cant.</th><th class="p-2 text-right">Valor venta</th><th class="p-2 text-right">IGV</th><th class="p-2 text-right">Total</th></tr></thead>
            <tbody>
            @forelse ($tipos as $t)
                <tr class="border-t"><td class="p-2">{{ \App\Models\Comprobante::TIPOS[$t->tipo_comprobante] ?? $t->tipo_comprobante }}</td><td class="p-2 text-right">{{ $t->cantidad }}</td><td class="p-2 text-right">{{ number_format($t->valor, 2) }}</td><td class="p-2 text-right">{{ number_format($t->igv, 2) }}</td><td class="p-2 text-right font-medium">{{ number_format($t->total, 2) }}</td></tr>
            @empty <tr><td colspan="5" class="p-2 text-slate-500">Sin ventas en el periodo.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-white rounded-lg shadow p-4 overflow-x-auto">
        <h2 class="font-semibold mb-2">Por método de pago</h2>
        <table class="w-full text-sm">
            <thead class="text-left bg-slate-50"><tr><th class="p-2">Método</th><th class="p-2 text-right">Cant.</th><th class="p-2 text-right">Total</th></tr></thead>
            <tbody>
            @forelse ($metodos as $m)
                <tr class="border-t"><td class="p-2">{{ \App\Models\Caja::METODOS_PAGO[$m->metodo_pago] ?? $m->metodo_pago }}</td><td class="p-2 text-right">{{ $m->cantidad }}</td><td class="p-2 text-right font-medium">{{ number_format($m->total, 2) }}</td></tr>
            @empty <tr><td colspan="3" class="p-2 text-slate-500">Sin ventas en el periodo.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-4 mb-4 overflow-x-auto">
    <h2 class="font-semibold mb-2">Productos vendidos</h2>
    <table class="w-full text-sm">
        <thead class="text-left bg-slate-50"><tr><th class="p-2">Código</th><th class="p-2">Producto</th><th class="p-2 text-right">Cantidad</th><th class="p-2 text-right">Total</th><th class="p-2 text-right">Utilidad</th></tr></thead>
        <tbody>
        @forelse ($productos as $p)
            <tr class="border-t">
                <td class="p-2 font-mono">{{ $p->codigo }}</td><td class="p-2">{{ $p->descripcion }}</td>
                <td class="p-2 text-right">{{ $p->cantidad + 0 }}</td><td class="p-2 text-right">{{ number_format($p->total, 2) }}</td>
                <td class="p-2 text-right">{{ $p->costo === null ? '—' : number_format($p->valor_venta - $p->costo, 2) }}</td>
            </tr>
        @empty <tr><td colspan="5" class="p-2 text-slate-500">Sin ventas en el periodo.</td></tr> @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white rounded-lg shadow p-4">
    <h2 class="font-semibold mb-2">Inventario valorizado (hoy)</h2>
    <div class="grid sm:grid-cols-4 gap-4 text-sm">
        <div><div class="text-slate-500">Productos (bienes activos)</div><div class="text-lg font-semibold">{{ number_format($inventario['productos']) }}</div></div>
        <div><div class="text-slate-500">Unidades</div><div class="text-lg font-semibold">{{ number_format($inventario['unidades']) }}</div></div>
        <div><div class="text-slate-500">Valor a precio de venta</div><div class="text-lg font-semibold">S/ {{ number_format($inventario['valor_venta'], 2) }}</div></div>
        <div><div class="text-slate-500">Valor a costo</div><div class="text-lg font-semibold">S/ {{ number_format($inventario['valor_costo'], 2) }}</div>
            @if ($inventario['sin_costo'])<div class="text-xs text-slate-400">{{ $inventario['sin_costo'] }} productos sin costo registrado</div>@endif</div>
    </div>
</div>
@endsection
