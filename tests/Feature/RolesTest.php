<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_vendedor_vende_pero_no_administra(): void
    {
        $vendedor = User::factory()->create(['rol' => 'vendedor']);
        $producto = Producto::factory()->create();

        foreach (['dashboard', 'comprobantes.create', 'comprobantes.index', 'caja.index', 'clientes.index', 'productos.index'] as $ruta) {
            $this->actingAs($vendedor)->get(route($ruta))->assertOk();
        }
        $this->actingAs($vendedor)->get(route('previos.index', 'cotizaciones'))->assertOk();
        $this->actingAs($vendedor)->get(route('productos.index'))->assertDontSee('Exportar')->assertDontSee('Usuarios');

        foreach (['productos.create', 'compras.index', 'proveedores.index', 'reportes.index', 'usuarios.index', 'productos.exportar'] as $ruta) {
            $this->actingAs($vendedor)->get(route($ruta))->assertForbidden();
        }
        $this->actingAs($vendedor)->get(route('productos.edit', $producto))->assertForbidden();
        $this->actingAs($vendedor)->post(route('productos.stock', $producto), ['tipo' => 'entrada', 'cantidad' => 5])->assertForbidden();
    }

    public function test_admin_crea_vendedor_y_lo_desactiva(): void
    {
        $admin = User::first();
        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk();
        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Rosa', 'email' => 'rosa@example.com', 'rol' => 'vendedor', 'password' => 'clave-segura',
        ])->assertRedirect();

        $rosa = User::where('email', 'rosa@example.com')->first();
        $this->assertFalse($rosa->esAdmin());

        auth()->logout();
        $this->post(route('login'), ['email' => 'rosa@example.com', 'password' => 'clave-segura'])->assertRedirect(route('dashboard'));
        auth()->logout();

        $this->actingAs($admin)->put(route('usuarios.update', $rosa), ['name' => 'Rosa', 'email' => 'rosa@example.com', 'rol' => 'vendedor', 'activo' => 0])->assertRedirect();
        auth()->logout();
        $this->post(route('login'), ['email' => 'rosa@example.com', 'password' => 'clave-segura'])->assertSessionHasErrors('email');
    }

    public function test_no_se_puede_quitar_el_ultimo_admin(): void
    {
        $admin = User::first();
        $this->actingAs($admin)->put(route('usuarios.update', $admin), ['name' => 'A', 'email' => $admin->email, 'rol' => 'vendedor', 'activo' => 1])
            ->assertSessionHasErrors('rol');
        $this->assertTrue($admin->fresh()->esAdmin());
    }

    public function test_cambiar_mi_contrasena(): void
    {
        $vendedor = User::factory()->create(['rol' => 'vendedor', 'password' => 'clave-vieja-1']);
        $this->actingAs($vendedor)->get(route('cuenta'))->assertOk();
        $this->actingAs($vendedor)->put(route('cuenta'), ['name' => 'Yo', 'password_actual' => 'mala', 'password' => 'clave-nueva-1', 'password_confirmation' => 'clave-nueva-1'])
            ->assertSessionHasErrors('password_actual');
        $this->actingAs($vendedor)->put(route('cuenta'), ['name' => 'Yo', 'password_actual' => 'clave-vieja-1', 'password' => 'clave-nueva-1', 'password_confirmation' => 'clave-nueva-1'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('clave-nueva-1', $vendedor->fresh()->password));
    }
}
