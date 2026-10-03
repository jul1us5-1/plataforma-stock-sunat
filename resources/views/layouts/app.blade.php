<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen" x-data="{ menu: false }">
@auth
@php
    $secciones = [
        ['dashboard', 'Dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
        ['comprobantes.create', 'Nueva venta', 'M12 4v16m8-8H4'],
        ['comprobantes.index', 'Comprobantes', 'M7 3h10l4 4v14H3V3h4zm0 6h10M7 13h10M7 17h6'],
        ['previos.index:cotizaciones', 'Cotizaciones', 'M9 5h6M9 3h6v4H9zM5 7h14v14H5zM9 12h6M9 16h4'],
        ['previos.index:pedidos', 'Pedidos', 'M3 7l9-4 9 4-9 4-9-4zm0 0v10l9 4 9-4V7'],
        ['caja.index', 'Caja', 'M3 7h18v12H3zM3 11h18M7 15h2'],
        ['productos.index', 'Productos', 'M4 4h7v7H4zm9 0h7v7h-7zM4 13h7v7H4zm9 0h7v7h-7z'],
        ['clientes.index', 'Clientes', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21v-1a6 6 0 0112 0v1'],
        ['reportes.index', 'Reportes', 'M4 20V10m6 10V4m6 16v-7m4 7H2'],
    ];
    // Una sección está activa en su ruta o en cualquier ruta hermana (productos.edit activa Productos), salvo "Nueva venta"
    // "previos.index:pedidos" es la ruta previos.index con el parámetro pedidos
    $enlace = fn ($ruta) => str_contains($ruta, ':') ? route(...explode(':', $ruta)) : route($ruta);
    $activa = function ($ruta) {
        if (str_contains($ruta, ':')) {
            [$nombre, $parametro] = explode(':', $ruta);

            return request()->routeIs('previos.*') && request()->route('ruta') === $parametro;
        }

        return request()->routeIs($ruta) || (! in_array($ruta, ['dashboard', 'comprobantes.create'])
            && request()->routeIs(\Illuminate\Support\Str::beforeLast($ruta, '.').'.*') && ! request()->routeIs('comprobantes.create'));
    };
@endphp
<div class="fixed inset-0 bg-black/40 z-30 lg:hidden" x-show="menu" x-cloak @click="menu = false"></div>
<aside class="fixed inset-y-0 left-0 z-40 w-60 bg-white border-r transform transition-transform lg:translate-x-0 print:hidden"
       :class="menu ? 'translate-x-0' : '-translate-x-full'">
    <div class="h-16 flex items-center px-5 font-semibold text-blue-700 border-b">{{ config('sunat.empresa.nombre_comercial') ?: config('app.name') }}</div>
    <nav class="p-3 space-y-1">
        @foreach ($secciones as [$ruta, $texto, $icono])
            <a href="{{ $enlace($ruta) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ $activa($ruta) ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono }}"/></svg>
                {{ $texto }}
            </a>
        @endforeach
    </nav>
</aside>
<header class="lg:pl-60 bg-white border-b h-16 flex items-center gap-2 px-4 print:hidden">
    <button class="lg:hidden p-2 -ml-2" @click="menu = true" aria-label="Menú">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <a href="{{ route('comprobantes.create') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm px-3 py-1.5 rounded">+ Venta</a>
    <a href="{{ route('caja.index') }}" class="border text-sm px-3 py-1.5 rounded hover:bg-slate-50">Caja</a>
    <span class="ml-auto text-xs font-semibold px-2 py-1 rounded {{ config('sunat.entorno') === 'produccion' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
        SUNAT {{ config('sunat.entorno') === 'produccion' ? 'PRODUCCIÓN' : strtoupper(config('sunat.entorno')) }}
    </span>
    <span class="hidden sm:block text-sm text-right leading-tight"><span class="font-medium">{{ auth()->user()->name }}</span><br><span class="text-slate-500 text-xs">{{ auth()->user()->email }}</span></span>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-slate-500 hover:text-slate-900 px-2">Salir</button></form>
</header>
@endauth
<main class="@auth lg:pl-60 @endauth">
    <div class="max-w-7xl mx-auto p-4">
        @if (session('ok'))
            <div class="mb-4 rounded bg-emerald-100 border border-emerald-300 px-4 py-2">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 border border-red-300 px-4 py-2">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        @yield('contenido')
    </div>
</main>
</body>
</html>
