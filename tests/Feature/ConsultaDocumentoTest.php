<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultaDocumentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_token_responde_no_configurado(): void
    {
        config(['services.consulta_documentos.token' => null]);

        $this->actingAs(User::factory()->create())->getJson(route('consulta-documento', '20100070970'))->assertStatus(503);
    }

    public function test_consulta_ruc_y_dni(): void
    {
        config(['services.consulta_documentos.token' => 'abc', 'services.consulta_documentos.url' => 'https://api.ejemplo.pe/v1']);
        Http::fake([
            'api.ejemplo.pe/v1/sunat/ruc*' => Http::response(['razon_social' => 'CLINICA EJEMPLO S.A.C.', 'direccion' => 'AV. ARENALES 123', 'distrito' => 'LINCE', 'estado' => 'ACTIVO', 'condicion' => 'HABIDO']),
            'api.ejemplo.pe/v1/reniec/dni*' => Http::response(['nombres' => 'MARIA', 'apellidoPaterno' => 'LOPEZ', 'apellidoMaterno' => 'QUISPE']),
        ]);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->getJson(route('consulta-documento', '20100070970'))
            ->assertOk()->assertJson(['razon_social' => 'CLINICA EJEMPLO S.A.C.', 'direccion' => 'AV. ARENALES 123, LINCE']);
        $this->actingAs($usuario)->getJson(route('consulta-documento', '72665831'))
            ->assertOk()->assertJson(['razon_social' => 'LOPEZ QUISPE MARIA']);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer abc') && str_contains($request->url(), 'numero=72665831'));
        $this->actingAs($usuario)->get(route('clientes.create'))->assertOk()->assertSee('Buscar');
    }

    public function test_no_encontrado(): void
    {
        config(['services.consulta_documentos.token' => 'abc', 'services.consulta_documentos.url' => 'https://api.ejemplo.pe/v1']);
        Http::fake(['*' => Http::response(['message' => 'not found'], 404)]);

        $this->actingAs(User::factory()->create())->getJson(route('consulta-documento', '12345678'))->assertNotFound();
    }

    public function test_volver_solo_a_urls_propias(): void
    {
        $usuario = User::factory()->create();
        $datos = ['tipo_documento' => '1', 'numero_documento' => '12345678', 'razon_social' => 'Ana'];

        $this->actingAs($usuario)->post(route('clientes.store'), $datos + ['volver' => route('comprobantes.create')])
            ->assertRedirect(route('comprobantes.create'));
        $this->actingAs($usuario)->post(route('clientes.store'), ['numero_documento' => '87654321', 'volver' => 'https://malicioso.com/x'] + $datos)
            ->assertRedirect(route('clientes.index'));
    }
}
