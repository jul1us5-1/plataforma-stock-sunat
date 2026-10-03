@extends('layouts.app')
@section('titulo', 'Nota de crédito')
@section('contenido')
<a href="{{ route('comprobantes.show', $comprobante) }}" class="text-blue-700 hover:underline">← {{ $comprobante->numero() }}</a>
<form method="POST" action="{{ route('comprobantes.nota-credito', $comprobante) }}" x-data="{ motivo: '01' }"
      class="bg-white rounded-lg shadow overflow-hidden mt-4 max-w-4xl" onsubmit="return confirm('¿Emitir la nota de crédito? Se enviará a SUNAT y no se puede deshacer.')">
    @csrf
    <div class="bg-red-600 text-white px-5 py-3 text-lg">Nota de crédito sobre {{ $comprobante->nombreTipo() }} {{ $comprobante->numero() }}</div>
    <div class="p-5 space-y-4">
        <div class="grid sm:grid-cols-2 gap-4 text-sm">
            <label class="block">Motivo
                <select name="motivo_codigo" x-model="motivo" class="mt-1 w-full border rounded px-3 py-2">
                    @foreach (\App\Models\Comprobante::MOTIVOS_NC as $k => $v)<option value="{{ $k }}">{{ $k }} - {{ $v }}</option>@endforeach
                </select>
            </label>
            <label class="block">Descripción del motivo
                <input name="motivo_descripcion" required maxlength="250" value="{{ old('motivo_descripcion') }}" placeholder="Ej. Cliente devolvió el producto por talla" class="mt-1 w-full border rounded px-3 py-2">
            </label>
        </div>
        <p class="text-sm text-slate-600" x-show="motivo !== '07'">Se devolverá todo lo pendiente del comprobante y los productos vuelven al stock.</p>
        <table class="w-full text-sm">
            <thead class="text-left border-b"><tr><th class="py-2">Producto</th><th class="text-right">Vendido</th><th class="text-right">Pendiente</th><th class="text-right">P. unit.</th><th class="w-32 text-right" x-show="motivo === '07'">Devolver</th></tr></thead>
            <tbody>
            @foreach ($comprobante->items->unique('producto_id') as $item)
                <tr class="border-b">
                    <td class="py-2">{{ $item->descripcion }}</td>
                    <td class="text-right">{{ $comprobante->items->where('producto_id', $item->producto_id)->sum('cantidad') + 0 }}</td>
                    <td class="text-right">{{ $devolvibles[$item->producto_id] ?? 0 }}</td>
                    <td class="text-right">{{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="text-right" x-show="motivo === '07'">
                        <input type="number" step="0.01" min="0" max="{{ $devolvibles[$item->producto_id] ?? 0 }}" name="cantidades[{{ $item->producto_id }}]" value="0" class="w-24 border rounded px-2 py-1">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <button class="bg-red-600 text-white rounded px-6 py-2">Emitir nota de crédito</button>
    </div>
</form>
@endsection
