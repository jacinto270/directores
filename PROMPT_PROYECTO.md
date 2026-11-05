# Prompt para crear el proyecto "Catálogo de películas"

Copia todo lo que hay debajo de la línea y pégalo como mensaje para que una IA de programación (por ejemplo, Claude Code) construya el proyecto desde cero.

---

Quiero que construyas una aplicación web completa en **PHP y MySQL** para gestionar un catálogo de películas y directores. La usaré en local con **XAMPP en Windows** (Apache, PHP 8.2 y MariaDB 10.4) y el proyecto irá en `C:\xampp\htdocs\peliculas`. Todo el texto visible, los comentarios del código y los nombres de variables y funciones deben estar **en español**.

Constrúyela por fases, en el orden indicado al final. Después de cada fase, prueba que funciona contra el servidor real y dime qué has comprobado y qué no.

## 1. Tecnología y estilo de código

- PHP 8.2 sin frameworks ni Composer. HTML, CSS y JavaScript sin librerías. El único recurso externo permitido son las tipografías de Google Fonts.
- Base de datos con **PDO** y siempre con **sentencias preparadas**. Nunca concatenes datos del usuario en una consulta.
- Patrón **clase + DAO**: por cada tabla, una clase con sus datos, getters y setters (`Director`, `Pelicula`, `Usuario`, `Miembro`) y una clase DAO con las consultas (`DirectorDAO`, `PeliculasDAO`, `UsuarioDAO`, `MiembroDAO`).
- Cada DAO carga los datos de conexión **dentro de su constructor** con `require __DIR__ . "/datos/config.php";`. No uses `global`, porque falla si la clase se carga por primera vez dentro de una función.
- En `config.php` usa los nombres `$servidor`, `$nombreBase`, `$usuarioBD` y `$passwordBD`, para que no choquen con variables de los formularios como `$usuario` o `$password`. Crea también `config_example.php` y añade `config.php` al `.gitignore`.
- Lee las filas de la base de datos **por nombre de columna** (`FETCH_ASSOC`), no por posición, para no depender del orden del constructor.
- Código sencillo y comentado, apto para alguien que está aprendiendo PHP.

## 2. Estructura de carpetas

```
peliculas/
├── index.php, directores.php, registro.php, login.php, logout.php, perfil.php   (sitio público)
├── panel/                index.php, login.php, logout.php
│   ├── pelicula/         formPelicula.php, guardarPelicula.php, eliminarPelicula.php
│   ├── director/         formDirector.php, guardarDirector.php, eliminarDirector.php
│   └── usuario/          usuarios.php, eliminarUsuario.php, cambiarPassword.php
├── clases/               clases, DAO y datos/ (config.php, config_example.php, scripts .sql)
├── includes/             sesion.php, seguridad.php, validacion_panel.php, validacion_miembro.php,
│                         imagenes.php, cabecera.php, pie.php, cabecera_panel.php, pie_panel.php, modal.php
├── assets/css/estilos.css   (todos los estilos)
├── assets/js/app.js         (todo el JavaScript)
└── img/                  portadas; img/avatares/ fotos de perfil; img/avatares/predefinidos/ avatares incluidos
```

- Las páginas que solo procesan formularios (`guardar*.php` y `eliminar*.php`) hacen el trabajo y redirigen con `?msg=...`, que muestra un aviso temporal.
- Cada página define `$base` (la ruta hasta la raíz: `""`, `"../"` o `"../../"`) antes de incluir la cabecera, para que los enlaces, estilos y scripts funcionen desde cualquier carpeta.

## 3. Base de datos `peliculas_bd`

InnoDB y `utf8mb4`. Genera `clases/datos/peliculas_bd.sql`, que cree la base, las tablas y datos de ejemplo: 10 directores reales con biografía, 10 películas suyas y un usuario `admin` / `admin123` con rol `admin` y la contraseña ya cifrada. Pon un aviso en el script de que hace `DROP TABLE`.

