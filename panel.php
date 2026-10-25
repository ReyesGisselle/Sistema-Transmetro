
<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$nombre = htmlspecialchars($_SESSION['nombre'], ENT_QUOTES, 'UTF-8');
$rol = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel Transmetro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark" style="background:#14253e">
    <div class="container">
        <span class="navbar-brand">Transmetro Info</span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">
            Cerrar sesión
        </a>
    </div>
</nav>

<main class="container py-5">
    <h2>¡Bienvenida, <?= $nombre ?>!</h2>
    <p>Rol: <strong><?= htmlspecialchars($rol) ?></strong></p>

    <?php if ($rol === 'administrador'): ?>
<a href="lineas.php" class="btn btn-primary mb-3">
    Administrar líneas
</a>
        <div class="alert alert-primary">
            Panel de administración: aquí agregaremos los módulos
            para gestionar líneas, estaciones, buses y personal.
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            Panel de usuario: aquí podrás consultar las rutas,
            estaciones, buses y alertas del servicio.
        </div>
    <?php endif; ?>
</main>

</body>
</html>
