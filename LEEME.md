# MWH Fresas con Crema — Proyecto final (Grupo 4)

Página web y panel administrativo para el negocio de fresas con crema.
**PHP 8 + MySQL (mysqli) + HTML + CSS + JavaScript.** Funciona en XAMPP.

Este proyecto **parte del código de ustedes**: su diseño, sus fotos, sus textos, sus
toppings, sus salsas y su regla de precio. Se arregló lo que estaba dañado y se le agregó
lo que exige el alcance mínimo (login, panel, CRUD, reportes, validación en el servidor).
Abajo está la lista completa de qué era suyo y qué se agregó: **eso va en el documento
final** (copiar sin citar deja el criterio en 0).

---

## 1. Cómo ponerlo a funcionar (10 minutos)

1. Abrir XAMPP y encender **Apache** y **MySQL**.
2. Copiar la carpeta `MWH-Fresas` dentro de `C:\xampp\htdocs\` (en Mac: `/Applications/XAMPP/htdocs/`).
3. Entrar a `http://localhost/phpmyadmin` → pestaña **Importar** → escoger `base_datos/fresas_db.sql` → **Continuar**.
   Crea la base `fresas_db` con todo y datos de ejemplo. **Ojo:** si ya existía, la borra y la crea de nuevo.
4. Abrir `http://localhost/MWH-Fresas/`

**Panel del negocio:** `http://localhost/MWH-Fresas/admin/` (también hay un enlace en el pie de página).

| Usuario | Correo | Clave de prueba | Puede |
|---|---|---|---|
| Administrador | `admin@mwhfresas.com` | `admin123` | Todo |
| Vendedor | `vendedor@mwhfresas.com` | `vender123` | Resumen, pedidos, marcar agotados, cambiar su clave |

> **Cambiar las dos claves antes de sustentar** (panel → *Mi clave*).

**En Mac, para poder subir fotos desde el panel:** el Apache de XAMPP no puede escribir en
la carpeta de las fotos hasta que se le da permiso. En la Terminal, dentro de la carpeta del proyecto:
```
chmod 777 assets/img/subidas
```
Si falta este paso, el panel lo avisa al guardar: "la carpeta assets/img/subidas no tiene
permiso de escritura". (En Windows no hace falta.)

**Verlo en el celular:** el celular y el computador en el mismo Wi-Fi. En el computador,
`ipconfig` (Windows) o `ifconfig` (Mac) para ver la IP, algo como `192.168.1.20`.
En el celular: `http://192.168.1.20/MWH-Fresas/`

---

## 2. Qué era de ustedes y qué se hizo

### Lo que ya tenían y se conservó
- El diseño: colores, logo, portada, tarjetas de producto, pie de página, sección "Nosotros"
  y "¿Por qué elegir MWH?".
- Las fotos reales (`assets/img/img1.png` a `assets/img/img5.png`) y el logo.
- Los datos del negocio: toppings (barquillos, gomitas, galletas, masmellos, queso),
  salsas (chocolate, arequipe, fresa, mora), la regla **1 topping $13.000 / 2 toppings $17.000**,
  el WhatsApp y la ciudad.
- La idea de personalizar el vaso y enviar el pedido por WhatsApp.
- `conexion.php` con `mysqli` y las consultas preparadas con `bind_param`, que ustedes ya usaban.

