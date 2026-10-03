# Plataforma de stock y comprobantes electrónicos (SUNAT)

Aplicación web en **Laravel** para:

- Registrar productos y controlar su stock (kardex con cada entrada, salida y ajuste).
- Importar el catálogo de productos desde un CSV/Excel exportado.
- Registrar clientes (DNI, RUC, carnet de extranjería, pasaporte).
- Emitir **facturas** (01), **boletas** (03) y **recibos internos** (notas de venta que no van a SUNAT), indicando el método de pago (efectivo, tarjeta, Yape, Plin, transferencia).
- Manejar la **caja**: apertura con efectivo inicial, cada venta entra sola a la caja abierta, gastos e ingresos manuales, y cierre con arqueo (efectivo esperado vs. contado).
- Enviar facturas y boletas a **SUNAT** en formato UBL 2.1 firmado, usando [Greenter](https://github.com/thegreenter/greenter), y guardar el XML y el CDR de respuesta.

## Requisitos

- PHP 8.3+ con extensiones `soap`, `openssl`, `dom`, `zip`, `mbstring`, `pdo_sqlite` (o `pdo_mysql`)
- Composer

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Entra a http://localhost:8000 con el usuario definido en `ADMIN_EMAIL` / `ADMIN_PASSWORD` (cámbialo antes de correr el seeder).

El seeder crea las series `F001` (facturas), `B001` (boletas) y `R001` (recibos internos).

## Importar productos

En **Productos → Importar productos desde CSV** sube un archivo con encabezados. Separador `,` o `;`.

| columna | obligatoria | ejemplo |
|---|---|---|
| codigo | sí | P-0001 |
| nombre | sí | Arroz extra 5kg |
| precio_venta | sí (IGV incluido) | 25.90 |
| stock | no | 40 |
| stock_minimo | no | 5 |
| categoria | no | Abarrotes |
| unidad_medida | no (`NIU` unidad, `ZZ` servicio, `KGM`, `LTR`...) | NIU |
| afectacion_igv | no (`10` gravado, `20` exonerado, `30` inafecto) | 10 |
| descripcion | no | |

Si el código ya existe, el producto se actualiza y el stock se ajusta al valor del archivo (queda registrado en el kardex).

## SUNAT

Por defecto la app apunta al **entorno beta** de SUNAT con las credenciales de prueba (`MODDATOS`). Para pasar a producción:

1. Obtén tu **certificado digital** (o el gratuito de SUNAT) y conviértelo a PEM:
   `openssl pkcs12 -in certificado.pfx -out storage/app/private/sunat/certificado.pem -nodes`
2. Crea un **usuario secundario SOL** con perfil de emisor electrónico.
3. Configura en `.env`: `SUNAT_RUC`, `SUNAT_RAZON_SOCIAL`, dirección y ubigeo, `SUNAT_SOL_USUARIO`, `SUNAT_SOL_CLAVE` y `SUNAT_ENTORNO=produccion`.

Reglas que ya valida la app:

- La factura exige cliente con RUC.
- Boletas mayores a S/ 700 exigen cliente con documento.
- No se puede vender más que el stock disponible (salvo servicios `ZZ`).
- El correlativo se reserva con bloqueo, así que no se duplica aunque dos cajas vendan a la vez.

Si SUNAT no responde, el comprobante queda en estado `error` y se puede reenviar desde su detalle.

## Pruebas

```bash
php artisan test
```

## Pendiente

- Notas de crédito/débito y comunicación de baja (anulaciones).
- Resumen diario de boletas.
- PDF con código QR para enviar al cliente.
- Consulta de RUC/DNI.
- Despliegue en servidor.
