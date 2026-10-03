@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('contenido')
<h1 class="text-xl font-semibold mb-4">{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h1>
<form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="bg-white rounded-lg shadow p-4 grid sm:grid-cols-2 gap-4 max-w-3xl">
    @csrf @if ($usuario->exists) @method('PUT') @endif
    <label class="block">Nombre<input name="name" value="{{ old('name', $usuario->name) }}" required class="w-full border rounded px-3 py-2"></label>
    <label class="block">Correo (para iniciar sesión)<input name="email" type="email" value="{{ old('email', $usuario->email) }}" required class="w-full border rounded px-3 py-2"></label>
    <label class="block">Rol
        <select name="rol" class="w-full border rounded px-3 py-2">
            @foreach (\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected(old('rol', $usuario->rol) === $k)>{{ $v }}</option>@endforeach
        </select>
    </label>
    <label class="block">Contraseña {{ $usuario->exists ? '(déjala vacía para no cambiarla)' : '' }}<input name="password" type="password" autocomplete="new-password" {{ $usuario->exists ? '' : 'required' }} minlength="8" class="w-full border rounded px-3 py-2"></label>
    @if ($usuario->exists)
        <input type="hidden" name="activo" value="0">
        <label class="flex items-center gap-2"><input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->activo))> Activo (puede iniciar sesión)</label>
    @endif
    <div class="sm:col-span-2 flex gap-2"><button class="bg-slate-900 text-white rounded px-4 py-2">Guardar</button><a href="{{ route('usuarios.index') }}" class="px-4 py-2">Cancelar</a></div>
</form>
@endsection
