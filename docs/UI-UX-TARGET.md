# ZoFloridane UI/UX Target v1

## 1. Propósito

Este documento define el objetivo visual y funcional del rediseño de **ZoFloridane**.

La meta no es simplemente cambiar colores. El storefront debe dejar de sentirse como una instalación genérica de Electro y convertirse en una tienda propia, terminada, confiable y fácil de comprar, especialmente desde móvil.

La lógica comercial existente debe conservarse salvo indicación expresa:

- selección de localidad;
- productos según disponibilidad/destino;
- WooCommerce;
- carrito;
- cuenta;
- entrega;
- Zelle;
- promociones;
- administración propia de `zfl-admin`.

## 2. Problemas detectados en el sitio original

### P0 — contaminación del demo

Eliminar del frontend público cualquier rastro heredado de Electro que no pertenezca a ZoFloridane, incluyendo cuando aplique:

- categorías como `Laptops & Computers`, `Cameras & Photography`, `Video Games & Consoles`, `PC Gaming Headsets` y equivalentes;
- carruseles o marcas demo como Acer, Apple, Asus, Dell, Lenovo;
- páginas/demo `Inicio v1...Inicio v9` si son visibles o navegables sin propósito;
- textos sin traducir;
- copyright de Electro;
- datos de contacto ficticios;
- `17 Princess Road, London`;
- teléfonos genéricos;
- bloques, menús o enlaces heredados del template.

### P0 — confianza

Para una tienda donde el cliente realiza pagos mediante Zelle, la interfaz debe comunicar identidad real, proceso de compra y soporte con máxima claridad.

No deben existir datos contradictorios, placeholders ni textos demo visibles.

### P0 — carrito y checkout

Eliminar textos y bloques genéricos en inglés como:

- `Your cart is currently empty!`;
- `New in store`;
- restos de marcas/demo;
- elementos WooCommerce que no aporten al flujo real.

## 3. Identidad

### Nombre

Usar siempre:

**ZoFloridane**

La `F` es mayúscula en toda interfaz, documentación, banner, título y copy visible.

### Personalidad visual

La tienda debe sentirse:

- moderna;
- directa;
- familiar;
- confiable;
- cálida sin perder claridad comercial;
- específica para su negocio, no como marketplace genérico.

## 4. Sistema de temas

Debe existir una sola arquitectura de componentes y dos skins visuales intercambiables.

### Tema A — Base Negra

- fondo principal negro/carbón;
- superficies oscuras diferenciadas;
- texto principal blanco;
- texto secundario gris claro;
- verde ZoFloridane como acento;
- contraste alto;
- estética premium.

### Tema B — Base Verde

- fondo principal verde profundo/forestal;
- superficies verdes diferenciadas;
- texto claro de alto contraste;
- acentos verdes más luminosos/menta cuando convenga;
- misma jerarquía y estructura que Base Negra.

### Reglas

- el cambio de tema no debe duplicar templates;
- implementar con tokens/variables CSS compartidas;
- la elección debe persistir en el navegador;
- debe funcionar en desktop y móvil;
- los componentes mantienen dimensiones, layout y comportamiento al cambiar de tema;
- solo cambian propiedades visuales compatibles: fondos, texto, bordes, sombras, acentos y estados.

## 5. Home objetivo

La referencia principal es el mockup aprobado y los recursos guardados en `docs/screenshots/`.

### 5.1 Top bar

Franja superior compacta con tres mensajes principales:

- `Entrega en Florida, Camagüey`;
- `Pago con Zelle`;
- `Atención por WhatsApp`.

En móvil puede reducirse o simplificarse, pero nunca debe competir con las acciones principales.

### 5.2 Header

Jerarquía desktop:

```text
Logo ZoFloridane | Buscar productos | Localidad | Mi cuenta | Carrito
```

Requisitos:

- logo claramente visible;
- buscador protagonista;
- selector de localidad legible;
- acceso a cuenta;
- carrito con contador;
- sin megamenú demo;
- sin enlaces irrelevantes.

En móvil priorizar:

```text
Logo + localidad + búsqueda + carrito
```

### 5.3 Navegación

Menú corto basado en categorías reales, por ejemplo:

