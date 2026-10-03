<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 34px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
    table { border-collapse: collapse; width: 100%; }
    .encabezado td { vertical-align: top; }
    .emisor { font-size: 10px; line-height: 1.45; }
    .emisor .nombre { font-size: 14px; font-weight: bold; }
    .caja { border: 1.5px solid #111; border-radius: 6px; text-align: center; padding: 10px 6px; font-size: 13px; line-height: 1.5; }
    .caja .tipo { font-weight: bold; }
    .datos { margin-top: 18px; }
    .datos td { padding: 2px 0; }
    .datos .et { width: 130px; font-weight: bold; }
    .items { margin-top: 16px; }
    .items th { border-top: 1px solid #111; border-bottom: 1px solid #111; padding: 5px 4px; text-align: left; font-size: 9px; }
    .items td { padding: 5px 4px; border-bottom: 1px solid #ddd; }
    .der { text-align: right; }
    .totales { width: 260px; margin-left: auto; margin-top: 8px; }
    .totales td { padding: 2px 4px; }
    .totales .total td { font-weight: bold; font-size: 12px; border-top: 1px solid #111; padding-top: 4px; }
    .pie { margin-top: 18px; }
    .pie td { vertical-align: top; }
    .leyenda { text-align: center; margin-top: 24px; font-size: 9px; color: #333; }
</style>
</head>
<body>
<table class="encabezado">
    <tr>
        @if ($logo)<td style="width: 110px"><img src="{{ $logo }}" style="max-width: 100px; max-height: 80px"></td>@endif
        <td class="emisor">
            <div class="nombre">{{ $empresa['razon_social'] }}</div>
            @if ($empresa['nombre_comercial'])<div>{{ $empresa['nombre_comercial'] }}</div>@endif
            <div>RUC {{ $empresa['ruc'] }}</div>
            <div>{{ $empresa['direccion'] }}, {{ $empresa['distrito'] }}, {{ $empresa['provincia'] }} - {{ $empresa['departamento'] }}</div>
            @if ($empresa['email'])<div>Email: {{ $empresa['email'] }}</div>@endif
            @if ($empresa['telefono'])<div>Teléfono: {{ $empresa['telefono'] }}</div>@endif
        </td>
        <td style="width: 210px">
            <div class="caja">
                <div>RUC {{ $empresa['ruc'] }}</div>
                <div class="tipo">{{ mb_strtoupper($c->nombreTipo()) }}</div>
                <div>{{ $c->numero() }}</div>
            </div>
        </td>
    </tr>
</table>

<table class="datos">
    <tr><td class="et">Fecha de emisión</td><td>: {{ $c->fecha_emision->format('d/m/Y H:i') }}</td></tr>
    <tr><td class="et">Cliente</td><td>: {{ $c->cliente?->razon_social ?? 'Clientes varios' }}</td></tr>
    @if ($c->cliente)
        <tr><td class="et">{{ \App\Models\Cliente::TIPOS_DOCUMENTO[$c->cliente->tipo_documento] ?? 'Documento' }}</td><td>: {{ $c->cliente->numero_documento }}</td></tr>
        @if ($c->cliente->direccion)<tr><td class="et">Dirección</td><td>: {{ $c->cliente->direccion }}</td></tr>@endif
    @endif
    <tr><td class="et">Moneda</td><td>: Soles</td></tr>
</table>

<table class="items">
    <thead><tr><th style="width: 50px">CANT.</th><th style="width: 50px">UNIDAD</th><th>DESCRIPCIÓN</th><th class="der" style="width: 70px">P. UNIT.</th><th class="der" style="width: 70px">TOTAL</th></tr></thead>
    <tbody>
    @foreach ($c->items as $item)
        <tr><td>{{ $item->cantidad + 0 }}</td><td>{{ $item->unidad_medida }}</td><td>{{ $item->descripcion }}</td><td class="der">{{ number_format($item->precio_unitario, 2) }}</td><td class="der">{{ number_format($item->total, 2) }}</td></tr>
    @endforeach
    </tbody>
</table>

<table class="totales">
    @if ($c->op_gravadas > 0)<tr><td>OP. GRAVADAS</td><td class="der">S/ {{ number_format($c->op_gravadas, 2) }}</td></tr>@endif
    @if ($c->op_exoneradas > 0)<tr><td>OP. EXONERADAS</td><td class="der">S/ {{ number_format($c->op_exoneradas, 2) }}</td></tr>@endif
    @if ($c->op_inafectas > 0)<tr><td>OP. INAFECTAS</td><td class="der">S/ {{ number_format($c->op_inafectas, 2) }}</td></tr>@endif
    <tr><td>IGV 18%</td><td class="der">S/ {{ number_format($c->igv, 2) }}</td></tr>
    <tr class="total"><td>TOTAL A PAGAR</td><td class="der">S/ {{ number_format($c->total, 2) }}</td></tr>
</table>

<p><strong>SON:</strong> {{ \App\Support\NumeroALetras::convertir((float) $c->total) }}</p>

<table class="pie">
    <tr>
        <td>
            <div><strong>Condición de pago:</strong> Contado</div>
            <div><strong>Pago:</strong> {{ \App\Models\Caja::METODOS_PAGO[$c->metodo_pago] ?? $c->metodo_pago }} - S/ {{ number_format($c->total, 2) }}</div>
            @if ($c->vendedor)<div><strong>Vendedor:</strong> {{ $c->vendedor->name }}</div>@endif
            @if ($c->observaciones)<div style="margin-top: 6px"><strong>Observaciones:</strong> {{ $c->observaciones }}</div>@endif
            @if ($c->hash)<div style="margin-top: 6px"><strong>Código hash:</strong> {{ $c->hash }}</div>@endif
        </td>
        @if ($qr)<td style="width: 110px; text-align: right"><img src="{{ $qr }}" style="width: 100px; height: 100px"></td>@endif
    </tr>
</table>

<div class="leyenda">
    @if ($c->seEnviaASunat())
        Representación impresa de la {{ mb_strtoupper($c->nombreTipo()) }}.<br>Puede consultar su validez en www.sunat.gob.pe
    @else
        Documento interno, no es un comprobante de pago. Canjéelo por una boleta o factura.
    @endif
</div>
</body>
</html>
