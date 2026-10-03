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
- Node.js 20+ solo si vas a modificar el diseño (los estilos compilados ya vienen en `public/build`)

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

El seeder (`php artisan migrate --seed`) ya carga los **566 productos** de `database/data/productos_mypefact.xlsx` (exportados de MYPEFACT). Ese reporte no trae stock, así que todos empiezan en 0.

Para reimportar o cargar otro archivo: `php artisan productos:importar ruta/al/archivo.xlsx` o desde la pantalla de Productos.

En **Productos → Importar productos desde Excel o CSV** sube un `.xlsx` o `.csv` con encabezados (separador `,` o `;`). El reporte de productos de MYPEFACT se acepta tal cual.

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

## Notas de crédito y anulaciones

- Desde el detalle de una factura o boleta **aceptada por SUNAT** se emite una **nota de crédito** (series `FC01` para facturas y `BC01` para boletas) con motivo 01 anulación, 06 devolución total o 07 devolución por ítem. Los productos vuelven al stock, el dinero sale de la caja abierta y los reportes restan la nota.
- Los **recibos internos** se anulan directamente (no van a SUNAT), devolviendo stock y dinero.

## Impresión y envío al cliente

Cada comprobante tiene **PDF A4** y **ticket de 80 mm**, con el código QR de SUNAT, el hash, el importe en letras y la leyenda de representación impresa. El botón **WhatsApp** abre un mensaje con un enlace firmado al PDF; el cliente puede verlo sin iniciar sesión, pero el enlace no se puede adivinar ni modificar. Para poner tu logo, usa `SUNAT_LOGO=/ruta/al/logo.png`.

## Reportes

- **Dashboard**: CPE emitidos, monto en comprobantes y en recibos, total general, utilidad, ventas por hora o por día, ventas por método de pago, productos más vendidos, stock bajo y comprobantes pendientes en SUNAT. Se puede filtrar por hoy, ayer, semana, mes, mes anterior o un rango de fechas.
- **Reportes**: lo mismo con detalle por tipo de comprobante y por producto, inventario valorizado y exportación de ventas a CSV (se abre en Excel).
- La utilidad usa el **costo de compra** de cada producto (sin IGV). Los productos sin costo registrado no suman a la utilidad.

## Diseño

Tailwind CSS, Alpine.js y Chart.js se compilan con Vite. Si cambias vistas o estilos, ejecuta `npm install && npm run build` y sube `public/build`.

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

- Notas de débito y comunicación de baja.
- Resumen diario de boletas.
- Consulta de RUC/DNI.
- Despliegue en servidor.
