<table class="w-full text-sm">
    <thead class="text-left bg-slate-50"><tr><th class="p-2">Hora</th><th class="p-2">Concepto</th><th class="p-2">Método</th><th class="p-2 text-right">Monto</th></tr></thead>
    <tbody>
    @forelse ($movimientos as $m)
        <tr class="border-t">
            <td class="p-2">{{ $m->created_at->format('d/m H:i') }}</td>
            <td class="p-2">@if ($m->comprobante)<a class="text-blue-700 hover:underline" href="{{ route('comprobantes.show', $m->comprobante) }}">{{ $m->concepto }}</a>@else{{ $m->concepto }}@endif</td>
            <td class="p-2">{{ \App\Models\Caja::METODOS_PAGO[$m->metodo_pago] ?? $m->metodo_pago }}</td>
            <td class="p-2 text-right {{ $m->tipo === 'egreso' ? 'text-red-600' : 'text-emerald-700' }}">{{ $m->tipo === 'egreso' ? '-' : '' }}S/ {{ number_format($m->monto, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="p-2 text-slate-500">Sin movimientos.</td></tr>
    @endforelse
    </tbody>
</table>
