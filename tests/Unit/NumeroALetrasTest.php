<?php

namespace Tests\Unit;

use App\Support\NumeroALetras;
use PHPUnit\Framework\TestCase;

class NumeroALetrasTest extends TestCase
{
    public function test_convierte_importes(): void
    {
        $this->assertSame('CERO CON 50/100 SOLES', NumeroALetras::convertir(0.5));
        $this->assertSame('CIEN CON 00/100 SOLES', NumeroALetras::convertir(100));
        $this->assertSame('DOSCIENTOS TREINTA Y SEIS CON 00/100 SOLES', NumeroALetras::convertir(236));
        $this->assertSame('MIL DOSCIENTOS CINCUENTA CON 50/100 SOLES', NumeroALetras::convertir(1250.5));
        $this->assertSame('VEINTIUN MIL CIENTO UNO CON 99/100 SOLES', NumeroALetras::convertir(21101.99));
        $this->assertSame('UN MILLON DOS MIL CON 00/100 SOLES', NumeroALetras::convertir(1002000));
    }
}