### Lo que estaba dañado y se arregló
| Problema | Qué pasaba | Arreglo |
|---|---|---|
| Conflicto de Git en `script.js` | Las marcas `<<<<<<<`, `=======`, `>>>>>>>` rompían todo el archivo: **el botón "Confirmar pedido" no hacía nada** | Se resolvió. Regla: antes de `git push`, siempre `git pull`; si sale CONFLICT, se resuelve antes de subir |
| Conflicto de Git en `estilos.css` | Mismo problema, y un bloque "PRUEBA" pintaba todo el fondo de rosado | Se unieron las dos versiones y se quitó la prueba |
| El precio lo mandaba el navegador | Siempre llegaba en 0, y cualquiera podía cambiarlo con F12 | Ahora el servidor calcula el precio con la base de datos |
| HTML dañado | `<head>` repetidos, `<!DOCTYPE>` al final, `nosotros.html` sin cerrar | El menú y el pie se escriben una sola vez (`includes/encabezado.php`, `includes/pie.php`) |
| `web.whatsapp.com` | En el celular no abre bien | Se usa `wa.me` |
| "Dancing Script" no cargaba | Faltaba el enlace de Google Fonts | Se agregó; queda para títulos, y el texto normal va en Poppins, que se lee mejor |
| Productos escritos a mano en el HTML | Dos se llamaban igual y no se podían cambiar sin tocar código | Salen de la base de datos y se editan en el panel |

### Lo que se agregó
- **Base de datos de 8 tablas** relacionadas (antes había 1).
- **Personaliza:** el vaso se dibuja en SVG (`assets/js/vaso.js`): fresas en el fondo, la salsa
  chorreando por las paredes y encima de la crema, y los toppings encima de todo, cada uno con
  su forma (barquillo, galleta, gomita, masmelo, queso o chispas) y su color; no deja
  escoger más toppings de los que trae el vaso; se pueden pedir varios vasos; los agotados
  aparecen tachados y no se pueden escoger.
- **Pedido guardado + WhatsApp:** cada pedido recibe un número (`MWH-00013`) y el mensaje
  de WhatsApp sale ya escrito con todo el detalle.
- **Fotos de productos:** vista previa al escogerla, máximo 5 MB, y al reemplazar una foto
  subida se borra la anterior.
- **Panel** con login y 2 roles: resumen con contadores, pedidos con buscador y filtro,
  orden de preparación, CRUD de productos con foto, toppings y salsas, 4 reportes,
  configuración del negocio y cambio de clave.

---

## 3. La base de datos (8 tablas)

```
usuarios          personal del panel (clave con SHA-256 + sal)
productos         los vasos: nombre, toppings que incluye, precio, foto
toppings          nombre, color de su capa, disponible, activo
salsas            nombre, color de su capa, disponible, activo
pedidos           cliente, celular, entrega, dirección, total, estado, fecha
detalle_pedidos   un renglón por vaso → pedido, producto, salsa, precio copiado
detalle_toppings  qué toppings lleva cada vaso (tabla intermedia)
configuracion     nombre, lema, WhatsApp, ciudad, horario, integrantes
```

```
productos ─┐
salsas ────┼──< detalle_pedidos >── pedidos
           │         │
toppings ──┴──< detalle_toppings
```

Decisiones que les van a preguntar:
- **¿Por qué `detalle_toppings`?** Un vaso lleva varios toppings y un topping está en muchos
  vasos: relación muchos a muchos. Se resuelve con una tabla en el medio.
- **¿Por qué el precio se copia en `detalle_pedidos`?** Si mañana sube el precio, los pedidos
  viejos conservan lo que se cobró ese día.
- **`disponible` vs `activo`:** *disponible* = hoy se acabó (vuelve mañana).
  *activo* = ya no se vende. Nada se borra con DELETE, porque los pedidos viejos lo usan
  (borrado lógico).
- **¿Por qué el cliente no tiene cuenta?** Nadie se registra para pedir un vaso de fresas.

---

## 4. Archivos

Organizados por carpetas: las **páginas públicas** van sueltas en la raíz, y lo demás en
su carpeta.

