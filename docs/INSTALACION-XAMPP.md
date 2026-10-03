# Probar la plataforma en tu PC con XAMPP (Windows)

## 1. Revisa la versión de PHP

La plataforma necesita **PHP 8.3 o superior**. Abre el panel de XAMPP, pulsa **Shell** y escribe:

```
php -v
```

Si dice 8.2 o menos, instala una versión de XAMPP que traiga PHP 8.3+ (apachefriends.org) o usa **Laragon**, que también es gratis y permite cambiar de versión de PHP.

## 2. Activa las extensiones de PHP

En el panel de XAMPP: **Apache → Config → PHP (php.ini)**. Busca estas líneas y quítales el `;` del inicio:

```
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=soap
extension=sqlite3
extension=zip
```

Guarda el archivo y reinicia Apache. `soap` es la que se usa para hablar con SUNAT.

## 3. Instala Composer y Git

- Composer: https://getcomposer.org/download (el instalador te pide la ruta de PHP: `C:\xampp\php\php.exe`).
- Git: https://git-scm.com/download/win. También sirve GitHub Desktop.

## 4. Descarga el proyecto

En una terminal (PowerShell o la Shell de XAMPP):

```
cd C:\xampp\htdocs
git clone https://github.com/jul1us5-1/plataforma-stock-sunat.git
cd plataforma-stock-sunat
composer install
copy .env.example .env
php artisan key:generate
```

Como el repositorio es privado, la primera vez Git te pedirá iniciar sesión con tu cuenta de GitHub.

## 5. Base de datos

**Opción fácil (SQLite, sin configurar nada):**

```
type nul > database\database.sqlite
```

**Opción MySQL de XAMPP:** inicia MySQL en el panel, entra a http://localhost/phpmyadmin, crea una base llamada `plataforma_stock` (cotejamiento `utf8mb4_unicode_ci`) y cambia estas líneas en `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plataforma_stock
DB_USERNAME=root
DB_PASSWORD=
```

## 6. Usuario administrador y datos iniciales

En `.env`, cambia `ADMIN_EMAIL` y `ADMIN_PASSWORD` por tu correo y una clave tuya. Luego:

```
php artisan migrate --seed
```

Eso crea las tablas, tu usuario, las series (F001, B001, R001, FC01, BC01) y carga tus 566 productos.

## 7. Abre la plataforma

```
php artisan serve
```

Entra a http://localhost:8000 con el correo y la clave del paso 6. Deja esa ventana abierta mientras la usas.

## 8. Probar el envío a SUNAT (modo beta)

El `.env` viene en `SUNAT_ENTORNO=beta`: los comprobantes van al servidor de pruebas de SUNAT, **no tienen validez** y no afectan tu RUC. Para firmarlos se necesita un certificado; para pruebas basta uno generado así:

```
php artisan sunat:certificado-prueba
```

Ahora emite una boleta. En su detalle debería salir **Aceptado** y podrás descargar el XML y el CDR. Si sale "Error", el mensaje dice el motivo.

## 9. Lo que NO hay que hacer en tu PC

- No pongas `SUNAT_ENTORNO=produccion` ni tu usuario SOL en la PC de pruebas. Eso se configura en el servidor real, con tu certificado digital y un usuario SOL secundario.
- No subas el archivo `.env` a GitHub (ya está excluido).
