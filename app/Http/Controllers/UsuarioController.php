<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', ['usuarios' => User::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('usuarios.form', ['usuario' => new User(['rol' => 'vendedor', 'activo' => true])]);
    }

    public function store(Request $request)
    {
        User::create($this->validar($request) + ['activo' => true]);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado.');
    }

    public function edit(User $usuario)
    {
        return view('usuarios.form', compact('usuario'));
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $this->validar($request, $usuario);
        $datos['activo'] = $request->boolean('activo');

        // Evita quedarse sin administradores activos
        $quedanAdmins = User::where('rol', 'admin')->where('activo', true)->whereKeyNot($usuario->id)->exists();
        if ($usuario->esAdmin() && ($datos['rol'] !== 'admin' || ! $datos['activo']) && ! $quedanAdmins) {
            return back()->withErrors(['rol' => 'Debe quedar al menos un administrador activo.']);
        }

        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    public function cuenta(Request $request)
    {
        return view('usuarios.cuenta', ['usuario' => $request->user()]);
    }

    public function actualizarCuenta(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password_actual' => ['required_with:password', 'nullable', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(array_filter(['name' => $datos['name'], 'password' => $datos['password'] ?? null]));

        return back()->with('ok', 'Tus datos se actualizaron.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($usuario)],
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$usuario ? 'nullable' : 'required', Password::min(8)],
        ]);
    }
}
