<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 34px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
    table { border-collapse: collapse; width: 100%; }
    .der { text-align: right; }
    .items th { border-top: 1px solid #111; border-bottom: 1px solid #111; padding: 5px 4px; text-align: left; font-size: 9px; }
    .items td { padding: 5px 4px; border-bottom: 1px solid #ddd; }
</style>
</head>
<body>
<table>
    <tr>
        <td>
            <div style="font-size: 14px; font-weight: bold">{{ $empresa['razon_social'] }}</div>
            <div>RUC {{ $empresa['ruc'] }}</div>
            <div>{{ $empresa['direccion'] }}, {{ $empresa['distrito'] }}</div>
            @if ($empresa['email'])<div>{{ $empresa['email'] }}</div>@endif
        </td>
        <td style="width: 200px; text-align: center; border: 1.5px solid #111; padding: 10px; font-size: 13px">
            <strong>{{ mb_strtoupper($d->nombreTipo()) }}</strong><br>{{ $d->codigo() }}
        </td>
    </tr>
</table>
<p style="margin-top: 16px">
    <strong>Cliente:</strong> {{ $d->cliente?->razon_social ?? 'Clientes varios' }}@if ($d->cliente) ({{ $d->cliente->numero_documento }})@endif<br>
    <strong>Fecha:</strong> {{ $d->fecha->format('d/m/Y') }}
    @if ($d->fecha_limite)<br><strong>{{ \App\Models\DocumentoPrevio::TIPOS[$d->tipo]['limite'] }}:</strong> {{ $d->fecha_limite->format('d/m/Y') }}@endif
</p>
<table class="items">
    <thead><tr><th style="width: 50px">CANT.</th><th>DESCRIPCIÓN</th><th class="der" style="width: 80px">P. UNIT.</th><th class="der" style="width: 80px">TOTAL</th></tr></thead>
    <tbody>
    @foreach ($d->items as $item)
        <tr><td>{{ $item->cantidad + 0 }}</td><td>{{ $item->descripcion }}</td><td class="der">{{ number_format($item->precio_unitario, 2) }}</td><td class="der">{{ number_format($item->total, 2) }}</td></tr>
    @endforeach
    </tbody>
</table>
<p class="der" style="font-size: 13px"><strong>TOTAL: S/ {{ number_format($d->total, 2) }}</strong></p>
<p>Precios incluyen IGV.</p>
@if ($d->observaciones)<p><strong>Observaciones:</strong> {{ $d->observaciones }}</p>@endif
<p style="text-align: center; margin-top: 30px; font-size: 9px; color: #444">Este documento no es un comprobante de pago.</p>
</body>
</html>
