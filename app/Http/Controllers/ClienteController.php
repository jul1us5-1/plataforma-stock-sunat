<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $clientes = Cliente::query()
            ->when($request->q, fn ($q, $texto) => $q->where(fn ($q) => $q
                ->where('razon_social', 'like', "%{$texto}%")
                ->orWhere('numero_documento', 'like', "%{$texto}%")))
            ->orderBy('razon_social')
            ->paginate(25)
            ->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.form', ['cliente' => new Cliente(['tipo_documento' => '1'])]);
    }

    public function store(Request $request)
    {
        Cliente::create($this->validar($request));

        return redirect()->route('clientes.index')->with('ok', 'Cliente creado.');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.form', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($this->validar($request, $cliente));

        return redirect()->route('clientes.index')->with('ok', 'Cliente actualizado.');
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        $digitos = match ($request->tipo_documento) {
            '1' => 'digits:8',
            '6' => 'digits:11',
            default => 'max:15',
        };

        return $request->validate([
            'tipo_documento' => ['required', Rule::in(array_keys(Cliente::TIPOS_DOCUMENTO))],
            'numero_documento' => ['required', 'string', $digitos,
                Rule::unique('clientes')->where('tipo_documento', $request->tipo_documento)->ignore($cliente)],
            'razon_social' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
