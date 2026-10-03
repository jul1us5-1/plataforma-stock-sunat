<form class="bg-white rounded-lg shadow p-3 flex flex-wrap items-end gap-2 mb-4" x-data="{ periodo: '{{ $periodo->tipo }}' }">
    <label class="text-sm">Periodo
        <select name="periodo" x-model="periodo" class="block border rounded px-3 py-1.5">
            @foreach (\App\Support\Periodo::OPCIONES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
        </select>
    </label>
    <template x-if="periodo === 'rango'">
        <div class="flex gap-2">
            <label class="text-sm">Desde<input type="date" name="desde" value="{{ $periodo->desde->format('Y-m-d') }}" class="block border rounded px-3 py-1.5"></label>
            <label class="text-sm">Hasta<input type="date" name="hasta" value="{{ $periodo->hasta->format('Y-m-d') }}" class="block border rounded px-3 py-1.5"></label>
        </div>
    </template>
    <button class="bg-blue-600 text-white rounded px-4 py-1.5">Ver</button>
    <span class="text-sm text-slate-500 ml-auto">{{ $periodo->texto() }}</span>
    {{ $slot ?? '' }}
</form>
