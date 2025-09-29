## API Cotizaciones USD → ARS (Laravel)

Proyecto Laravel que consume una API pública de dólar, convierte USD→ARS y registra historial para promedios mensuales y series por mes. Incluye caché, validaciones, rate limiting y despliegue en Koyeb con MySQL gestionado en Aiven.

## Características

GET /api/convertir: convierte USD→ARS usando la última cotización y guarda el punto en BD.

GET /api/promedio-mensual: promedio mensual (compra/venta) por tipo de dólar.

GET /api/historial: lista cronológica del mes para graficar/analizar.

Histórico: tabla cotizaciones con tipo, momento, fecha, compra, venta.

Caché: reduce llamadas a la API externa (p. ej., 60s).

Rate limit: throttle:30,1 en endpoints públicos.

## Stack

Laravel 12 (HTTP Client, Validation)

MySQL 8+ (Aiven)

PHP 8.2+

Postman (colección de ejemplo)

## Flujo (alto nivel)

Cliente → GET /api/convertir?valor=...&tipo=...

Controlador: llama API externa (DOLAR_API_URL/tipo), aplica caché y valida.

Guarda registro en cotizaciones (tipo, momento, fecha, compra, venta).

Responde JSON con la cotización usada y el resultado.

Consultas de promedios/series usan whereYear/whereMonth e índices por fecha/tipo.

## Variables de entorno (producción en Koyeb)

Definí estas Environment Variables en tu servicio de Koyeb:

APP_ENV=production
APP_DEBUG=false
APP_URL=https://<tu-servicio>.koyeb.app
APP_KEY=base64:<tu-app-key>   # Generar con: php artisan key:generate --show

# API externa
DOLAR_API_URL=https://dolarapi.com/v1/dolares  # ej. ajustá según tu fuente

# Base de datos (Aiven)
DB_CONNECTION=mysql
DB_HOST=<tu-host>.aivencloud.com
DB_PORT=<tu-puerto>
DB_DATABASE=<tu_db>
DB_USERNAME=<tu_usuario>
DB_PASSWORD=<tu_password>

# SSL Aiven (opcional, recomendado si verificás certificado):
# MYSQL_ATTR_SSL_CA=/app/ca.pem

# Nota sobre SSL (Aiven)

Rápido para pruebas: podés conectar sin verificar CA.

Recomendado: descargar el CA de Aiven y exponerlo como archivo en el contenedor (secret/file) y setear MYSQL_ATTR_SSL_CA.

## Pasos en Koyeb

Create Web Service → conecta GitHub → seleccioná tu repo.

Build: usar el Dockerfile.

Cargá las ENV de arriba (APP_KEY incluida).

Deploy.

Probar: curl https://<tu-servicio>.koyeb.app/api/convertir?valor=100&tipo=blue

No hace falta correr migraciones en Koyeb si ya las ejecutaste desde tu PC apuntando a Aiven. Si preferís, podés correrlas localmente:

php artisan migrate --force
php artisan db:seed --force
# Si querés un seeder puntual:
php artisan db:seed --class=CotizacionSeeder


## Puesta en marcha local

cp .env.example .env
php artisan key:generate
composer install
# Configura DB_* si usarás Aiven desde local
php artisan migrate
php artisan serve

## Endpoints ejemplo

- `GET /api/convertir?valor=100&tipo=blue`

- `GET /api/promedio-mensual?tipo=blue&anio=2025&mes=9&valor=venta`  
  Devuelve solo el promedio de **venta** (o `compra` si lo indicás).

- `GET /api/promedio-mensual?tipo=blue&anio=2025&mes=9`  
  Si **no** enviás `valor`, devuelve **ambos** promedios: `compra` y `venta`.

- `GET /api/historial?tipo=blue&anio=2025&mes=9`

## Troubleshooting

500 / “No application encryption key” → falta APP_KEY en Koyeb.

SQLSTATE[HY000] [1045] → usuario/clave/DB mal o IP no permitida en Aiven.

Timeout a la API externa → subí el timeout del HTTP Client y valida errores.

CORS bloquea → ajustá config/cors.php.

SSL Aiven → si activaste verificación, cargá el CA y MYSQL_ATTR_SSL_CA.