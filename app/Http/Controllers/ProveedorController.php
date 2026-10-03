<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $proveedores = Proveedor::withCount('compras')
            ->when($request->q, fn ($q, $texto) => $q->where(fn ($q) => $q
                ->where('razon_social', 'like', "%{$texto}%")->orWhere('ruc', 'like', "%{$texto}%")))
            ->orderBy('razon_social')->paginate(25)->withQueryString();

        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.form', ['proveedor' => new Proveedor]);
    }

    public function store(Request $request)
    {
        $proveedor = Proveedor::create($this->validar($request));

        return redirect($request->input('volver', route('proveedores.index')))->with('ok', "Proveedor {$proveedor->razon_social} creado.");
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.form', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $proveedor->update($this->validar($request, $proveedor));

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor actualizado.');
    }

    private function validar(Request $request, ?Proveedor $proveedor = null): array
    {
        return $request->validate([
            'ruc' => ['nullable', 'digits:11', Rule::unique('proveedores')->ignore($proveedor)],
            'razon_social' => ['required', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
