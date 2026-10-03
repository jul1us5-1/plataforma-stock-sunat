<table class="w-full text-sm">
    <thead class="text-left bg-slate-50"><tr><th class="p-2">Método</th><th class="p-2 text-right">Ingresos</th><th class="p-2 text-right">Egresos</th></tr></thead>
    <tbody>
    @forelse ($caja->resumenPorMetodo() as $metodo => $r)
        <tr class="border-t"><td class="p-2">{{ \App\Models\Caja::METODOS_PAGO[$metodo] ?? $metodo }}</td><td class="p-2 text-right">S/ {{ number_format($r['ingresos'], 2) }}</td><td class="p-2 text-right">S/ {{ number_format($r['egresos'], 2) }}</td></tr>
    @empty
        <tr><td colspan="3" class="p-2 text-slate-500">Sin movimientos.</td></tr>
    @endforelse
    </tbody>
</table>
