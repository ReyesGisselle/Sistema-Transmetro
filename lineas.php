
<?php
session_start();
require_once 'conexion.php';

if (
    !isset($_SESSION['id_usuario']) ||
    ($_SESSION['rol'] ?? '') !== 'administrador'
) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Solicitud inválida.');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $municipalidad = filter_var(
        $_POST['municipalidad'] ?? null,
        FILTER_VALIDATE_INT
    );
    $distancia = $_POST['distancia'] ?? '';

    if (
        $nombre === '' ||
        mb_strlen($nombre) > 160 ||
        !$municipalidad ||
        !is_numeric($distancia) ||
        (float)$distancia < 0
    ) {
        $error = 'Revisa los datos ingresados.';
    } else {
        try {
            $stmt = $conexion->prepare(
                "INSERT INTO lineas
                (id_municipalidad, nombre, descripcion, distancia_total_km, estado)
                VALUES (?, ?, ?, ?, 'activa')"
            );

            $stmt->execute([
                $municipalidad,
                $nombre,
                $descripcion,
                $distancia
            ]);

            header('Location: lineas.php?guardado=1');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'No se pudo registrar la línea.';
        }
    }
}

$municipalidades = $conexion->query(
    "SELECT id_municipalidad, nombre FROM municipalidades ORDER BY nombre"
)->fetchAll();

$lineas = $conexion->query(
    "SELECT l.*, m.nombre AS municipalidad
     FROM lineas l
     INNER JOIN municipalidades m
       ON m.id_municipalidad = l.id_municipalidad
     ORDER BY l.id_linea DESC"
)->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administrar líneas | Transmetro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f5f7fb}
.navbar{background:#14253e}
.card{border:0;border-radius:16px;box-shadow:0 5px 20px #14253e12}
.btn-brand{background:#f0a72e;color:#14253e;font-weight:700}
</style>
</head>
<body>
<nav class="navbar navbar-dark">
<div class="container">
<a class="navbar-brand fw-bold" href="panel.php">Transmetro Info</a>
<a href="panel.php" class="btn btn-outline-light btn-sm">Volver al panel</a>
</div>
</nav>

<main class="container py-5">
<h2 class="fw-bold mb-2">Administración de líneas</h2>
<p class="text-secondary">Registro de líneas y sus municipalidades.</p>

<?php if (isset($_GET['guardado'])): ?>
<div class="alert alert-success">Línea registrada correctamente.</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="card mb-4">
<div class="card-body p-4">
<h4 class="mb-3">Registrar nueva línea</h4>

<form method="POST" class="row g-3">
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

<div class="col-md-6">
<label class="form-label">Nombre de la línea</label>
<input name="nombre" class="form-control" placeholder="Ej. Línea 12" maxlength="160" required>
</div>

<div class="col-md-6">
<label class="form-label">Municipalidad</label>
<select name="municipalidad" class="form-select" required>
<option value="">Seleccionar</option>
<?php foreach ($municipalidades as $m): ?>
<option value="<?= (int)$m['id_municipalidad'] ?>">
<?= h($m['nombre']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Distancia total (km)</label>
<input name="distancia" type="number" step="0.01" min="0" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Descripción</label>
<input name="descripcion" class="form-control" placeholder="Recorrido de la línea">
</div>

<div class="col-12">
<button class="btn btn-brand">Guardar línea</button>
</div>
</form>
</div>
</div>

<div class="card">
<div class="card-body p-4">
<h4 class="mb-3">Líneas registradas</h4>
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>ID</th><th>Línea</th><th>Municipalidad</th>
<th>Distancia</th><th>Estado</th>
</tr>
</thead>
<tbody>
<?php foreach ($lineas as $l): ?>
<tr>
<td><?= (int)$l['id_linea'] ?></td>
<td><?= h($l['nombre']) ?></td>
<td><?= h($l['municipalidad']) ?></td>
<td><?= h($l['distancia_total_km']) ?> km</td>
<td><span class="badge bg-success"><?= h($l['estado']) ?></span></td>
</tr>
<?php endforeach; ?>
<?php if (!$lineas): ?>
<tr><td colspan="5">Todavía no hay líneas registradas.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</main>
</body>
</html>
