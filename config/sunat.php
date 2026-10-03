<?php

return [
    // beta (pruebas), homologacion o produccion
    'entorno' => env('SUNAT_ENTORNO', 'beta'),

    // Envía automáticamente facturas y boletas a SUNAT al emitirlas
    'envio_automatico' => env('SUNAT_ENVIO_AUTOMATICO', true),

    'empresa' => [
        'ruc' => env('SUNAT_RUC', '20000000001'),
        'razon_social' => env('SUNAT_RAZON_SOCIAL', 'MI EMPRESA S.A.C.'),
        'nombre_comercial' => env('SUNAT_NOMBRE_COMERCIAL', 'MI EMPRESA'),
        'ubigeo' => env('SUNAT_UBIGEO', '150101'),
        'departamento' => env('SUNAT_DEPARTAMENTO', 'LIMA'),
        'provincia' => env('SUNAT_PROVINCIA', 'LIMA'),
        'distrito' => env('SUNAT_DISTRITO', 'LIMA'),
        'direccion' => env('SUNAT_DIRECCION', 'AV. PRINCIPAL 123'),
        'cod_local' => env('SUNAT_COD_LOCAL', '0000'),
    ],

    // Usuario secundario SOL. En beta SUNAT acepta MODDATOS / moddatos
    'sol_usuario' => env('SUNAT_SOL_USUARIO', 'MODDATOS'),
    'sol_clave' => env('SUNAT_SOL_CLAVE', 'moddatos'),

    // Certificado digital en formato PEM (clave privada + certificado)
    'certificado' => env('SUNAT_CERTIFICADO', storage_path('app/private/sunat/certificado.pem')),

    'igv' => 0.18,
];