```
MWH-Fresas/
├── index.php             ← inicio                         ┐
├── nosotros.php                                           │ páginas
├── productos.php         ← menú + toppings y salsas del día │ públicas
├── personaliza.php       ← ★ el armador de vasos            │
├── guardar_pedido.php    ← ★ valida, calcula el precio y guarda con transacción ┘
│
├── assets/               ← todo lo que el navegador descarga
│   ├── css/
│   │   └── estilos.css   ← su CSS, arreglado, con las secciones nuevas (también el del panel)
│   ├── js/
│   │   ├── vaso.js       ← ★ dibuja el vaso en SVG (formas de cada topping, salsa)
│   │   ├── script.js     ← ★ lógica del armador (límites, validación, envío)
│   │   └── admin.js      ← validaciones del panel en el navegador
│   └── img/
│       ├── img1..5.png, logo.png    ← sus fotos
│       └── subidas/      ← fotos que se suben desde el panel (no deja ejecutar .php)
│
├── includes/             ← piezas que usan todas las páginas (no se abren solas)
│   ├── conexion.php      ← la conexión (la de ustedes, con utf8mb4 y excepciones)
│   ├── funciones.php     ← limpiar(), pesos(), consultar(), ejecutar(), config()...
│   ├── encabezado.php    ← <head> y menú, iguales en todas las páginas
│   └── pie.php           ← pie de página
├── base_datos/
│   ├── fresas_db.sql     ← importar en phpMyAdmin (no se puede descargar desde el navegador)
│   └── actualizacion_forma_toppings.sql  ← agrega la forma sin borrar los datos
│
└── admin/                ← el panel del negocio
    ├── seguridad.php     ← exigir_sesion(), exigir_admin(), huella de la clave
    ├── encabezado.php, pie.php   ← menú del panel
    ├── login.php, salir.php
    ├── index.php         ← resumen con contadores
    ├── pedidos.php       ← listado con buscador y filtro
    ├── pedido.php        ← detalle y cambio de estado
    ├── productos.php, producto_form.php   ← CRUD con foto
    ├── ingredientes.php  ← pantalla común de toppings.php y salsas.php
    └── reportes.php, configuracion.php, clave.php
```

**Rutas:** una página de la raíz pide `assets/css/estilos.css`; una página de `admin/` pide
`../assets/css/estilos.css` (los dos puntos significan "salir de la carpeta admin"). Si una
imagen o un estilo no carga, casi siempre es eso. En la base de datos la foto de cada
producto se guarda con su carpeta: `assets/img/img2.png` o `assets/img/subidas/producto_....png`.

Las carpetas `includes/`, `base_datos/` y `assets/img/subidas/` tienen un archivo `.htaccess`
que le dice a Apache (el de XAMPP) que no las deje abrir desde el navegador: nadie puede
descargar el `.sql` ni ejecutar un archivo subido. **No borren esos `.htaccess`.**

Los tres archivos con ★ son los que más van a preguntar. **Los cuatro integrantes deben
poder explicarlos.**

---

## 5. Las tres demostraciones para la sustentación

**1. El precio lo pone el servidor.** Hacer un pedido. En el navegador, F12 → Consola, y
antes de confirmar escribir:
`DATOS.productos.forEach(p => p.precio = 1)`. La pantalla va a mostrar $1, pero en el
panel el pedido aparece con el precio real. Explicación: el navegador solo manda **qué**
escogió el cliente; `guardar_pedido.php` busca los precios en la base de datos.

**2. Agotado en vivo.** Abrir *Personaliza* en el celular. En el panel, *Toppings* →
"Se acabó hoy" en Queso. Recargar el celular: el queso sale tachado y no se deja escoger.
Si alguien lo intenta de todas formas, el servidor lo rechaza.

**3. El vaso cambia desde el panel.** Panel → *Toppings* → *Editar* Gomitas →
cambiar el color → guardar. Recargar *Personaliza* y escoger gomitas: salen con el color
nuevo. Todavía mejor: crear un topping nuevo (por ejemplo "Oreo", color negro, forma
"Galleta") y mostrar que aparece en la página y se dibuja en el vaso **sin tocar el código**.
La pregunta que les van a hacer: *¿cómo sabe el dibujo que un barquillo es un palito?* —
porque la tabla `toppings` tiene la columna `forma`, y `vaso.js` tiene una función de dibujo
por cada forma.

