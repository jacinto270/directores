<?php
//cabecera del panel de administracion (menu lateral)
//antes de incluirla se definen $titulo, $seccion (peliculas o directores) y $base (ruta hasta la raiz)
$titulo = $titulo ?? "Panel";
$seccion = $seccion ?? "";
$base = $base ?? "../";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?> · Panel de administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/estilos.css">
</head>
<body class="admin">
    <aside class="admin__lateral">
        <a class="marca" href="<?php echo $base; ?>panel/index.php">
            <span class="marca__icono">🎬</span>
            <span>
                <span class="marca__texto">Películas</span>
                <small class="admin__subtitulo">Panel de administración</small>
            </span>
        </a>
        <nav class="admin__menu">
            <a href="<?php echo $base; ?>panel/index.php?tab=peliculas" class="<?php echo $seccion === "peliculas" ? "activo" : ""; ?>"><span>🎞️</span> Películas</a>
            <a href="<?php echo $base; ?>panel/index.php?tab=directores" class="<?php echo $seccion === "directores" ? "activo" : ""; ?>"><span>🎬</span> Directores</a>
            <?php if (esAdmin()): ?>
                <a href="<?php echo $base; ?>panel/usuario/usuarios.php" class="<?php echo $seccion === "usuarios" ? "activo" : ""; ?>"><span>👤</span> Usuarios</a>
                <?php
                    //buzon de pruebas: solo tiene sentido cuando los correos se guardan en archivos
                    require_once __DIR__ . "/../clases/correo.php";
                    if (Correo::modo() === "archivo"):
                ?>
                    <a href="<?php echo $base; ?>panel/correos.php" class="<?php echo $seccion === "correos" ? "activo" : ""; ?>"><span>📬</span> Correos</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        <a class="admin__publico" href="<?php echo $base; ?>index.php" target="_blank" title="Ver sitio público"><span class="admin__publico-texto">Ver sitio público</span> ↗</a>
        <div class="admin__usuario">
            <div class="admin__usuario-nombre">
                <span class="avatar avatar--pequeno" data-nombre="<?php echo htmlspecialchars($_SESSION["usuario_nombre"]); ?>"></span>
                <span class="admin__usuario-texto"><?php echo htmlspecialchars($_SESSION["usuario_nombre"]); ?></span>
            </div>
            <a href="<?php echo $base; ?>panel/usuario/cambiarPassword.php" class="admin__usuario-accion <?php echo $seccion === "usuario" ? "activo" : ""; ?>" title="Cambiar contraseña">🔑<span class="admin__usuario-texto"> Contraseña</span></a>
            <form action="<?php echo $base; ?>panel/logout.php" method="post">
                <?php echo campoToken(); ?>
                <button type="submit" class="admin__usuario-accion" title="Cerrar sesión">⏻<span class="admin__usuario-texto"> Salir</span></button>
            </form>
        </div>
    </aside>
    <main class="admin__contenido">
