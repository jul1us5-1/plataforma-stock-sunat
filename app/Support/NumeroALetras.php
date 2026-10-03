<?php

namespace App\Support;

/**
 * Convierte importes a texto para la leyenda 1000 de SUNAT.
 * Ejemplo: 1250.5 => "MIL DOSCIENTOS CINCUENTA CON 50/100 SOLES"
 */
class NumeroALetras
{
    private const UNIDADES = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
        'VEINTE', 'VEINTIUNO', 'VEINTIDOS', 'VEINTITRES', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISEIS',
        'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];

    private const DECENAS = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];

    private const CENTENAS = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    public static function convertir(float $monto, string $moneda = 'SOLES'): string
    {
        $centimos = (int) round($monto * 100);
        $entero = intdiv($centimos, 100);
        $decimales = $centimos % 100;

        $texto = $entero === 0 ? 'CERO' : self::entero($entero);

        return sprintf('%s CON %02d/100 %s', $texto, $decimales, $moneda);
    }

    private static function entero(int $n): string
    {
        if ($n >= 1000000) {
            $millones = intdiv($n, 1000000);
            $resto = $n % 1000000;
            $texto = $millones === 1 ? 'UN MILLON' : self::entero($millones).' MILLONES';

            return trim($texto.' '.($resto ? self::entero($resto) : ''));
        }

        if ($n >= 1000) {
            $miles = intdiv($n, 1000);
            $resto = $n % 1000;
            $texto = $miles === 1 ? 'MIL' : self::apocopar(self::entero($miles)).' MIL';

            return trim($texto.' '.($resto ? self::entero($resto) : ''));
        }

        if ($n === 100) {
            return 'CIEN';
        }

        $texto = self::CENTENAS[intdiv($n, 100)];
        $resto = $n % 100;

        if ($resto < 30) {
            $parte = self::UNIDADES[$resto];
        } else {
            $parte = self::DECENAS[intdiv($resto, 10)];
            if ($resto % 10) {
                $parte .= ' Y '.self::UNIDADES[$resto % 10];
            }
        }

        return trim($texto.' '.$parte);
    }

    // "VEINTIUNO MIL" => "VEINTIUN MIL", "UNO MIL" => "UN MIL"
    private static function apocopar(string $texto): string
    {
        return preg_replace(['/VEINTIUNO$/', '/UNO$/'], ['VEINTIUN', 'UN'], $texto);
    }
}
