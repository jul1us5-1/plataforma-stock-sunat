<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** URL a la que volver tras guardar, solo si es de esta misma aplicación */
    protected function volver(\Illuminate\Http\Request $request, string $porDefecto): string
    {
        $volver = (string) $request->input('volver');

        return $volver !== '' && str_starts_with($volver, url('/').'/') ? $volver : $porDefecto;
    }
}
