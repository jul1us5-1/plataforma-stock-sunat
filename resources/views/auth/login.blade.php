@extends('layouts.app')
@section('titulo', 'Ingresar')
@section('contenido')
<div class="max-w-sm mx-auto mt-20 bg-white rounded-lg shadow p-6">
    <h1 class="text-xl font-semibold mb-4">{{ config('app.name') }}</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-3">
        @csrf
        <input name="email" type="email" value="{{ old('email') }}" placeholder="Correo" required autofocus class="w-full border rounded px-3 py-2">
        <input name="password" type="password" placeholder="Contraseña" required class="w-full border rounded px-3 py-2">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Recordarme</label>
        <button class="w-full bg-slate-900 text-white rounded py-2">Ingresar</button>
    </form>
</div>
@endsection