- **pelicula**: `id`, `titulo` VARCHAR(150), `director` VARCHAR(100) (nombre del director), `anio` INT, `genero` VARCHAR(50), `imagen` VARCHAR(255), `visto` TINYINT(1), `actores` TEXT (lista en JSON).
- **director**: `id`, `nombre` VARCHAR(100), `nacionalidad` VARCHAR(60), `fecha_nacimiento` DATE, `biografia` TEXT NULL.
- **usuario** (panel): `id`, `nombre` VARCHAR(50) UNIQUE, `password` VARCHAR(255), `rol` ENUM('admin','usuario') DEFAULT 'usuario'.
- **miembro** (sitio público): `id`, `nombre` VARCHAR(80), `email` VARCHAR(120) UNIQUE, `telefono` VARCHAR(20), `password` VARCHAR(255), `avatar` VARCHAR(255) NULL, `fecha_alta` DATETIME DEFAULT CURRENT_TIMESTAMP.

Las contraseñas siempre con `password_hash` y `password_verify`.

## 4. Cuentas y permisos

Dos tipos de cuenta **en tablas separadas**: registrarse en el sitio público **nunca** debe dar acceso al panel.

| Cuenta | Puede |
|---|---|
| Miembro | Ver el catálogo y los directores, y editar su perfil |
| Usuario del panel (rol `usuario`) | Gestionar películas y directores, y cambiar su contraseña |
| Administrador (rol `admin`) | Además, crear y eliminar usuarios del panel y elegir su rol |

- En `includes/sesion.php`: `requiereLogin`, `requiereAdmin`, `requiereMiembro` y `requiereCuentaMiembro`, que se llaman al principio de cada página, incluidas las de guardar y eliminar.
- El rol y los datos del usuario se vuelven a leer de la base de datos en cada página. Si se elimina una cuenta con la sesión abierta, pierde el acceso en su siguiente clic.
- La sesión del panel y la del sitio público conviven en el mismo navegador: cerrar una no cierra la otra.
- Un administrador no puede eliminarse a sí mismo y nunca se puede eliminar al último administrador.
- Un usuario del panel puede ver el sitio público sin tener cuenta de miembro.

## 5. Funcionalidades

**Sitio público** (el catálogo solo se ve con sesión de miembro o del panel):
- **Registro** con nombre, email, teléfono y contraseña repetida, con un medidor de seguridad de la contraseña. Al registrarse se inicia la sesión.
- **Login** con email y contraseña.
- **Catálogo**: tarjetas con portada, año, género, director y actores, un buscador sin distinción de acentos y filtros por género.
- **Directores**: fichas con biografía completa, nacionalidad, fecha de nacimiento y sus películas del catálogo.
- **Mi perfil**:
  - Cambiar nombre y teléfono.
  - Cambiar el email; pide la contraseña actual, y ese campo aparece solo al modificar el email.
  - Cambiar la contraseña.
  - Subir una foto de perfil, con vista previa antes de guardarla, o quitarla.
  - Elegir uno de **10 avatares originales** de personajes típicos del cine: robot, vampiro, pirata, astronauta, mago, dinosaurio, fantasma, superhéroe, detective y alienígena. Dibújalos tú en SVG con los colores pastel de la web y conviértelos a WEBP de 400×400. No copies personajes con derechos de autor.
- **Cabecera**: muestra la foto o las iniciales del visitante, su nombre (que lleva al perfil) y el botón Salir.

**Panel de administración** (con menú lateral):
- Resumen con totales y pestañas **Películas** y **Directores**, cada una con tabla, buscador y botones de crear, editar y eliminar.
- **Formulario de película**: el director se elige de una lista, el género tiene sugerencias, los actores van separados por comas, la portada tiene vista previa e interruptor "vista". Al cambiar la portada se borra la anterior.
- **Formulario de director**: biografía con contador de caracteres (`245 / 1000`).
- **Usuarios** (solo administradores): lista con el rol de cada uno, formulario de alta y eliminar.
- **Cambiar contraseña** propia.
- Eliminar siempre pide confirmación en una ventana propia, no con `confirm()`.

