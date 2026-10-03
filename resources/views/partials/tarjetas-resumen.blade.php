<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
    @foreach ([
        ['CPE emitidos', number_format($resumen['cpe']), 'Facturas y boletas'],
        ['Monto comprobantes', 'S/ '.number_format($resumen['monto_cpe'], 2), 'Facturas y boletas'],
        ['Monto recibos', 'S/ '.number_format($resumen['monto_recibos'], 2), 'Notas de venta internas'],
        ['Total general', 'S/ '.number_format($resumen['total'], 2), 'Todo lo vendido'],
        ['Utilidad', 'S/ '.number_format($resumen['utilidad'], 2), $resumen['items_sin_costo'] ? $resumen['items_sin_costo'].' ítems sin costo registrado' : 'Venta sin IGV menos costo'],
    ] as [$titulo, $valor, $nota])
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-sm text-slate-500">{{ $titulo }}</div>
            <div class="text-2xl font-semibold text-slate-800 my-1">{{ $valor }}</div>
            <div class="text-xs text-slate-400">{{ $nota }}</div>
        </div>
    @endforeach
</div>
