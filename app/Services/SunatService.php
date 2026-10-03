<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Support\NumeroALetras;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Response\BillResult;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SunatService
{
    /**
     * Construye el documento UBL 2.1 de Greenter a partir de un comprobante.
     */
    public function construirDocumento(Comprobante $comprobante): Invoice
    {
        $comprobante->loadMissing(['items', 'cliente']);
        $empresa = config('sunat.empresa');
        $tasaIgv = (float) config('sunat.igv') * 100;

        $company = (new Company)
            ->setRuc($empresa['ruc'])
            ->setRazonSocial($empresa['razon_social'])
            ->setNombreComercial($empresa['nombre_comercial'])
            ->setAddress((new Address)
                ->setUbigueo($empresa['ubigeo'])
                ->setDepartamento($empresa['departamento'])
                ->setProvincia($empresa['provincia'])
                ->setDistrito($empresa['distrito'])
                ->setUrbanizacion('-')
                ->setDireccion($empresa['direccion'])
                ->setCodLocal($empresa['cod_local']));

        $cliente = $comprobante->cliente;
        $client = (new Client)
            ->setTipoDoc($cliente?->tipo_documento ?? '0')
            ->setNumDoc($cliente?->numero_documento ?? '00000000')
            ->setRznSocial($cliente?->razon_social ?? 'CLIENTES VARIOS');

        $detalles = $comprobante->items->map(function ($item) use ($tasaIgv) {
            $gravado = $item->afectacion_igv === '10';

            return (new SaleDetail)
                ->setCodProducto($item->codigo)
                ->setUnidad($item->unidad_medida)
                ->setCantidad((float) $item->cantidad)
                ->setDescripcion($item->descripcion)
                ->setMtoValorUnitario((float) $item->valor_unitario)
                ->setMtoBaseIgv((float) $item->valor_venta)
                ->setPorcentajeIgv($gravado ? $tasaIgv : 0)
                ->setIgv((float) $item->igv)
                ->setTipAfeIgv($item->afectacion_igv)
                ->setTotalImpuestos((float) $item->igv)
                ->setMtoValorVenta((float) $item->valor_venta)
                ->setMtoPrecioUnitario((float) $item->precio_unitario);
        })->all();

        $valorVenta = (float) $comprobante->op_gravadas + (float) $comprobante->op_exoneradas + (float) $comprobante->op_inafectas;

        return (new Invoice)
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')
            ->setTipoDoc($comprobante->tipo_comprobante)
            ->setSerie($comprobante->serie)
            ->setCorrelativo((string) $comprobante->correlativo)
            ->setFechaEmision($comprobante->fecha_emision)
            ->setFormaPago(new FormaPagoContado)
            ->setTipoMoneda($comprobante->moneda)
            ->setCompany($company)
            ->setClient($client)
            ->setMtoOperGravadas((float) $comprobante->op_gravadas)
            ->setMtoOperExoneradas((float) $comprobante->op_exoneradas)
            ->setMtoOperInafectas((float) $comprobante->op_inafectas)
            ->setMtoIGV((float) $comprobante->igv)
            ->setTotalImpuestos((float) $comprobante->igv)
            ->setValorVenta($valorVenta)
            ->setSubTotal((float) $comprobante->total)
            ->setMtoImpVenta((float) $comprobante->total)
            ->setDetails($detalles)
            ->setLegends([(new Legend)->setCode('1000')->setValue(NumeroALetras::convertir((float) $comprobante->total))]);
    }

    /**
     * Firma y envía el comprobante a SUNAT, guardando XML, CDR y estado.
     */
    public function enviar(Comprobante $comprobante): Comprobante
    {
        if (! $comprobante->seEnviaASunat()) {
            return $comprobante;
        }

        try {
            $see = $this->cliente();
            $documento = $this->construirDocumento($comprobante);
            $resultado = $see->send($documento);

            $nombre = $documento->getName();
            $xmlPath = "sunat/xml/{$nombre}.xml";
            Storage::put($xmlPath, $see->getFactory()->getLastXml());
            $comprobante->xml_path = $xmlPath;
            $comprobante->hash = $this->extraerHash($see->getFactory()->getLastXml());

            if (! $resultado->isSuccess()) {
                $error = $resultado->getError();
                $comprobante->estado_sunat = 'rechazado';
                $comprobante->sunat_codigo = $error?->getCode();
                $comprobante->sunat_mensaje = $error?->getMessage();
            } else {
                /** @var BillResult $resultado */
                $cdrPath = "sunat/cdr/R-{$nombre}.zip";
                Storage::put($cdrPath, $resultado->getCdrZip());
                $cdr = $resultado->getCdrResponse();
                $codigo = (int) $cdr->getCode();

                $comprobante->cdr_path = $cdrPath;
                $comprobante->sunat_codigo = $cdr->getCode();
                $comprobante->sunat_mensaje = trim($cdr->getDescription().' '.implode(' | ', $cdr->getNotes() ?? []));
                $comprobante->estado_sunat = match (true) {
                    $codigo === 0 && empty($cdr->getNotes()) => 'aceptado',
                    $codigo === 0 || $codigo >= 4000 => 'observado',
                    default => 'rechazado',
                };
            }
        } catch (Throwable $e) {
            $comprobante->estado_sunat = 'error';
            $comprobante->sunat_mensaje = $e->getMessage();
            report($e);
        }

        $comprobante->save();

        return $comprobante;
    }

    private function cliente(): See
    {
        $certificado = config('sunat.certificado');
        if (! is_file($certificado)) {
            throw new \RuntimeException("No se encontró el certificado digital en {$certificado}.");
        }

        $see = new See;
        $see->setCertificate(file_get_contents($certificado));
        $see->setService(match (config('sunat.entorno')) {
            'produccion' => SunatEndpoints::FE_PRODUCCION,
            'homologacion' => SunatEndpoints::FE_HOMOLOGACION,
            default => SunatEndpoints::FE_BETA,
        });
        $see->setClaveSOL(config('sunat.empresa.ruc'), config('sunat.sol_usuario'), config('sunat.sol_clave'));

        return $see;
    }

    private function extraerHash(?string $xml): ?string
    {
        if (! $xml || ! preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/', $xml, $m)) {
            return null;
        }

        return $m[1];
    }
}