## 6. Reglas de validación

Todas se comprueban **en el servidor**. El navegador repite las mismas solo para avisar antes de enviar. Si algo falla, se vuelve al formulario con el error **debajo de cada campo**, un aviso arriba ("hay 3 errores") y lo escrito conservado; las contraseñas nunca se vuelven a escribir. Para volver al formulario tras redirigir, guarda los errores en la sesión y bórralos al mostrarlos.

- **Director**: nombre obligatorio, de 2 a 30 caracteres, solo letras, espacios, punto, apóstrofe y guion. Nacionalidad obligatoria. Fecha de nacimiento obligatoria, real (no 31 de febrero), de **al menos 18 años antes de hoy**, calculado cada día, y posterior a 1850. Biografía de hasta 1000 caracteres, cortada también en el servidor con `mb_substr`.
- **Película**: título obligatorio (máximo 150). Director obligatorio y de la lista (no se acepta uno inventado). Año entre 1888 y el actual + 5. Género obligatorio, solo letras. Hasta 30 actores.
- **Usuario del panel**: nombre de 2 a 30 caracteres, sin espacios (letras, números, `.`, `-` y `_`) y no repetido. Contraseña de 8 a 72 caracteres (72 es el límite de `password_hash`), con letras y números, y que no contenga el nombre de usuario si este tiene 3 o más caracteres. Rol solo de la lista.
- **Miembro**: nombre de 2 a 80 letras. Email válido, guardado en minúsculas y no repetido. Teléfono de 9 a 15 cifras, con `+` opcional, guardado sin espacios ni guiones. Contraseña de al menos 8 caracteres, con letras y números y distinta del email.
- **Imágenes** (portadas y fotos de perfil): JPG, PNG, WEBP o GIF, de hasta 2 MB y 5000×5000 píxeles. El tipo se comprueba por el contenido (`mime_content_type` y `getimagesize`), no por el nombre. Si la imagen no se puede procesar, muestra un error y **no guardes el registro**.
- Pon los límites como constantes al principio de los archivos de validación.

## 7. Seguridad

- **XSS**: todo lo que se muestra pasa por `htmlspecialchars`. Crea `sanitizarTexto()` en `includes/seguridad.php`, que quita etiquetas, caracteres de control invisibles y bytes UTF-8 inválidos, y une los espacios; en textos de varias líneas conserva los saltos de línea. Úsala en todos los formularios.
- **Valores que no son texto**: crea `campoPost()`, que devuelve `""` si llega una lista (`campo[]=x`) en lugar de un texto, para evitar errores fatales que muestren rutas del servidor.
- **CSRF**: un token por sesión en cada formulario (`campoToken()`) y comprobado con `hash_equals` (`verificarToken()`). Guardar, eliminar y cerrar sesión solo con POST y token válido.
- **Sesión**: cookie `HttpOnly` y `SameSite=Lax`. `session_regenerate_id(true)` al iniciar sesión, al cerrarla y al cambiar la contraseña. Un segundo de espera tras cada login fallido.
- **Cabeceras de seguridad** en todas las páginas, desde `includes/seguridad.php` cargado por `sesion.php`:
  - `Content-Security-Policy` estricta: `default-src 'self'`, `script-src 'self'`, Google Fonts permitido, `img-src 'self' data: blob:`, `form-action 'self'`, `frame-ancestors 'none'`, `base-uri 'self'` y `object-src 'none'`.
  - `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: same-origin` y `Permissions-Policy`.
  - Quitar `X-Powered-By`.
  - Por la CSP, **nada de JavaScript dentro del HTML** (ni `<script>` ni `onclick`/`onerror`): todo en `app.js`, enganchado a atributos `data-*`.
