# Kaixa · Sistema contable para barberías

Aplicación web (Laravel 12 + Livewire) para llevar la caja diaria de una o varias barberías.
Funciona en computador y en el teléfono: el diseño se adapta solo a la pantalla, y desde el
teléfono se puede "Agregar a la pantalla de inicio" para abrirla como una app.

## Qué hace

- **Caja del día**: cobrar servicios y productos en efectivo, Nequi/transferencia o pago combinado.
- **Historial de ventas**: editar o eliminar ventas pidiendo motivo y contraseña; cada cambio queda auditado.
- **Inventario**: insumos y productos físicos para la venta, con aviso de stock bajo. Al vender se descuenta solo.
  Los cortes y demás servicios no van aquí sino en **Servicios**.
- **Gastos** del negocio por categoría.
- **Barberos** con su porcentaje de comisión. La comisión se calcula **solo sobre servicios**, nunca sobre productos.
- **Reportes** mensuales con ingresos, gastos, comisiones y ganancia, descargables en Excel y PDF.
- **Panel de administrador** (`/admin`) para crear barberías, sus usuarios, registrar sus pagos y recargas.
  No hay registro público: las cuentas las crea el administrador.

## Planes de Kaixa

| Plan | Precio | Notas |
|---|---|---|
| Mensual | $130.000 al mes | Contrato mínimo de 6 meses |
| Semestral | $700.000 | Ahorra $80.000 (10%) frente al mensual |
| Anual | $1.200.000 | Ahorra $360.000 (23%) frente al mensual |
| Por uso | 2,5% de cada venta | Saldo prepagado que se recarga: la primera recarga es de mínimo $200.000. Contrato mínimo de 6 meses; al terminar, el saldo a favor se devuelve |

La instalación y entrega del sistema cuesta $65.000, en un solo pago, para cualquier plan.

- El administrador registra cada pago o recarga en `/admin`. Cada uno se anota solo en los **Gastos** de la barbería (categoría "Suscripción Kaixa").
- Cuando un plan vence hay **5 días de gracia**: al iniciar sesión sale un aviso de no pago (se puede cerrar) y arriba de la pantalla
  siempre se ve la fecha de vencimiento. Pasada la gracia, o si el saldo del plan por uso llega a $0, el sistema se deshabilita hasta que se pague.
- La barbería ve su estado, los planes y su historial de pagos en **Mi plan Kaixa** (`/mi-plan`).
- Los precios, el porcentaje y los días de gracia se cambian en `config/suscripcion.php`.

## Requisitos

- PHP 8.2 o superior (con las extensiones `sqlite3`, `mbstring`, `gd` y `zip`)
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) 20 o superior

## Instalarlo en tu computador

Abre la carpeta `barberos` en una terminal (en VS Code: menú **Terminal → New Terminal**, y luego `cd barberos`) y ejecuta:

```bash
composer install
npm install
cp .env.example .env          # en Windows (PowerShell): copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Abre http://127.0.0.1:8000 en el navegador.

> **Si `php artisan serve` no arranca** (por ejemplo en Windows con Laravel Herd sale "Failed to listen"),
> usa en su lugar:
>
> ```bash
> php -S 127.0.0.1:8000 -t public server.php
> ```
>
> El `server.php` del final es importante: sin él los botones y filtros no responden.

Al ejecutar `php artisan migrate --seed` la terminal muestra una tabla con el correo y la contraseña
del administrador y de una barbería de prueba. Si prefieres elegirlas tú, escríbelas antes en el
archivo `.env` (`SEED_ADMIN_PASSWORD` y `SEED_DEMO_PASSWORD`).

> Si ya tenías el proyecto instalado, después de traer cambios nuevos ejecuta
> `composer install`, `npm install`, `php artisan migrate` y `npm run build`.

## Verlo en tu teléfono (misma red WiFi)

1. Inicia el servidor así: `php artisan serve --host=0.0.0.0 --port=8000`
   (o, si ese comando no te funciona: `php -S 0.0.0.0:8000 -t public server.php`)
2. Busca la IP de tu computador (en Windows: `ipconfig`, en Mac: `ipconfig getifaddr en0`), por ejemplo `192.168.1.20`.
3. En el teléfono abre `http://192.168.1.20:8000`.

Para que los clientes lo usen desde cualquier lugar hay que publicarlo en un servidor (hosting) con dominio y HTTPS.
Los pasos están en [DESPLIEGUE.md](DESPLIEGUE.md).

## Pruebas automáticas

```bash
php artisan test
```

Revisan, entre otras cosas, que la comisión no se cobre sobre productos, que una barbería no pueda usar
datos de otra, que el stock no quede inconsistente y que solo el administrador entre a `/admin`.

## Configuración útil (`.env`)

| Variable | Para qué sirve | Valor por defecto |
|---|---|---|
| `APP_TIMEZONE` | Zona horaria de las ventas y reportes | `America/Bogota` |
| `APP_LOCALE` | Idioma de fechas y meses | `es` |
| `DB_CONNECTION` | Base de datos (`sqlite` o `mysql`) | `sqlite` |
| `KAIXA_WHATSAPP` | WhatsApp de soporte para que las barberías paguen (con indicativo, ej. `573001234567`) | vacío |
