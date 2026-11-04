<?php
//sesion del panel: se incluye al principio de cada pagina del panel

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "httponly" => true,     //javascript no puede leer la cookie
        "samesite" => "Lax",    //no se envia en formularios de otras webs
        "secure" => !empty($_SERVER["HTTPS"]),
    ]);
    session_start();
}

//true si hay un usuario con la sesion iniciada
function haySesion(): bool {
    return isset($_SESSION["usuario_id"]);
}

//si no hay sesion, manda al login; $base es la ruta hasta la raiz del proyecto
function requiereLogin(string $base): void {
    if (!haySesion()) {
        header("Location: {$base}panel/login.php");
        exit;
    }

    //si otro administrador ha eliminado este usuario, su sesion deja de valer
    require_once __DIR__ . "/../clases/usuarioDAO.php";
    $usuario = (new UsuarioDAO())->findById($_SESSION["usuario_id"]);
    if (!$usuario) {
        cerrarSesion();
        header("Location: {$base}panel/login.php");
        exit;
    }

    //el rol se lee de la base en cada pagina, asi un cambio de rol se aplica al momento
    $_SESSION["usuario_rol"] = $usuario->getRol();
}

//true si el usuario del panel es administrador
function esAdmin(): bool {
    return ($_SESSION["usuario_rol"] ?? "") === "admin";
}

//para paginas que solo pueden usar los administradores (gestion de usuarios)
function requiereAdmin(string $base): void {
    requiereLogin($base);
    if (!esAdmin()) {
        header("Location: {$base}panel/index.php?msg=sin_permiso");
        exit;
    }
}

function iniciarSesion(Usuario $usuario): void {
    //nuevo id de sesion para evitar que alguien reutilice uno anterior
    session_regenerate_id(true);
    $_SESSION["usuario_id"] = $usuario->getId();
    $_SESSION["usuario_nombre"] = $usuario->getNombre();
    $_SESSION["usuario_rol"] = $usuario->getRol();
}

//cierra solo la sesion del panel; si tambien hay un miembro del sitio publico, sigue dentro
function cerrarSesion(): void {
    unset($_SESSION["usuario_id"], $_SESSION["usuario_nombre"], $_SESSION["usuario_rol"]);
    session_regenerate_id(true);
}

// ---------- sesion del sitio publico (miembros) ----------

function hayMiembro(): bool {
    return isset($_SESSION["miembro_id"]);
}

//el catalogo publico solo se ve con una cuenta de miembro o con la sesion del panel abierta
function requiereMiembro(string $base): void {
    if (hayMiembro()) {
        //se leen los datos en cada pagina: si se cambian en el perfil se ven al momento
        //y si la cuenta ya no existe la sesion deja de valer
        require_once __DIR__ . "/../clases/miembroDAO.php";
        $miembro = (new MiembroDAO())->findById($_SESSION["miembro_id"]);
        if ($miembro) {
            guardarDatosMiembro($miembro);
            return;
        }
        cerrarSesionMiembro();
    }
    if (haySesion()) {
        return;
    }
    header("Location: {$base}login.php");
    exit;
}

//para paginas que necesitan una cuenta de miembro de verdad (el perfil)
function requiereCuentaMiembro(string $base): void {
    requiereMiembro($base);
    if (!hayMiembro()) {
        header("Location: {$base}index.php");
        exit;
    }
}

function iniciarSesionMiembro(Miembro $miembro): void {
    session_regenerate_id(true);
    $_SESSION["miembro_id"] = $miembro->getId();
    guardarDatosMiembro($miembro);
}

function guardarDatosMiembro(Miembro $miembro): void {
    $_SESSION["miembro_nombre"] = $miembro->getNombre();
    $_SESSION["miembro_avatar"] = $miembro->getAvatar();
}

function cerrarSesionMiembro(): void {
    unset($_SESSION["miembro_id"], $_SESSION["miembro_nombre"], $_SESSION["miembro_avatar"]);
    session_regenerate_id(true);
}

//nombre que se muestra en la cabecera publica
function nombreVisitante(): string {
    return $_SESSION["miembro_nombre"] ?? $_SESSION["usuario_nombre"] ?? "";
}

//archivo de la foto de perfil del miembro, o null si no tiene
function avatarVisitante(): ?string {
    return $_SESSION["miembro_avatar"] ?? null;
}

//token contra CSRF: cada formulario que modifica datos lo envia y se comprueba al recibirlo
function tokenCSRF(): string {
    if (empty($_SESSION["token"])) {
        $_SESSION["token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["token"];
}

function campoToken(): string {
    return '<input type="hidden" name="token" value="' . tokenCSRF() . '">';
}

//corta la peticion si no es POST o el token no coincide
function verificarToken(): void {
    $token = $_POST["token"] ?? "";
    if ($_SERVER["REQUEST_METHOD"] !== "POST" || !hash_equals(tokenCSRF(), $token)) {
        http_response_code(403);
        exit("Petición no válida. Vuelve atrás y recarga la página.");
    }
}
?>
