# Desarrollo local de ZoFloridane

## Objetivo

Mantener el código versionado en GitHub separado de la instalación completa de WordPress/LocalWP, y sincronizar únicamente las piezas que modificamos.

## Entorno actual de referencia

LocalWP:

```text
https://zofloridane-local.local
```

WordPress se restauró localmente desde el backup del sitio y se verificó que el frontend original vuelve a verse correctamente al activar el stack esencial y `zfl-admin`.

## Repositorio

```text
https://github.com/LuisHdezE/ZoFloridane
```

Clonar en una carpeta de trabajo fuera de LocalWP, por ejemplo:

```bat
cd C:\Users\Luis\Projects
git clone https://github.com/LuisHdezE/ZoFloridane.git
cd ZoFloridane
```

Para actualizar:

```bat
git switch main
git pull --ff-only
```

Para probar una rama:

```bat
git fetch origin
git switch feature/ui-home-v1
git pull --ff-only
```

## Sincronización con LocalWP

La instalación local está en una ruta similar a:

```text
C:\Users\Luis\Local Sites\zofloridane\app\public
```

Antes de copiar una rama por primera vez, conservar un respaldo de las carpetas locales funcionales.

### Plugin

Origen Git:

```text
ZoFloridane\wp-content\plugins\zfl-admin
```

Destino LocalWP:

```text
C:\Users\Luis\Local Sites\zofloridane\app\public\wp-content\plugins\zfl-admin
```

### Child theme

Origen Git:

```text
ZoFloridane\wp-content\themes\electro-child
```

Destino LocalWP:

```text
C:\Users\Luis\Local Sites\zofloridane\app\public\wp-content\themes\electro-child
```

Se puede copiar desde PowerShell:

```powershell
Copy-Item -Path "C:\Users\Luis\Projects\ZoFloridane\wp-content\plugins\zfl-admin\*" `
  -Destination "C:\Users\Luis\Local Sites\zofloridane\app\public\wp-content\plugins\zfl-admin" `
  -Recurse -Force

Copy-Item -Path "C:\Users\Luis\Projects\ZoFloridane\wp-content\themes\electro-child\*" `
  -Destination "C:\Users\Luis\Local Sites\zofloridane\app\public\wp-content\themes\electro-child" `
  -Recurse -Force
```

Si una rama elimina archivos, la copia anterior no los elimina del destino. Para cambios estructurales conviene reemplazar completamente la carpeta correspondiente después de hacer respaldo.

## Después de sincronizar

Abrir el Site Shell de LocalWP y ejecutar cuando corresponda:

```bat
wp cache flush --skip-themes
```

Si se modifican reglas de reescritura/templates:

```bat
wp rewrite flush --hard --skip-themes
```

Recargar el navegador con:

```text
Ctrl + F5
```

## Plugins activos del baseline local recuperado

Durante la recuperación se confirmó un estado funcional con:

```text
advanced-custom-fields
electro-extensions
ajax-search-for-woocommerce
zfl-admin
mas-woocommerce-brands
mas-static-content
redux-framework
revslider
woocommerce
js_composer
```

No es una declaración de dependencias definitivas. En el nuevo ciclo se debe auditar qué plugins son realmente necesarios para el storefront objetivo.

## Plugins/integraciones que NO deben activarse por rutina durante trabajo UI

Hasta que una tarea necesite específicamente probarlos, evitar activar integraciones externas reales como:

- PayPal;
- Stripe;
- WooPayments;
- Telegram;
- automatizaciones que envíen mensajes/correos externos.

Mantener el clon local aislado de producción.

## Configuración local de seguridad

En el clon se recomendó:

```php
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
```

Si una prueba necesita llamadas HTTP externas, habilitarlas de forma consciente y temporal, no eliminar el aislamiento por defecto.

## Comprobaciones rápidas

### PHP

Desde Git Bash/WSL/Linux:

```bash
find wp-content -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

### JavaScript

```bash
find wp-content -type f -name '*.js' -print0 | xargs -0 -n1 node --check
```

GitHub Actions ejecuta equivalentes en cada PR.

## Flujo recomendado por cambio

1. Actualizar `main`.
2. Crear/cambiar a rama `feature/*`.
3. Modificar en Git.
4. Commit/push.
5. CI verde.
6. Copiar la rama a LocalWP.
7. Probar desktop y móvil.
8. Revisar carrito/localidad si la tarea los toca.
9. Abrir/actualizar Pull Request.
10. Merge solo después de la validación local.

## Regla de oro

El repositorio es la fuente de verdad del código personalizado. LocalWP es el laboratorio funcional. Producción no se modifica durante el desarrollo del rediseño.
