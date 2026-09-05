# ZoFloridane Roadmap

## Fase 0 — Baseline y seguridad

- [x] Repositorio público creado.
- [x] Código personalizado versionado.
- [x] README y target UI/UX documentados.
- [x] Flujo LocalWP documentado.
- [x] Handoff para nuevo chat creado.
- [x] GitHub Actions básico configurado.
- [ ] Confirmar primera ejecución verde del CI.

## Fase 1 — Home UI v1

Rama prevista:

```text
feature/ui-home-v1
```

### Alcance

- [ ] limpiar demo visible de Electro en Home;
- [ ] top bar;
- [ ] header nuevo;
- [ ] búsqueda;
- [ ] localidad visible;
- [ ] cuenta + carrito;
- [ ] navegación real;
- [ ] hero rotatorio;
- [ ] promos laterales;
- [ ] categorías visuales;
- [ ] Los más vendidos;
- [ ] bloque de confianza;
- [ ] Cómo comprar;
- [ ] footer propio;
- [ ] Base Negra;
- [ ] Base Verde;
- [ ] persistencia del tema;
- [ ] responsive desktop/tablet/móvil;
- [ ] prueba manual de carrito/localidad.

## Fase 2 — Catálogo y producto

- [ ] página de tienda;
- [ ] categorías;
- [ ] filtros solo si aportan valor;
- [ ] tarjeta de producto canónica;
- [ ] producto individual;
- [ ] estados de stock/disponibilidad;
- [ ] consistencia con ambos temas.

## Fase 3 — Carrito y checkout

- [ ] carrito simplificado;
- [ ] totales claros;
- [ ] entrega;
- [ ] checkout por pasos lógicos;
- [ ] comprador en EE. UU.;
- [ ] receptor en Cuba;
- [ ] localidad/dirección/recogida;
- [ ] revisión del pedido;
- [ ] UX Zelle;
- [ ] comprobante/confirmación según lógica existente;
- [ ] estados vacíos y errores en español.

## Fase 4 — Cuenta y soporte

- [ ] Mi cuenta;
- [ ] pedidos;
- [ ] seguimiento;
- [ ] WhatsApp;
- [ ] FAQs;
- [ ] favoritos si se mantienen en alcance.

## Fase 5 — Calidad

- [ ] responsive fino;
- [ ] accesibilidad;
- [ ] performance;
- [ ] SEO técnico;
- [ ] limpieza de dependencias/plugins innecesarios;
- [ ] auditoría de textos y marca `ZoFloridane`;
- [ ] pruebas visuales finales;
- [ ] staging;
- [ ] plan de despliegue a producción.

## Regla de avance

No mezclar fases grandes en una sola entrega. Cada slice debe:

1. tener alcance identificable;
2. pasar CI;
3. poder probarse en LocalWP;
4. preservar negocio existente;
5. quedar aprobado antes del merge cuando afecte flujo de compra.