---

## 6. Preguntas que les pueden hacer

1. **¿Por qué dejaron de mandar el precio desde el navegador?** — Porque se puede cambiar
   con F12. Antes siempre llegaba en 0. Ahora lo calcula el servidor con la base de datos.
2. **¿Qué pasó con el botón "Confirmar pedido"?** — Dos personas cambiaron el mismo archivo
   y se subió con las marcas del conflicto de Git. JavaScript no las entiende y deja de leer
   el archivo completo.
3. **¿Qué es una transacción y dónde la usan?** — En `guardar_pedido.php`. Un pedido son
   varios INSERT (el pedido, cada vaso, cada topping). Si uno falla, `rollback()` deshace los
   demás: nunca queda un pedido a medias.
4. **¿Dónde se valida?** — En los dos lados. En el navegador (`assets/js/script.js`, `assets/js/admin.js`), para
   avisar rápido. En el servidor, porque el JavaScript se puede desactivar o cambiar.
5. **¿Cómo evitan la inyección SQL?** — Con consultas preparadas: los valores van con `?` y
   `bind_param`, nunca pegados al texto del SQL. Probar en el login con `' OR '1'='1`.
6. **¿Cómo guardan las claves?** — No se guardan. Se guarda la huella SHA-256 de (sal + clave).
   Cada usuario tiene su propia sal, así que dos claves iguales dan huellas distintas.
7. **¿Qué hace `limpiar()`?** — Convierte `<` y `>` en texto, para que si alguien escribe
   `<script>` en las notas del pedido, se vea como texto y no se ejecute.
8. **¿Por qué validan la foto con `getimagesize()` y no por la extensión?** — Porque un archivo
   `.php` se puede renombrar a `.jpg`. `getimagesize()` abre el archivo y revisa que de verdad
   sea una imagen. Además, el nombre del archivo lo inventa el servidor.
9. **¿Qué diferencia hay entre admin y vendedor?** — El vendedor atiende pedidos y marca
   agotados. Productos, reportes y configuración son solo del admin (`exigir_admin()`).
10. **¿Qué queda como trabajo futuro?** — Pagos en línea, domicilios con costo por zona,
    que el cliente consulte su pedido por el número y publicar la página en internet.

---

## 7. Lo que les falta a ustedes

- [ ] Importar el `.sql`, abrir la página y el panel en su computador. Hacer un pedido completo.
- [ ] Confirmar con la dueña los precios, los toppings y las salsas. Cambiarlos desde el panel.
- [ ] Escoger con la dueña el color de cada topping y salsa (es el color de su capa en el vaso).
- [ ] Panel → *Configuración*: horario real y **sus nombres** en "integrantes" (sale en el pie).
- [ ] Cambiar las dos claves del panel.
- [ ] Probar en el celular por la IP, y enviar un pedido real por WhatsApp.
- [ ] Hacer las tres demostraciones de la sección 5 y tomarles captura.
- [ ] Exportar el `.sql` final desde phpMyAdmin con los datos reales (**sin el .sql: −10**).
- [ ] Manual del cliente, manual del administrador, documento final con la sección
      "Qué era nuestro y qué se agregó" (sección 2 de este archivo).
- [ ] Subir todo a su repositorio de GitHub (ver abajo).

**Subirlo a GitHub sin dañar nada:**
```
git pull                      # traer lo último primero
# borrar los archivos viejos de la raíz (los .html, estilos.css, script.js, img*.jpeg,
# pedidos.sql...) y copiar las carpetas y archivos nuevos
git add -A
git commit -m "Proyecto final: base de datos, panel y armador"
git push
```
Los archivos viejos no se pierden: quedan en el historial de Git como evidencia de lo que
hicieron solos.