- Alimentos;
- Bebidas;
- Aseo;
- Higiene;
- Perfumería;
- Electrodomésticos;
- Ofertas.

La lista final debe derivar de categorías reales de ZoFloridane, no del demo.

### 5.4 Hero / banner rotatorio

Debe ser el principal foco visual de la Home.

Características:

- carrusel grande tipo hero;
- imágenes rotatorias administrables;
- flechas anterior/siguiente;
- indicadores/puntos;
- swipe táctil;
- auto-rotación razonable;
- CTA visible;
- overlay de texto responsive;
- no incrustar textos críticos dentro de la imagen si pueden ser HTML.

Mensaje base recomendado:

> **Compra desde EE. UU. y entrégalo en Cuba**

Apoyo:

> Productos seleccionados para Florida, Camagüey.

CTA:

> **Ver productos**

El sistema de promociones existente de `zfl-admin` debe reutilizarse cuando sea viable en lugar de crear una administración paralela.

### 5.5 Promos laterales del hero

En desktop, acompañar el hero con bloques de confianza/promoción como:

**Pago seguro con Zelle**  
Rápido, fácil y confiable.

**Entrega garantizada en Florida, Camagüey**  
Tu familia recibe lo mejor, con puntualidad y cuidado.

En móvil estos bloques pueden apilarse.

### 5.6 Categorías visuales

Título:

> **Explora por categoría**

Mostrar tarjetas grandes, táctiles y limpias.

Categorías sugeridas:

- Alimentos;
- Bebidas;
- Aseo;
- Higiene;
- Perfumería;
- Hogar y cocina cuando aplique;
- Electrodomésticos;
- Ofertas;
- Ver todo.

Cada tarjeta debe priorizar imagen/icono + nombre. Evitar ruido adicional.

### 5.7 Productos destacados / más vendidos

Título principal recomendado:

> **Los más vendidos**

Mostrar aproximadamente 8 productos en desktop y un patrón responsive razonable en tablet/móvil.

Cada tarjeta debe incluir únicamente lo necesario:

- fotografía uniforme;
- nombre;
- precio;
- disponibilidad cuando aporte valor;
- botón `Añadir al carrito`.

Eliminar de la tarjeta si no aportan al negocio:

- `Compare`;
- estrellas vacías/falsas;
- SKU visible;
- metadatos técnicos innecesarios.

La Home no debe convertirse en un catálogo infinito. Mantener una selección breve, aproximadamente 8–12 productos por bloque destacado.

### 5.8 Bloque de confianza

Cuatro beneficios visibles:

1. **Pagos seguros** — verificación de transferencia/Zelle.
2. **Foto de tu entrega** — evidencia cuando aplique.
3. **Entregas en Cuba** — claridad sobre destino/servicio.
4. **Atención personalizada** — WhatsApp.

### 5.9 Cómo comprar

Proceso en cuatro pasos:

1. **Elige tus productos**.
2. **Indica quién recibe**.
3. **Paga con Zelle**.
4. **Coordinamos la entrega**.

Debe ser comprensible sin necesidad de abrir FAQs.

### 5.10 CTA emocional

Copy sugerido:

> **Tú compras desde EE. UU.**  
> **Nosotros lo ponemos en sus manos en Cuba.**

No abusar del mensaje emocional; debe apoyar la conversión, no desplazar el catálogo.

### 5.11 Footer

Footer mínimo y propio de ZoFloridane.

Contenido recomendado:

- logo / marca;
- Cómo comprar;
- Seguimiento;
- Preguntas frecuentes;
- Contacto / WhatsApp;
- Privacidad;
- Términos;
- Pago: Zelle;
- redes sociales reales si existen.

Eliminar definitivamente datos demo, Londres, teléfonos ficticios y copyright de Electro.

## 6. Selector de localidad

El selector de localidad es una capacidad central del negocio y debe tratarse como componente principal de UX.

Experiencia deseada:

> **¿Dónde quieres que entreguemos?**  
> 📍 Florida, Camagüey  
> `[Continuar]`

Después de elegir destino:

- el catálogo debe reflejar disponibilidad correspondiente;
- el usuario debe ver claramente la localidad activa;
- cambiar la localidad debe ser sencillo;
- no esconder este estado en menús secundarios.

## 7. Página individual de producto

