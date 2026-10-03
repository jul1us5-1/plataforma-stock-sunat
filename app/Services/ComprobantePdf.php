<?php

namespace App\Services;

use App\Models\Comprobante;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Representación impresa de los comprobantes en A4 o ticket de 80 mm.
 */
class ComprobantePdf
{
    public const FORMATOS = ['a4', 'ticket'];

    public function generar(Comprobante $comprobante, string $formato = 'a4'): \Barryvdh\DomPDF\PDF
    {
        $comprobante->loadMissing(['items', 'cliente', 'vendedor']);

        $datos = [
            'c' => $comprobante,
            'empresa' => config('sunat.empresa'),
            'qr' => $comprobante->seEnviaASunat() ? $this->qr($comprobante) : null,
            'logo' => $this->logo(),
        ];

        $pdf = Pdf::loadView("pdf.{$formato}", $datos);

        if ($formato === 'ticket') {
            // 80 mm de ancho; el alto crece con la cantidad de ítems
            $alto = 420 + $comprobante->items->count() * 28 + ($comprobante->observaciones ? 40 : 0);
            $pdf->setPaper([0, 0, 226.77, $alto]);
        } else {
            $pdf->setPaper('a4');
        }

        return $pdf;
    }

    public function nombreArchivo(Comprobante $comprobante): string
    {
        return config('sunat.empresa.ruc').'-'.$comprobante->tipo_comprobante.'-'.$comprobante->serie.'-'.$comprobante->correlativo.'.pdf';
    }

    /**
     * Contenido del QR según SUNAT:
     * RUC | tipo | serie | número | IGV | total | fecha | tipo doc. cliente | número doc. cliente | hash |
     */
    public function contenidoQr(Comprobante $comprobante): string
    {
        return implode('|', [
            config('sunat.empresa.ruc'),
            $comprobante->tipo_comprobante,
            $comprobante->serie,
            $comprobante->correlativo,
            number_format((float) $comprobante->igv, 2, '.', ''),
            number_format((float) $comprobante->total, 2, '.', ''),
            $comprobante->fecha_emision->format('Y-m-d'),
            $comprobante->cliente?->tipo_documento ?? '0',
            $comprobante->cliente?->numero_documento ?? '00000000',
            $comprobante->hash ?? '',
        ]).'|';
    }

    private function qr(Comprobante $comprobante): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(160, 1), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($this->contenidoQr($comprobante)));
    }

    private function logo(): ?string
    {
        $ruta = config('sunat.empresa.logo');
        if (! $ruta || ! is_file($ruta)) {
            return null;
        }

        return 'data:'.mime_content_type($ruta).';base64,'.base64_encode(file_get_contents($ruta));
    }
}
