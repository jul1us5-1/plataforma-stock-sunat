@php($colores = ['pendiente' => 'bg-amber-100 text-amber-800', 'convertido' => 'bg-emerald-100 text-emerald-800', 'anulado' => 'bg-slate-200 text-slate-600'])
<span class="inline-block rounded px-2 py-0.5 text-xs font-medium {{ $colores[$estado] ?? '' }}">{{ $estado === 'convertido' ? 'Vendido' : ucfirst($estado) }}</span>