Objetivo: eliminar el aspecto WooCommerce/demo y concentrarse en compra.

Debe incluir:

- imagen grande;
- nombre;
- precio;
- stock/disponibilidad;
- descripción breve;
- cantidad;
- CTA dominante `Añadir al carrito`;
- localidad/destino cuando sea relevante;
- información corta de entrega/confianza.

Eliminar o esconder si no aportan:

- `Uncategorized`;
- compare;
- reviews demo o vacías;
- formularios en inglés;
- metadatos técnicos irrelevantes.

## 8. Carrito

Estructura objetivo:

```text
Tu pedido

Producto × cantidad
Subtotal
Entrega
Total

Continuar comprando        CONTINUAR AL PAGO
```

Requisitos:

- edición sencilla de cantidad;
- eliminación clara;
- totales inequívocos;
- CTA primario visible;
- sin bloques promocionales demo;
- textos completamente en español.

## 9. Checkout

El checkout debe explicar el proceso paso a paso para que el usuario nunca se pregunte qué hacer después.

Flujo objetivo:

1. **Datos del comprador en EE. UU.**
2. **Datos de quien recibe en Cuba**
3. **Dirección / recogida / localidad**
4. **Revisión del pedido**
5. **Instrucciones de Zelle y comprobante**
6. **Confirmación**

Evitar campos WooCommerce que no sean necesarios para este modelo comercial.

## 10. Responsive / mobile-first

El móvil se considera interfaz principal, no versión secundaria.

Criterios:

- CTA accesibles con pulgar;
- tarjetas sin texto diminuto;
- búsqueda fácil de abrir/usar;
- localidad siempre identificable;
- carrito visible;
- hero legible sin depender de texto incrustado en imágenes;
- grids que reduzcan columnas progresivamente;
- sin scroll horizontal accidental;
- targets táctiles de al menos ~44 px cuando sea posible.

## 11. Accesibilidad y calidad visual

- contraste adecuado en ambos temas;
- estados `hover`, `focus` y `active` coherentes;
- navegación por teclado razonable;
- labels accesibles para controles icon-only;
- imágenes con `alt` cuando corresponda;
- no comunicar información exclusivamente por color;
- animaciones discretas y respetuosas de `prefers-reduced-motion` cuando se añadan.

## 12. Roadmap priorizado

### 🔴 P0

- eliminar contenido demo/datos falsos;
- header + navegación + footer;
- Home completamente nueva;
- hero slider;
- Base Negra + Base Verde;
- carrito;
- checkout.

### 🟠 P1

- catálogo/categorías;
- tarjeta de producto;
- producto individual;
- selector de localidad.

### 🟡 P2

- seguimiento del pedido;
- WhatsApp;
- favoritos;
- responsive fino;
- accesibilidad.

### 🟢 P3

- SEO;
- performance;
- microinteracciones;
- refinamiento visual final.

## 13. Criterios de aceptación de Home UI v1

La primera entrega del Home se considerará lista para revisión cuando:

- [ ] no muestre categorías demo de Electro;
- [ ] no muestre datos falsos/demo;
- [ ] use la marca `ZoFloridane` correctamente;
- [ ] tenga header limpio;
- [ ] muestre localidad activa;
- [ ] tenga hero rotatorio funcional;
- [ ] hero tenga CTA real;
- [ ] muestre categorías reales;
- [ ] muestre productos destacados/más vendidos;
- [ ] tenga bloque de confianza;
- [ ] tenga sección Cómo comprar;
- [ ] tenga footer propio;
- [ ] permita cambiar Base Negra / Base Verde;
- [ ] persista el tema elegido;
- [ ] funcione sin errores visibles en desktop y móvil;
- [ ] mantenga funcionamiento de carrito/localidad;
- [ ] no requiera activar pasarelas externas reales para probar la UI.

## 14. Restricciones

Durante esta fase:

- no modificar datos reales de producción;
- no introducir credenciales en código;
- no reemplazar WooCommerce sin justificación;
- no romper el comportamiento de localidad/disponibilidad;
- no convertir el rediseño en una dependencia innecesaria de un page builder;
- no duplicar lógica entre temas negro y verde.

La prioridad es modernizar la experiencia sobre el motor ya funcional.
