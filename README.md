# ZoFloridane

Repositorio de desarrollo para la modernización UI/UX de **ZoFloridane**, una tienda WordPress + WooCommerce orientada a compras desde EE. UU. y entrega a familiares en Cuba.

> **Marca:** escribir siempre **ZoFloridane**, con `F` mayúscula.

## Objetivo actual

Evolucionar la interfaz existente hacia una experiencia de compra moderna, confiable, simple y mobile-first, preservando la lógica comercial ya funcional: WooCommerce, selección de localidad, disponibilidad de productos, carrito, cuenta, entrega y pago mediante Zelle.

La referencia funcional y visual está documentada en [`docs/UI-UX-TARGET.md`](docs/UI-UX-TARGET.md).

## Estado del repositorio

El repositorio contiene únicamente el código que necesitamos versionar para el rediseño:

```text
ZoFloridane/
├── wp-content/
│   ├── plugins/
│   │   └── zfl-admin/
│   └── themes/
│       └── electro-child/
├── docs/
│   ├── screenshots/
│   ├── UI-UX-TARGET.md
│   ├── LOCAL-DEVELOPMENT.md
│   └── HANDOFF_2026-09-05.md
├── .github/workflows/ci.yml
├── .gitignore
└── README.md
```

No se versionan WordPress completo, base de datos, `uploads`, backups, credenciales, logs ni datos reales de clientes/pedidos.

## Arquitectura encontrada

La inspección del sitio restaurado localmente confirmó que el frontend real no depende principalmente del contenido de WPBakery. La portada `home` tiene `post_content` vacío y usa una plantilla registrada dinámicamente por el plugin personalizado `zfl-admin`.

`zfl-admin` es por tanto una pieza estructural del storefront. Entre otras responsabilidades, registra/carga plantillas personalizadas para Home, producto y carrito, y contiene lógica asociada a catálogo, promociones, monedas, pedidos, visitas y Zelle.

`electro-child` es actualmente muy ligero y sirve como child theme de Electro.

## Principios del rediseño

- Mantener intacta la lógica comercial existente salvo que una tarea indique lo contrario.
- Eliminar cualquier contenido heredado del demo de Electro visible al cliente.
- Priorizar claridad, confianza y conversión.
- Diseñar mobile-first.
- Mantener una sola estructura visual con dos skins intercambiables:
  - **Base Negra**
  - **Base Verde**
- No duplicar páginas ni lógica para implementar los temas: deben usar variables/tokens de diseño compartidos.
- Zelle, localidad, carrito y entrega deben ser elementos de confianza visibles, no funciones enterradas.

## Flujo de trabajo Git

`main` representa el baseline estable del código versionado.

Los cambios funcionales deben hacerse en ramas cortas, por ejemplo:

```text
feature/ui-home-v1
feature/product-page
feature/cart-checkout
feature/location-ux
feature/mobile-polish
fix/<descripcion>
chore/<descripcion>
```

Flujo esperado:

```text
main
  ↓
feature/*
  ↓
GitHub Actions
  ↓
Pull Request
  ↓
prueba en LocalWP
  ↓
merge aprobado
```

No se debe desplegar directamente a producción desde una rama de trabajo.

## Desarrollo local

El clon local usado durante la recuperación funciona en LocalWP con el dominio:

```text
https://zofloridane-local.local
```

Las instrucciones para sincronizar este repositorio con LocalWP están en [`docs/LOCAL-DEVELOPMENT.md`](docs/LOCAL-DEVELOPMENT.md).

## CI

GitHub Actions ejecuta validaciones básicas sobre cada Pull Request y push relevante:

- sintaxis PHP;
- sintaxis JavaScript.

El CI está pensado como red de seguridad rápida. No sustituye las pruebas funcionales en WordPress/WooCommerce local.

## Seguridad y datos

Nunca subir al repositorio:

- `wp-config.php`;
- archivos `.wpress`;
- dumps `.sql`;
- `wp-content/uploads/`;
- cachés;
- secretos/API keys;
- credenciales de producción;
- datos personales de clientes;
- exports de pedidos.

El sitio local debe mantenerse aislado de integraciones externas reales durante el desarrollo.

## Prioridades

### P0

1. Eliminar contenido demo y datos falsos.
2. Nuevo header, navegación y footer.
3. Home alineado al target visual.
4. Hero/banner rotatorio.
5. Base Negra + Base Verde.
6. Carrito y checkout simplificados.

### P1

1. Catálogo y categorías.
2. Nueva tarjeta de producto.
3. Página individual de producto.
4. UX del selector de localidad.

### P2

1. Seguimiento de pedidos.
2. WhatsApp.
3. Favoritos.
4. Refinamiento responsive y accesibilidad.

### P3

1. SEO técnico.
2. Performance.
3. Microinteracciones.

## Punto de continuación

Antes de iniciar una nueva sesión de desarrollo, leer:

1. [`docs/HANDOFF_2026-09-05.md`](docs/HANDOFF_2026-09-05.md)
2. [`docs/UI-UX-TARGET.md`](docs/UI-UX-TARGET.md)
3. este `README.md`

El siguiente bloque recomendado es **Home UI v1**, implementado en una rama `feature/ui-home-v1` y validado primero en LocalWP.