- **Subidas**: nombre aleatorio. Con la extensión GD, vuelve a crear la imagen desde cero en WEBP, lo que elimina el código escondido y los datos EXIF/GPS. Endereza las fotos de móvil según el EXIF. Las fotos de perfil se recortan en cuadrado a 400×400 y las portadas se reducen sin recortar a un máximo de 600×900. Sin GD, guarda el archivo tal cual. Los avatares predefinidos se eligen de una lista cerrada, nunca por una ruta enviada por el usuario, y no se borran al cambiar de foto.
- **Acceso a archivos** con `.htaccess`:
  - `clases/` e `includes/` con `Require all denied`.
  - En la raíz: `Options -Indexes`, bloquear archivos ocultos (`.git`...) y bloquear las extensiones `.sql`, `.db`, `.md`, `.ini`, `.log` y `.bak`.
  - `img/avatares/` sin ejecución de código y sin servir `.php`, `.html` ni `.svg`.

## 8. Diseño

- Profesional, en **tonos pastel**: fondo crema, lavanda como color principal, y menta, melocotón, rosa y azul cielo para etiquetas y avisos. Colores como variables CSS al principio de `estilos.css`. Tipografías DM Sans (texto) y Fraunces (títulos).
- **Sitio público y panel con diseños distintos**: el público con cabecera superior y cabecera grande con buscador; el panel con menú lateral, nombre del usuario y botones de contraseña y salir.
- **Adaptable a todas las pantallas**, de 320 px a 1600 px o más:
  - En el panel, las tablas pasan a tarjetas con etiquetas cuando no caben, usando `@container` sobre el ancho de la tabla.
  - En móvil, el menú lateral pasa a barra superior.
  - Los filtros se deslizan en horizontal.
- Botones más grandes en pantallas táctiles, sin efectos de hover en móviles, `prefers-reduced-motion` y estilos de impresión.
- Avisos temporales tras guardar o eliminar y medidor de seguridad de la contraseña.

## 9. Cómo quiero que trabajes

- **Pruebas sobre una copia de la base de datos**, nunca sobre la real. Nada de pruebas destructivas contra mis datos.
- Tras cada fase, prueba con peticiones reales (curl), incluidos los casos límite (por ejemplo, 30 y 31 caracteres, o el día exacto de los 18 años), y haz capturas a 360 px y 1280 px con Chrome sin interfaz. Borra después todo lo que hayas creado para probar.
- Haz una **prueba de seguridad final**: acceso sin permiso, CSRF, XSS, inyección SQL, subidas maliciosas, fijación de sesión y acceso directo a archivos internos. Usa solo cuentas y datos de prueba.
- Antes de tocar la configuración de XAMPP (`php.ini`, `httpd.conf`), haz una copia del archivo y dime si hay que reiniciar Apache.
- Si algo no lo has podido probar, dilo claramente.

## 10. Orden de las fases

1. Base de datos, clases, DAO y configuración.
2. Panel: login, películas y directores (crear, editar, eliminar) con validación completa.
3. Usuarios del panel con roles y cambio de contraseña.
4. Sitio público: registro, login, catálogo y directores.
5. Perfil del miembro con foto y avatares predefinidos.
6. Diseño pastel y adaptación a todas las pantallas.
7. Seguridad: cabeceras, limpieza de entradas, `.htaccess` y procesado de imágenes con GD.
8. Prueba de seguridad final y un `README.md` con la descripción del proyecto, la instalación, la estructura, la base de datos, los permisos, las reglas y la seguridad.

**Pendiente para una versión posterior** (no lo hagas salvo que te lo pida): usuario de MySQL propio con contraseña y permisos mínimos, bloqueo temporal tras varios logins fallidos, cierre de sesión por inactividad, recuperar contraseña por email y relacionar las películas con el director por `id`.
