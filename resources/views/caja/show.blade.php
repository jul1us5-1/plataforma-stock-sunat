@extends('layouts.app')
@section('titulo', 'Caja #'.$caja->id)
@section('contenido')
<a href="{{ route('caja.index') }}" class="text-blue-700 hover:underline print:hidden">← Caja</a>
<h1 class="text-xl font-semibold my-4">Caja #{{ $caja->id }} · {{ $caja->usuario->name }}</h1>
<div class="grid sm:grid-cols-4 gap-4 mb-4">
    <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Apertura</div><div>{{ $caja->abierta_en->format('d/m/Y H:i') }}</div><div class="font-semibold">S/ {{ number_format($caja->monto_apertura, 2) }}</div></div>
    <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Cierre</div><div>{{ $caja->cerrada_en?->format('d/m/Y H:i') ?? 'Abierta' }}</div></div>
    <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Efectivo esperado / contado</div><div class="font-semibold">S/ {{ number_format($caja->efectivo_esperado ?? $caja->efectivoEsperado(), 2) }} / S/ {{ number_format($caja->efectivo_contado ?? 0, 2) }}</div></div>
    <div class="bg-white rounded-lg shadow p-4"><div class="text-sm text-slate-500">Diferencia</div><div class="font-semibold {{ $caja->diferencia < 0 ? 'text-red-600' : '' }}">S/ {{ number_format($caja->diferencia ?? 0, 2) }}</div></div>
</div>
@if ($caja->observaciones)<p class="mb-4">Observaciones: {{ $caja->observaciones }}</p>@endif
<div class="grid md:grid-cols-3 gap-4">
    <div class="bg-white rounded-lg shadow p-4">@include('caja.resumen')</div>
    <div class="bg-white rounded-lg shadow p-4 md:col-span-2 overflow-x-auto">@include('caja.movimientos', ['movimientos' => $caja->movimientos])</div>
</div>
<button onclick="window.print()" class="mt-4 bg-slate-900 text-white rounded px-4 py-2 print:hidden">Imprimir</button>
@endsection
