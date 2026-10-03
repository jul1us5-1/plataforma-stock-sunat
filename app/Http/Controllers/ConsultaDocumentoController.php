<?php

namespace App\Http\Controllers;

use App\Services\ConsultaDocumento;

class ConsultaDocumentoController extends Controller
{
    public function __invoke(string $numero, ConsultaDocumento $consulta)
    {
        if (! $consulta->disponible()) {
            return response()->json(['mensaje' => 'La consulta de RUC/DNI no está configurada.'], 503);
        }

        $datos = $consulta->consultar($numero);

        return $datos ? response()->json($datos) : response()->json(['mensaje' => 'No se encontraron datos para ese número.'], 404);
    }
}
