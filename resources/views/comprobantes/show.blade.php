@extends('layouts.app')
@section('titulo', $comprobante->numero())
@section('contenido')
<div class="flex flex-wrap items-center gap-2 mb-4 print:hidden">
    <a href="{{ route('comprobantes.index') }}" class="text-blue-700 hover:underline mr-auto">← Comprobantes</a>
    @if ($comprobante->xml_path)<a href="{{ route('comprobantes.descargar', [$comprobante, 'xml']) }}" class="border bg-white rounded px-3 py-1.5">XML</a>@endif
    @if ($comprobante->cdr_path)<a href="{{ route('comprobantes.descargar', [$comprobante, 'cdr']) }}" class="border bg-white rounded px-3 py-1.5">CDR</a>@endif
    @if ($comprobante->seEnviaASunat() && in_array($comprobante->estado_sunat, ['pendiente', 'error']))
        <form method="POST" action="{{ route('comprobantes.reenviar', $comprobante) }}">@csrf<button class="bg-amber-500 text-white rounded px-3 py-1.5">Enviar a SUNAT</button></form>
    @endif
    <a href="{{ route('comprobantes.pdf', [$comprobante, 'ticket']) }}" target="_blank" class="bg-slate-900 text-white rounded px-3 py-1.5">Ticket 80 mm</a>
    <a href="{{ route('comprobantes.pdf', [$comprobante, 'a4']) }}" target="_blank" class="bg-slate-900 text-white rounded px-3 py-1.5">PDF A4</a>
    <a href="{{ route('comprobantes.pdf', [$comprobante, 'a4']) }}?descargar=1" class="border bg-white rounded px-3 py-1.5">Descargar PDF</a>
    @php
        $enlace = \Illuminate\Support\Facades\URL::signedRoute('comprobantes.publico', [$comprobante, 'a4']);
        $mensaje = "Hola, te envío tu {$comprobante->nombreTipo()} {$comprobante->numero()} por S/ ".number_format($comprobante->total, 2).": {$enlace}";
        $telefono = preg_replace('/\D/', '', (string) $comprobante->cliente?->telefono);
        if (strlen($telefono) === 9) { $telefono = '51'.$telefono; }
    @endphp
    <a href="https://wa.me/{{ $telefono }}?text={{ rawurlencode($mensaje) }}" target="_blank" class="bg-green-600 text-white rounded px-3 py-1.5">WhatsApp</a>
</div>
<div class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
    <div class="flex justify-between gap-4 mb-6">
        <div>
            <div class="font-semibold text-lg">{{ config('sunat.empresa.razon_social') }}</div>
            <div class="text-sm">RUC {{ config('sunat.empresa.ruc') }}</div>
            <div class="text-sm">{{ config('sunat.empresa.direccion') }}</div>
        </div>
        <div class="border-2 border-slate-800 rounded p-3 text-center">
            <div class="text-sm">RUC {{ config('sunat.empresa.ruc') }}</div>
            <div class="font-semibold uppercase">{{ $comprobante->nombreTipo() }}</div>
            <div class="font-mono">{{ $comprobante->numero() }}</div>
        </div>
    </div>
    <div class="text-sm mb-4 grid grid-cols-2 gap-1">
        <div><strong>Cliente:</strong> {{ $comprobante->cliente?->razon_social ?? 'Clientes varios' }}</div>
        <div><strong>Fecha:</strong> {{ $comprobante->fecha_emision->format('d/m/Y H:i') }}</div>
        @if ($comprobante->vendedor)<div><strong>Vendedor:</strong> {{ $comprobante->vendedor->name }}</div>@endif
        <div><strong>Pago:</strong> {{ \App\Models\Caja::METODOS_PAGO[$comprobante->metodo_pago] ?? $comprobante->metodo_pago }}</div>
        @if ($comprobante->cliente)<div><strong>Documento:</strong> {{ $comprobante->cliente->numero_documento }}</div>@endif
        @if ($comprobante->cliente?->direccion)<div><strong>Dirección:</strong> {{ $comprobante->cliente->direccion }}</div>@endif
    </div>
    <table class="w-full text-sm mb-4">
        <thead class="border-b-2 text-left"><tr><th class="py-1">Cant.</th><th>Descripción</th><th class="text-right">P. unit.</th><th class="text-right">Importe</th></tr></thead>
        <tbody>
        @foreach ($comprobante->items as $item)
            <tr class="border-b"><td class="py-1">{{ $item->cantidad + 0 }}</td><td>{{ $item->descripcion }}</td><td class="text-right">{{ number_format($item->precio_unitario, 2) }}</td><td class="text-right">{{ number_format($item->total, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="ml-auto w-64 text-sm space-y-1">
        @if ($comprobante->op_gravadas > 0)<div class="flex justify-between"><span>Op. gravadas</span><span>S/ {{ number_format($comprobante->op_gravadas, 2) }}</span></div>@endif
        @if ($comprobante->op_exoneradas > 0)<div class="flex justify-between"><span>Op. exoneradas</span><span>S/ {{ number_format($comprobante->op_exoneradas, 2) }}</span></div>@endif
        @if ($comprobante->op_inafectas > 0)<div class="flex justify-between"><span>Op. inafectas</span><span>S/ {{ number_format($comprobante->op_inafectas, 2) }}</span></div>@endif
        <div class="flex justify-between"><span>IGV 18%</span><span>S/ {{ number_format($comprobante->igv, 2) }}</span></div>
        <div class="flex justify-between font-semibold text-base border-t pt-1"><span>Total</span><span>S/ {{ number_format($comprobante->total, 2) }}</span></div>
    </div>
    @if ($comprobante->observaciones)<p class="text-sm mt-4"><strong>Observaciones:</strong> {{ $comprobante->observaciones }}</p>@endif
    <p class="text-sm mt-4">SON: {{ \App\Support\NumeroALetras::convertir((float) $comprobante->total) }}</p>
    @if ($comprobante->hash)<p class="text-xs text-slate-500 mt-2">Hash: {{ $comprobante->hash }}</p>@endif
    <div class="mt-4 text-sm print:hidden">
        Estado SUNAT: @include('comprobantes.estado', ['estado' => $comprobante->estado_sunat])
        @if ($comprobante->sunat_mensaje)<div class="text-slate-600 mt-1">{{ $comprobante->sunat_codigo }} {{ $comprobante->sunat_mensaje }}</div>@endif
    </div>
</div>
@endsection
