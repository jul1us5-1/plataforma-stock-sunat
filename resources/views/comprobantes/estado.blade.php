@php
    $colores = ['aceptado' => 'bg-emerald-100 text-emerald-800', 'observado' => 'bg-amber-100 text-amber-800', 'rechazado' => 'bg-red-100 text-red-800',
        'error' => 'bg-red-100 text-red-800', 'pendiente' => 'bg-slate-200 text-slate-700', 'no_aplica' => 'bg-slate-100 text-slate-500', 'anulado' => 'bg-slate-300 text-slate-700'];
    $textos = ['no_aplica' => 'No se envía'];
@endphp
<span class="inline-block rounded px-2 py-0.5 text-xs font-medium {{ $colores[$estado] ?? '' }}">{{ $textos[$estado] ?? ucfirst($estado) }}</span>
