# Catálogo de películas

Aplicación web en PHP y MySQL para gestionar un catálogo de películas y directores. Tiene dos partes:

- **Sitio público**: los visitantes se registran, inician sesión y consultan el catálogo de películas y las fichas de los directores. Cada miembro tiene un perfil con sus datos y su foto.
- **Panel de administración**: el equipo crea, edita y elimina películas, directores y usuarios del panel.

Está pensada para ejecutarse en local con **XAMPP** (Apache + PHP 8.2 + MariaDB/MySQL), sin frameworks ni dependencias externas.

---

## Índice

1. [Funcionalidades](#funcionalidades)
2. [Tecnologías](#tecnologías)
3. [Instalación](#instalación)
4. [Estructura del proyecto](#estructura-del-proyecto)
5. [Base de datos](#base-de-datos)
6. [Usuarios y permisos](#usuarios-y-permisos)
7. [Reglas de los formularios](#reglas-de-los-formularios)
8. [Seguridad](#seguridad)
9. [Diseño](#diseño)
10. [Mantenimiento](#mantenimiento)
11. [Pendiente y mejoras posibles](#pendiente-y-mejoras-posibles)

---

## Funcionalidades

### Sitio público (`/peliculas/`)

| Página | Qué hace |
|---|---|
| `registro.php` | Alta de una cuenta de miembro con nombre, email, teléfono y contraseña. Incluye un medidor de seguridad de la contraseña. La cuenta queda **sin verificar** y se envía un enlace al email. |
| `verificar.php` | Página del enlace del correo: activa la cuenta (o explica si el enlace ha caducado, ya se usó o no es válido). |
| `reenviarVerificacion.php` | Envía un enlace de verificación nuevo (como mucho uno por minuto). |
| `login.php` | Inicio de sesión con email y contraseña. Sin el email verificado no deja entrar y ofrece reenviar el enlace. |
| `index.php` | Catálogo de películas en tarjetas (portada, género, año, director y actores), con buscador y filtros por género. |
| `directores.php` | Fichas de los directores con biografía, nacionalidad, fecha de nacimiento y sus películas del catálogo. |
| `perfil.php` | Datos del miembro: cambiar nombre, email (pide la contraseña actual) y teléfono, cambiar la contraseña y la foto de perfil. La foto puede ser propia o uno de los 10 avatares de personajes incluidos. |
| `logout.php` | Cierra la sesión del miembro. |

El catálogo solo se ve con una cuenta de miembro o con la sesión del panel abierta.

### Panel de administración (`/peliculas/panel/`)

| Página | Qué hace |
|---|---|
| `login.php` | Inicio de sesión del panel con nombre de usuario y contraseña. |
| `index.php` | Resumen con totales y dos pestañas, **Películas** y **Directores**, con tablas, buscador y botones de crear, editar y eliminar. |
| `pelicula/formPelicula.php` | Crear y editar películas: título, director (de la lista), año, género, actores, portada y si se ha visto. |
| `director/formDirector.php` | Crear y editar directores: nombre, nacionalidad, fecha de nacimiento y biografía (con contador de caracteres). |
| `usuario/usuarios.php` | Solo administradores: lista de usuarios del panel, alta de usuarios con rol y eliminación. |
| `usuario/cambiarPassword.php` | Cambiar la contraseña propia. |
| `correos.php` | Solo administradores y solo en modo `archivo`: buzón de pruebas con los correos que la web habría enviado. |
| `logout.php` | Cierra la sesión del panel. |

Los archivos `guardar*.php` y `eliminar*.php` no son páginas: reciben los formularios, hacen el trabajo y redirigen con un aviso (`?msg=...`).

---

## Tecnologías

- **PHP 8.2**, con PDO para la base de datos y la extensión GD para procesar imágenes.
- **MariaDB 10.4 / MySQL**, la que trae XAMPP.
- **HTML, CSS y JavaScript** sin librerías. El único recurso externo son las tipografías de Google Fonts (DM Sans y Fraunces).
- **Apache**, con reglas de acceso en archivos `.htaccess`.

El código sigue un patrón sencillo de **clases + DAO**: cada tabla tiene una clase con sus datos (`Director`, `Pelicula`, `Usuario`, `Miembro`) y una clase DAO que hace las consultas (`DirectorDAO`, `PeliculasDAO`...).

---

## Instalación

### 1. Requisitos

- XAMPP con PHP 8.2 o superior.
- El proyecto dentro de `C:\xampp\htdocs\peliculas`.

### 2. Activar la extensión GD

Sirve para reducir las fotos de perfil y las portadas. En `C:\xampp\php\php.ini`, quita el `;` de esta línea:

```ini
extension=gd
```

Sin GD la web funciona igual, pero guarda las imágenes sin reducirlas.

### 3. Crear la base de datos

Arranca Apache y MySQL desde el panel de XAMPP e importa en phpMyAdmin el archivo:

```
clases/datos/peliculas_bd.sql
```

Crea la base `peliculas_bd` con sus cuatro tablas y datos de ejemplo: 10 directores, 10 películas y un usuario administrador.

> ⚠️ Este script **borra las tablas si ya existen** (`DROP TABLE`). No lo ejecutes sobre una base con datos que quieras conservar. Para añadir una sola tabla a una base existente están `crear_tabla_usuario.sql` y `crear_tabla_miembro.sql`.

### 4. Configurar la conexión

Copia `clases/datos/config_example.php` como `clases/datos/config.php` y rellena los datos:

```php
$servidor = "localhost";
$nombreBase = "peliculas_bd";
$usuarioBD = "root";
$passwordBD = "";
```

`config.php` está en el `.gitignore`: cada persona tiene el suyo y las contraseñas no se suben al repositorio.

En el mismo archivo están `$urlSitio` (la dirección de la web, para el enlace de los correos) y `$correo` (cómo se envían). Ver [Verificación del email](#verificación-del-email).

### 5. Entrar

- Sitio público: <http://localhost/peliculas/>. Crea una cuenta desde "Regístrate gratis".
- Panel: <http://localhost/peliculas/panel/>, con el usuario de ejemplo **admin** / **admin123**.

> ⚠️ Cambia la contraseña de `admin` nada más entrar (menú 🔑 Contraseña).

---

## Estructura del proyecto

```
peliculas/
├── index.php, directores.php        Sitio público: catálogo y directores
├── registro.php, login.php,         Cuentas de miembro
│   logout.php, perfil.php
├── .htaccess                         Bloquea carpetas ocultas (.git), listados y archivos .sql/.db
│
├── panel/                            Panel de administración
│   ├── index.php, login.php, logout.php
│   ├── pelicula/                     formPelicula, guardarPelicula, eliminarPelicula
│   ├── director/                     formDirector, guardarDirector, eliminarDirector
│   └── usuario/                      usuarios, eliminarUsuario, cambiarPassword
│
├── clases/                           Bloqueada desde el navegador (.htaccess)
│   ├── director.php, peliculas.php,  Clases con los datos de cada tabla
│   │   usuario.php, miembro.php
│   ├── *DAO.php                      Consultas a la base de datos (siempre preparadas)
│   └── datos/
│       ├── config.php                Conexión (no se sube a git)
│       ├── config_example.php        Plantilla de config.php
│       └── *.sql                     Scripts para crear la base de datos
│
├── includes/                         Bloqueada desde el navegador (.htaccess)
│   ├── sesion.php                    Sesiones, permisos, token CSRF y errores de formularios
│   ├── seguridad.php                 Cabeceras de seguridad y limpieza de textos
│   ├── validacion_panel.php          Reglas de directores, películas y usuarios del panel
│   ├── validacion_miembro.php        Reglas del registro y el perfil
│   ├── imagenes.php                  Recorte y reducción de fotos y portadas (GD)
│   ├── cabecera.php, pie.php         Plantilla del sitio público
│   ├── cabecera_panel.php,           Plantilla del panel (menú lateral)
│   │   pie_panel.php
│   └── modal.php                     Ventana de confirmación al eliminar
│
├── assets/
│   ├── css/estilos.css               Todos los estilos (sitio y panel)
│   └── js/app.js                     Todo el JavaScript
│
└── img/                              Portadas de las películas
    └── avatares/                     Fotos de perfil (no ejecuta código: .htaccess)
        └── predefinidos/             10 avatares de personajes (+ svg/ con los originales)
```

Cada página indica en la variable `$base` la ruta hasta la raíz del proyecto (`""`, `"../"` o `"../../"`). Las cabeceras y los pies la usan para enlazar los estilos, los scripts y el menú.

---

## Base de datos

Base `peliculas_bd`, motor InnoDB, codificación `utf8mb4`.

### `pelicula`
| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT, autoincremental | |
| `titulo` | VARCHAR(150) | |
| `director` | VARCHAR(100) | Nombre del director, como texto |
| `anio` | INT | |
| `genero` | VARCHAR(50) | |
| `imagen` | VARCHAR(255) | Archivo de la portada dentro de `img/` |
| `visto` | TINYINT(1) | 0 o 1 |
| `actores` | TEXT | Lista en JSON: `["Actor Uno", "Actor Dos"]` |

### `director`
| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT, autoincremental | |
| `nombre` | VARCHAR(100) | |
| `nacionalidad` | VARCHAR(60) | |
| `fecha_nacimiento` | DATE | |
| `biografia` | TEXT | Opcional, máximo 1000 caracteres |

### `usuario` (usuarios del panel)
| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT, autoincremental | |
| `nombre` | VARCHAR(50), único | Con él se inicia sesión |
| `password` | VARCHAR(255) | Cifrada con `password_hash` |
| `rol` | ENUM('admin', 'usuario') | Por defecto `usuario` |

### `miembro` (cuentas del sitio público)
| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT, autoincremental | |
| `nombre` | VARCHAR(80) | |
| `email` | VARCHAR(120), único | Con él se inicia sesión; se guarda en minúsculas |
| `telefono` | VARCHAR(20) | Sin espacios ni guiones: `+34600111222` |
| `password` | VARCHAR(255) | Cifrada con `password_hash` |
| `avatar` | VARCHAR(255), opcional | Archivo dentro de `img/avatares/` (o `predefinidos/robot.webp`...) |
| `verificado` | TINYINT(1) | 1 cuando ha pulsado el enlace del correo; sin verificar no puede entrar |
| `email_verificado_en` | DATETIME, opcional | Cuándo lo verificó |
| `fecha_alta` | DATETIME | Se rellena sola |

### `token_email` (enlaces de verificación)
| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT, autoincremental | |
| `miembro_id` | INT | Se borra solo al borrar el miembro (`ON DELETE CASCADE`) |
| `tipo` | ENUM('verificar') | |
| `token_hash` | CHAR(64), único | **Hash SHA-256** del token: el token en claro solo va en el correo |
| `caduca_en` | DATETIME | Por defecto, 24 h después de crearlo |
| `usado_en` | DATETIME, opcional | Un enlace solo sirve una vez |
| `creado_en` | DATETIME | Se rellena sola |

> La película guarda el **nombre** del director, no su id. Si se cambia el nombre de un director, sus películas siguen con el nombre antiguo y dejan de aparecer en su ficha pública.

---

## Usuarios y permisos

Hay dos tipos de cuenta, en tablas separadas a propósito: **registrarse en el sitio público nunca da acceso al panel**.

| Cuenta | Dónde entra | Qué puede hacer |
|---|---|---|
| **Miembro** (`miembro`) | Sitio público | Ver el catálogo y los directores, y editar su propio perfil. |
| **Usuario** (`usuario`, rol `usuario`) | Panel | Crear, editar y eliminar películas y directores, y cambiar su contraseña. |
| **Administrador** (`usuario`, rol `admin`) | Panel | Todo lo anterior y además crear y eliminar usuarios del panel. |

- Un administrador no puede eliminarse a sí mismo, así que siempre queda al menos uno.
- Si se elimina un usuario o miembro con la sesión abierta, pierde el acceso en su siguiente clic.
- Un usuario del panel puede ver el sitio público sin tener cuenta de miembro.

---

## Reglas de los formularios

Todas las reglas se comprueban **en el servidor**, que es lo que de verdad protege. El navegador hace las mismas comprobaciones solo para avisar antes de enviar. Si algo falla, el formulario vuelve con el error debajo de cada campo y conserva lo que se había escrito (nunca las contraseñas).

| Formulario | Reglas |
|---|---|
| **Director** | Nombre obligatorio, de 2 a 30 letras. Nacionalidad obligatoria. Fecha de nacimiento obligatoria y de al menos 18 años antes de hoy (y posterior a 1850). Biografía de hasta 1000 caracteres. |
| **Película** | Título obligatorio (máximo 150). Director elegido de la lista. Año entre 1888 y el actual + 5. Género obligatorio, solo letras. Hasta 30 actores. Portada JPG, PNG, WEBP o GIF, de hasta 2 MB y 5000×5000 píxeles. |
| **Usuario del panel** | Nombre de 2 a 30 caracteres, sin espacios (letras, números, `.`, `-`, `_`) y no repetido. Contraseña de 8 a 72 caracteres, con letras y números y sin contener el nombre de usuario. Rol `usuario` o `admin`. |
| **Registro y perfil** | Nombre de 2 a 80 letras. Email válido y no repetido. Teléfono de 9 a 15 cifras (puede empezar por `+`). Contraseña de al menos 8 caracteres, con letras y números y distinta del email. Foto de hasta 2 MB. |

Los límites del panel están como constantes al principio de `includes/validacion_panel.php` para poder cambiarlos fácilmente.

---

## Seguridad

| Riesgo | Protección | Dónde |
|---|---|---|
| Inyección SQL | Todas las consultas son sentencias preparadas de PDO | `clases/*DAO.php` |
| Inyección de HTML/JS (XSS) | Todo lo que se muestra pasa por `htmlspecialchars`. Los textos se limpian al recibirlos (`sanitizarTexto`: sin etiquetas ni caracteres invisibles). La cabecera CSP impide ejecutar scripts que no sean de `assets/js/app.js`. | `includes/seguridad.php` |
| Envíos desde otras webs (CSRF) | Cada formulario lleva un token secreto de la sesión. Guardar y eliminar solo aceptan POST con token válido. | `includes/sesion.php` (`campoToken`, `verificarToken`) |
| Acceso sin permiso | `requiereLogin`, `requiereAdmin`, `requiereMiembro` y `requiereCuentaMiembro` al principio de cada página | `includes/sesion.php` |
| Contraseñas | Cifradas con `password_hash`. Para cambiar la contraseña o el email hay que escribir la contraseña actual. | DAO y formularios |
| Robo de sesión | Cookie `HttpOnly` y `SameSite=Lax`; identificador nuevo al iniciar sesión y al cambiar la contraseña | `includes/sesion.php` |
| Subida de archivos | El tipo se comprueba por el contenido, no por el nombre. Límites de peso y tamaño. Nombre aleatorio. Con GD, la imagen se vuelve a crear desde cero, lo que elimina el código escondido y los datos ocultos (GPS...). `img/avatares/` no ejecuta código. | `includes/imagenes.php`, `img/avatares/.htaccess` |
| Clickjacking | La web no se puede cargar dentro de un iframe (`frame-ancestors 'none'`, `X-Frame-Options`) | `includes/seguridad.php` |
| Archivos internos | `clases/` e `includes/` están bloqueadas desde el navegador; también `.git`, los `.sql` y `.db` y los listados de carpetas | archivos `.htaccess` |
| Datos con formato inesperado | `campoPost()` y `sanitizarTexto()` solo aceptan texto: si llega una lista (`campo[]=...`) se trata como vacío en vez de provocar un error | `includes/seguridad.php` |

**Cabeceras de seguridad** que se envían en todas las páginas (desde `includes/seguridad.php`, que carga `sesion.php`):
`Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: same-origin` y `Permissions-Policy`. Además se quita `X-Powered-By`.

> Por la CSP **no se puede escribir JavaScript dentro del HTML** (`<script>...</script>` ni `onclick="..."`). Todo el JavaScript va en `assets/js/app.js`, enganchado a atributos `data-...`.

---

## Diseño

- Estilo en **tonos pastel** (lavanda, menta, melocotón, rosa y azul cielo), con las variables de color definidas al principio de `estilos.css`.
- **Adaptable** a móvil, tablet y escritorio. En el panel, las tablas se convierten en tarjetas cuando no caben, según el ancho de la propia tabla (`@container`).
- Botones más grandes en pantallas táctiles, menos animaciones si el sistema lo pide (`prefers-reduced-motion`) y estilos de impresión.
- Avisos temporales tras guardar o eliminar, y ventana de confirmación antes de borrar.

---

## Mantenimiento

- **Para MySQL antes de cerrar XAMPP o apagar el ordenador** (botón Stop). Si se corta de golpe, sus tablas internas pueden dañarse y no volver a arrancar.
- **Copias de seguridad:** exporta `peliculas_bd` de vez en cuando desde phpMyAdmin (pestaña Exportar).
- **Las pruebas se hacen sobre una copia de la base de datos**, nunca sobre la real.
- **Nuevos avatares de personaje:** guarda la imagen como `img/avatares/predefinidos/<nombre>.webp` y añádela a la lista `AVATARES_PREDEFINIDOS` de `perfil.php`.
- **Archivos auxiliares nuevos** (clases, funciones): ponlos en `clases/` o `includes/` para que queden protegidos automáticamente.
- `clases/datos/peliculas.db` es la base SQLite de la primera versión del proyecto. Ya no se usa.

---

## Verificación del email

Adaptada del proyecto `login-app`. Al registrarse, la cuenta queda sin verificar y se envía un correo con un enlace:

1. Se genera un token aleatorio de 64 caracteres (`random_bytes(32)`). En `token_email` solo se guarda su **hash SHA-256**, así que aunque alguien leyera la base de datos no podría usar los enlaces.
2. El enlace (`verificar.php?token=...`) caduca en `horasEnlace` horas y solo sirve una vez. Cada miembro tiene como mucho un enlace pendiente: al pedir otro, el anterior deja de valer.
3. El alta y el envío van juntos en una transacción: si el correo no se puede enviar, la cuenta no se crea.
4. Si alguien se registra con un email que nunca verificó, se actualizan sus datos y se le envía un enlace nuevo.
5. Reenviar el enlace responde siempre con el mismo mensaje (no revela qué emails están registrados) y como mucho envía uno por minuto.

El envío lo hace `clases/correo.php` con **PHPMailer 7.1.1** (en `clases/lib/PHPMailer/`, sin necesidad de Composer). Se elige en `$correo["modo"]` de `config.php`:

| Modo | Qué hace |
|---|---|
| `archivo` (por defecto) | No envía nada: guarda cada correo como `.txt` en `clases/datos/correos/` (bloqueada desde el navegador y fuera de git). Se leen desde el panel, en **📬 Correos** (solo administradores). |
| `smtp` | Envía los correos de verdad. Rellena `$correo["smtp"]`. Con Gmail: `smtp.gmail.com`, puerto `587`, cifrado `tls` y una **contraseña de aplicación** (no la normal). |

> Al subir la web a un servidor, cambia `$urlSitio` a la dirección real (por ejemplo `https://tudominio.com`). Si no, los enlaces de los correos apuntarán a `localhost`.

---

## Pendiente y mejoras posibles

**Seguridad (recomendado antes de usarlo fuera de un ordenador personal):**
- MySQL: poner contraseña a `root`, que escuche solo en el propio ordenador y crear un usuario de MySQL solo para la web, con permisos únicamente sobre `peliculas_bd`.
- Bloquear temporalmente el inicio de sesión tras varios intentos fallidos. Ahora solo hay una espera de 1 segundo.
- Cerrar la sesión tras un tiempo de inactividad.
- Al publicarla en internet: HTTPS, `display_errors = Off` y no subir las páginas de ejemplo de XAMPP (`dashboard/`, `phpinfo.php`).

**Funcionalidad:**
- Relacionar las películas con el director por `id` en lugar de por nombre.
- Recuperar la contraseña olvidada por email (se puede hacer igual que la verificación, como en `login-app`).
- Pedir verificación de nuevo cuando un miembro cambia su email en el perfil.
- Cambiar el rol de un usuario existente desde el panel.
- Gestionar las cuentas de miembro desde el panel.
