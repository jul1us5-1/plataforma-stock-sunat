@extends('layouts.app')
@section('titulo', 'Mi cuenta')
@section('contenido')
<h1 class="text-xl font-semibold mb-4">Mi cuenta</h1>
<form method="POST" action="{{ route('cuenta') }}" class="bg-white rounded-lg shadow p-4 grid gap-4 max-w-md">
    @csrf @method('PUT')
    <label class="block">Nombre<input name="name" value="{{ old('name', $usuario->name) }}" required class="w-full border rounded px-3 py-2"></label>
    <div class="text-sm text-slate-500">{{ $usuario->email }} · {{ \App\Models\User::ROLES[$usuario->rol] ?? $usuario->rol }}</div>
    <hr>
    <label class="block">Contraseña actual<input name="password_actual" type="password" autocomplete="current-password" class="w-full border rounded px-3 py-2"></label>
    <label class="block">Nueva contraseña<input name="password" type="password" autocomplete="new-password" minlength="8" class="w-full border rounded px-3 py-2"></label>
    <label class="block">Repite la nueva contraseña<input name="password_confirmation" type="password" autocomplete="new-password" class="w-full border rounded px-3 py-2"></label>
    <button class="bg-slate-900 text-white rounded px-4 py-2">Guardar</button>
</form>
@endsection
