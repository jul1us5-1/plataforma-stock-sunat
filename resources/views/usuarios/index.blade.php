@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('contenido')
<div class="flex items-center mb-4">
    <h1 class="text-xl font-semibold mr-auto">Usuarios</h1>
    <a href="{{ route('usuarios.create') }}" class="bg-blue-600 text-white rounded px-3 py-1.5 text-sm">+ Usuario</a>
</div>
<div class="bg-white rounded-lg shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-2">Nombre</th><th class="p-2">Correo</th><th class="p-2">Rol</th><th class="p-2">Estado</th><th class="p-2"></th></tr></thead>
    <tbody>
    @foreach ($usuarios as $u)
        <tr class="border-t {{ $u->activo ? '' : 'opacity-50' }}">
            <td class="p-2">{{ $u->name }}</td><td class="p-2">{{ $u->email }}</td>
            <td class="p-2">{{ \App\Models\User::ROLES[$u->rol] ?? $u->rol }}</td>
            <td class="p-2">{{ $u->activo ? 'Activo' : 'Desactivado' }}</td>
            <td class="p-2 text-right"><a href="{{ route('usuarios.edit', $u) }}" class="text-blue-700 hover:underline">Editar</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<p class="text-sm text-slate-500 mt-3">El vendedor puede vender, manejar su caja, registrar clientes, cotizaciones y pedidos, y ver productos. Productos, compras, reportes, notas de crédito y usuarios son solo para administradores.</p>
@endsection
