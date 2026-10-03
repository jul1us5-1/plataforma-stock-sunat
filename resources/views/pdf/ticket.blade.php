<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 10px 8px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #000; }
    .c { text-align: center; }
    .der { text-align: right; }
    .b { font-weight: bold; }
    hr { border: 0; border-top: 1px dashed #000; margin: 5px 0; }
    table { border-collapse: collapse; width: 100%; }
    td { padding: 1px 0; vertical-align: top; }
</style>
</head>
<body>
@if ($logo)<div class="c"><img src="{{ $logo }}" style="max-width: 120px; max-height: 60px"></div>@endif
<div class="c b" style="font-size: 10px">{{ $empresa['nombre_comercial'] ?: $empresa['razon_social'] }}</div>
@if ($empresa['nombre_comercial'])<div class="c">{{ $empresa['razon_social'] }}</div>@endif
<div class="c">RUC {{ $empresa['ruc'] }}</div>
<div class="c">{{ $empresa['direccion'] }}, {{ $empresa['distrito'] }}</div>
@if ($empresa['telefono'])<div class="c">Tel. {{ $empresa['telefono'] }}</div>@endif
<hr>
<div class="c b">{{ mb_strtoupper($c->nombreTipo()) }}</div>
<div class="c b">{{ $c->numero() }}</div>
<hr>
<div>Fecha: {{ $c->fecha_emision->format('d/m/Y H:i') }}</div>
<div>Cliente: {{ $c->cliente?->razon_social ?? 'Clientes varios' }}</div>
@if ($c->cliente)<div>{{ \App\Models\Cliente::TIPOS_DOCUMENTO[$c->cliente->tipo_documento] ?? 'Doc.' }}: {{ $c->cliente->numero_documento }}</div>@endif
@if ($c->cliente?->direccion)<div>Dir.: {{ $c->cliente->direccion }}</div>@endif
<hr>
<table>
    @foreach ($c->items as $item)
        <tr><td colspan="3">{{ $item->descripcion }}</td></tr>
        <tr><td>{{ $item->cantidad + 0 }} {{ $item->unidad_medida }} x {{ number_format($item->precio_unitario, 2) }}</td><td></td><td class="der">{{ number_format($item->total, 2) }}</td></tr>
    @endforeach
</table>
<hr>
<table>
    @if ($c->op_gravadas > 0)<tr><td>Op. gravadas</td><td class="der">S/ {{ number_format($c->op_gravadas, 2) }}</td></tr>@endif
    @if ($c->op_exoneradas > 0)<tr><td>Op. exoneradas</td><td class="der">S/ {{ number_format($c->op_exoneradas, 2) }}</td></tr>@endif
    @if ($c->op_inafectas > 0)<tr><td>Op. inafectas</td><td class="der">S/ {{ number_format($c->op_inafectas, 2) }}</td></tr>@endif
    <tr><td>IGV 18%</td><td class="der">S/ {{ number_format($c->igv, 2) }}</td></tr>
    <tr class="b" style="font-size: 10px"><td>TOTAL</td><td class="der">S/ {{ number_format($c->total, 2) }}</td></tr>
</table>
<div style="margin-top: 4px">SON: {{ \App\Support\NumeroALetras::convertir((float) $c->total) }}</div>
<hr>
<div>Pago: {{ \App\Models\Caja::METODOS_PAGO[$c->metodo_pago] ?? $c->metodo_pago }}</div>
@if ($c->vendedor)<div>Vendedor: {{ $c->vendedor->name }}</div>@endif
@if ($c->observaciones)<div>Obs.: {{ $c->observaciones }}</div>@endif
@if ($qr)
    <div class="c" style="margin-top: 6px"><img src="{{ $qr }}" style="width: 90px; height: 90px"></div>
@endif
@if ($c->hash)<div class="c" style="font-size: 7px">Hash: {{ $c->hash }}</div>@endif
<div class="c" style="margin-top: 4px; font-size: 7px">
    @if ($c->seEnviaASunat())
        Representación impresa de la {{ mb_strtoupper($c->nombreTipo()) }}. Consulte su validez en www.sunat.gob.pe
    @else
        Documento interno, no es comprobante de pago.
    @endif
</div>
<div class="c" style="margin-top: 4px">¡Gracias por su compra!</div>
</body>
</html>
