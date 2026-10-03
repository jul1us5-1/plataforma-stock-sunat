@extends('layouts.app')
@section('titulo', $compra->descripcion())
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4">
    <a href="{{ route('compras.index') }}" class="text-blue-700 hover:underline mr-auto">← Compras</a>
    @if ($compra->estado !== 'anulada')
        <form method="POST" action="{{ route('compras.anular', $compra) }}" onsubmit="return confirm('¿Anular la compra? Se descontará el stock que ingresó.')">@csrf<button class="border border-red-300 text-red-700 bg-white rounded px-3 py-1.5">Anular compra</button></form>
    @endif
</div>
<div class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
    <h1 class="text-xl font-semibold mb-4">{{ $compra->descripcion() }} @if ($compra->estado === 'anulada')<span class="text-sm text-red-600">(anulada)</span>@endif</h1>
    <div class="text-sm grid grid-cols-2 gap-1 mb-4">
        <div><strong>Proveedor:</strong> {{ $compra->proveedor->razon_social }}</div>
        <div><strong>Fecha:</strong> {{ $compra->fecha->format('d/m/Y') }}</div>
        <div><strong>Pago:</strong> {{ \App\Models\Caja::METODOS_PAGO[$compra->metodo_pago] ?? $compra->metodo_pago }}{{ $compra->pagado_desde_caja ? ' (desde caja)' : '' }}</div>
        @if ($compra->usuario)<div><strong>Registró:</strong> {{ $compra->usuario->name }}</div>@endif
    </div>
    <table class="w-full text-sm mb-4">
        <thead class="border-b-2 text-left"><tr><th class="py-1">Cant.</th><th>Producto</th><th class="text-right">Costo unit. (sin IGV)</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        @foreach ($compra->items as $item)
            <tr class="border-b"><td class="py-1">{{ $item->cantidad + 0 }}</td><td>{{ $item->producto->nombre }}</td><td class="text-right">{{ number_format($item->costo_unitario, 2) }}</td><td class="text-right">{{ number_format($item->total, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="ml-auto w-56 text-sm space-y-1">
        <div class="flex justify-between"><span>Subtotal</span><span>S/ {{ number_format($compra->subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span>IGV</span><span>S/ {{ number_format($compra->igv, 2) }}</span></div>
        <div class="flex justify-between font-semibold border-t pt-1"><span>Total</span><span>S/ {{ number_format($compra->total, 2) }}</span></div>
    </div>
    @if ($compra->observaciones)<p class="text-sm mt-4"><strong>Observaciones:</strong> {{ $compra->observaciones }}</p>@endif
</div>
@endsection
