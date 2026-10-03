<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
@auth
<nav class="bg-slate-900 text-white">
    <div class="max-w-7xl mx-auto px-4 flex flex-wrap items-center gap-1 py-2">
        <a href="{{ route('dashboard') }}" class="font-semibold mr-4">{{ config('app.name') }}</a>
        @foreach (['dashboard' => 'Inicio', 'productos.index' => 'Productos', 'clientes.index' => 'Clientes', 'comprobantes.index' => 'Comprobantes', 'caja.index' => 'Caja'] as $ruta => $texto)
            <a href="{{ route($ruta) }}" class="px-3 py-1.5 rounded {{ request()->routeIs(str_replace('.index', '.*', $ruta)) ? 'bg-slate-700' : 'hover:bg-slate-800' }}">{{ $texto }}</a>
        @endforeach
        <a href="{{ route('comprobantes.create') }}" class="ml-auto bg-emerald-600 hover:bg-emerald-500 px-3 py-1.5 rounded font-medium">+ Nueva venta</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="px-3 py-1.5 text-slate-300 hover:text-white">Salir</button></form>
    </div>
</nav>
@endauth
<main class="max-w-7xl mx-auto p-4">
    @if (session('ok'))
        <div class="mb-4 rounded bg-emerald-100 border border-emerald-300 px-4 py-2">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded bg-red-100 border border-red-300 px-4 py-2">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    @yield('contenido')
</main>
</body>
</html>
