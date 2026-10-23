# Publicar Kaixa en internet

Guía para subir Kaixa a un servidor con dominio propio y HTTPS, de forma que las barberías
entren desde cualquier lugar (computador o teléfono).

## Qué hosting usar

Kaixa guarda dos cosas en el disco del servidor: la base de datos SQLite
(`database/database.sqlite`) y los logos de las barberías (`storage/app/private/logos`).
Por eso necesita un servidor con **disco persistente**. Los servicios "serverless" donde el disco
se borra en cada despliegue (Laravel Cloud, Railway, Render sin disco pagado, etc.) obligarían a
cambiar a MySQL/Postgres y a otro sistema de archivos, y salen más caros.

También necesita poder actualizar PHP apenas salga un parche de seguridad (PHP 8.3.36+, 8.4.27+ o
8.5.12+). En un hosting compartido eso depende del proveedor; en un VPS lo haces tú en un minuto.

| Opción | Costo aproximado | A favor | En contra |
|---|---|---|---|
| **VPS Hostinger KVM 1 + CloudPanel** (recomendada) | ≈ US$6–8/mes pagando 12–24 meses; la renovación sube a ≈ US$11–13/mes | 1 vCPU, 4 GB RAM, 50 GB NVMe. CloudPanel es gratis, trae plantilla para Laravel, SSL gratis, varias versiones de PHP y copias de seguridad. Pago con tarjeta o PSE desde Colombia | Hay que seguir esta guía una vez; el servidor es tuyo para mantener |
| VPS DigitalOcean / Vultr / Hetzner + CloudPanel | US$5–7/mes, precio estable | Igual que arriba; pago por hora | Cobro en dólares con tarjeta internacional |
| VPS + Laravel Forge o Ploi (panel de pago) | VPS + US$10–19/mes del panel | Despliegue con un botón desde GitHub | Duplica el costo mensual |
| Hosting compartido (Hostinger Business, etc.) | US$3–5/mes | Lo más barato | No controlas cuándo se parchea PHP; Composer y Node limitados |

Precios de referencia a octubre de 2026; confírmalos en la página del proveedor al comprar.
Con 3 o más barberías pagando el plan mensual, el VPS se paga solo con mucha holgura.

**Dominio:** un `.com` cuesta ≈ US$10–15/año (Hostinger, Namecheap, Cloudflare). Un `.co` o `.com.co`
cuesta más (≈ US$25–40/año). Ejemplo: `kaixa.co` o `app.kaixa.com`.

## Paso 1 · Comprar el VPS y el dominio

1. Compra el VPS (KVM 1 o superior). Ubicación: la más cercana a Colombia que ofrezca
   (Estados Unidos o São Paulo).
2. Como sistema operativo elige **Ubuntu 24.04 con CloudPanel** (Hostinger lo ofrece como plantilla).
   En otro proveedor instala Ubuntu 24.04 y sigue https://www.cloudpanel.io/docs/v2/getting-started/
3. Compra el dominio y crea en su DNS un registro **A** que apunte a la IP del VPS
   (por ejemplo `app` → `123.45.67.89` si usarás `app.tudominio.com`).

## Paso 2 · Crear el sitio en CloudPanel

1. Entra a `https://IP-DEL-VPS:8443` y crea el usuario administrador de CloudPanel.
2. **Sites → Add Site → Create a PHP Site**.
   - Application: **Laravel 12**
   - Domain: `app.tudominio.com`
   - PHP Version: la **8.4** (o la más nueva que muestre)
   - Site User y contraseña: anótalos, con ellos entrarás por SSH.
3. En el sitio, pestaña **Settings**, cambia *Root Directory* a `htdocs/app.tudominio.com/barberos/public`
   (el código de Laravel está dentro de la carpeta `barberos` del repositorio).
4. Pestaña **SSL/TLS → Actions → New Let's Encrypt Certificate**. Esto activa HTTPS.

## Paso 3 · Subir el código

Entra por SSH con el usuario del sitio (en Windows sirve PowerShell):

```bash
ssh usuario-del-sitio@IP-DEL-VPS
cd ~/htdocs/app.tudominio.com
rm -rf ./*            # CloudPanel deja una página de ejemplo
```

El repositorio es privado, así que el servidor necesita permiso para leerlo:

```bash
ssh-keygen -t ed25519 -C "servidor-kaixa" -N "" -f ~/.ssh/id_ed25519
cat ~/.ssh/id_ed25519.pub
```

Copia esa línea en GitHub: repositorio → **Settings → Deploy keys → Add deploy key**
(solo lectura). Luego:

```bash
git clone git@github.com:FelipeBenavides-tech/Sistema-contable.git .
cd barberos
```

Instala Node para compilar los estilos (una sola vez):

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
source ~/.bashrc
nvm install 22
```

## Paso 4 · Configurar `.env` de producción

```bash
cp .env.example .env
nano .env
```

Cambia estas líneas (las demás pueden quedar igual):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.tudominio.com
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true

SEED_ADMIN_EMAIL=tu-correo@dominio.com
SEED_ADMIN_PASSWORD=una-contraseña-larga-y-única
KAIXA_WHATSAPP=573001234567
```

`APP_DEBUG=false` es obligatorio: con `true` cualquier error muestra datos internos del sistema.

## Paso 5 · Instalar

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force      # crea el administrador con los datos del .env y una barbería de prueba
npm ci && npm run build
php artisan optimize
```

Abre `https://app.tudominio.com`, entra con el administrador y crea la primera barbería desde `/admin`.
La "Barbería El Estilo" que crea el seeder es de prueba: desactívala desde `/admin` cuando ya no la uses.

> Guarda una copia del `APP_KEY` del `.env` en un lugar seguro. Si se pierde, las sesiones y datos
> cifrados dejan de funcionar.

## Paso 6 · Copias de seguridad

Toda la información de las barberías está en `database/database.sqlite` y `storage/app`.
En CloudPanel: pestaña **Cron Jobs** del sitio → agrega uno diario (por ejemplo a las 3:00):

```bash
cd ~/htdocs/app.tudominio.com/barberos && mkdir -p ~/backups && sqlite3 database/database.sqlite ".backup '/home/USUARIO/backups/kaixa-$(date +\%F).sqlite'" && tar czf ~/backups/logos-$(date +\%F).tgz storage/app && find ~/backups -mtime +30 -delete
```

(Si `sqlite3` no está instalado: como root, `apt install sqlite3`.)
Además activa las copias automáticas del VPS en el panel del proveedor, y de vez en cuando descarga
una copia a tu computador.

## Actualizar el sistema cuando haya cambios

Después de unir (merge) cambios en GitHub:

```bash
cd ~/htdocs/app.tudominio.com/barberos
./deploy.sh
```

El script `deploy.sh` pone el sitio en mantenimiento, trae el código, instala dependencias,
migra la base de datos, compila los estilos y lo vuelve a abrir.

## Mantener PHP al día

Cuando salga un parche de seguridad de PHP, como root:

```bash
apt update && apt upgrade -y
```

CloudPanel instala PHP desde paquetes que se actualizan con ese comando. Revisa la versión con
`php -v`. Para pasar a otra versión menor (por ejemplo 8.4 → 8.5) cámbiala en **Settings → PHP Version**
del sitio.
