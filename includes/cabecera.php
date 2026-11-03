<?php
//cabecera de la parte publica
//antes de incluirla se definen $titulo, $base (ruta hasta la raiz) y $seccion (peliculas o directores)
$titulo = $titulo ?? "Películas";
$base = $base ?? "";
$seccion = $seccion ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?> · Catálogo de películas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/estilos.css">
</head>
<body>
    <header class="cabecera">
        <div class="cabecera__contenido">
            <a class="marca" href="<?php echo $base; ?>index.php">
                <span class="marca__icono">🎬</span>
                <span class="marca__texto">Catálogo de películas</span>
            </a>
            <nav class="nav">
                <ul>
                    <li><a href="<?php echo $base; ?>index.php" class="<?php echo $seccion === "peliculas" ? "activo" : ""; ?>">Películas</a></li>
                    <li><a href="<?php echo $base; ?>directores.php" class="<?php echo $seccion === "directores" ? "activo" : ""; ?>">Directores</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="contenedor">
