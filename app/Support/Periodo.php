<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Rango de fechas elegido en los filtros de dashboard y reportes.
 */
class Periodo
{
    public const OPCIONES = [
        'hoy' => 'Hoy',
        'ayer' => 'Ayer',
        'semana' => 'Esta semana',
        'mes' => 'Este mes',
        'mes_anterior' => 'Mes anterior',
        'rango' => 'Rango de fechas',
    ];

    public function __construct(public string $tipo, public Carbon $desde, public Carbon $hasta) {}

    public static function desdeRequest(Request $request, string $porDefecto = 'hoy'): self
    {
        $tipo = array_key_exists($request->periodo, self::OPCIONES) ? $request->periodo : $porDefecto;
        $hoy = today();

        [$desde, $hasta] = match ($tipo) {
            'ayer' => [$hoy->copy()->subDay(), $hoy->copy()->subDay()],
            'semana' => [$hoy->copy()->startOfWeek(), $hoy->copy()],
            'mes' => [$hoy->copy()->startOfMonth(), $hoy->copy()],
            'mes_anterior' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            'rango' => [self::fecha($request->desde) ?? $hoy->copy(), self::fecha($request->hasta) ?? $hoy->copy()],
            default => [$hoy->copy(), $hoy->copy()],
        };

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return new self($tipo, $desde, $hasta);
    }

    public function texto(): string
    {
        return $this->desde->isSameDay($this->hasta)
            ? $this->desde->format('d/m/Y')
            : $this->desde->format('d/m/Y').' al '.$this->hasta->format('d/m/Y');
    }

    private static function fecha(?string $valor): ?Carbon
    {
        try {
            return $valor ? Carbon::createFromFormat('Y-m-d', $valor)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
