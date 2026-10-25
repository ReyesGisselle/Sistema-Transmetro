
<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_usuario']) ||
    ($_SESSION['rol'] ?? '') !== 'administrador') {
    http_response_code(403);
    exit('Acceso no autorizado');
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Solicitud inválida');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $municipalidad = filter_var($_POST['municipalidad'] ?? null, FILTER_VALIDATE_INT);
    $demanda = filter_var($_POST['demanda'] ?? '0', FILTER_VALIDATE_INT);
    $lat = trim($_POST['latitud'] ?? '');
    $lon = trim($_POST['longitud'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 160 ||
        !$municipalidad || $demanda === false || $demanda < 0 ||
        ($lat !== '' && (!is_numeric($lat) || (float)$lat < -90 || (float)$lat > 90)) ||
        ($lon !== '' && (!is_numeric($lon) || (float)$lon < -180 || (float)$lon > 180))) {
        $error = 'Revisa los datos ingresados.';
    } else {
        try {
            $stmt = $conexion->prepare(
                "INSERT INTO estaciones
                (id_municipalidad,nombre,direccion,latitud,longitud,demanda_espera)
                VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([
                $municipalidad, $nombre, $direccion,
                $lat === '' ? null : $lat,
                $lon === '' ? null : $lon,
                $demanda
            ]);
            header('Location: estaciones.php?guardado=1');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'No se pudo guardar la estación.';
        }
    }
}

$municipalidades = $conexion->query(
    "SELECT id_municipalidad,nombre FROM municipalidades ORDER BY nombre"
)->fetchAll();

$estaciones = $conexion->query(
    "SELECT e.*,m.nombre AS municipalidad
     FROM estaciones e
     JOIN municipalidades m ON m.id_municipalidad=e.id_municipalidad
     ORDER BY e.id_estacion DESC"
)->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Estaciones | Transmetro</title>
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
<a class="navbar-brand" href="panel.php">Transmetro Info</a>
<a href="panel.php" class="btn btn-outline-light btn-sm">Volver</a>
</div>
</nav>

<main class="container py-5">
<h2 class="fw-bold">Administración de estaciones</h2>
<p class="text-secondary">Registra las estaciones del sistema.</p>

<?php if (isset($_GET['guardado'])): ?>
<div class="alert alert-success">Estación registrada correctamente.</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="card mb-4"><div class="card-body p-4">
<h4>Registrar estación</h4>
<form method="POST" class="row g-3">
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

<div class="col-md-6">
<label class="form-label">Nombre</label>
<input name="nombre" class="form-control" maxlength="160" required>
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

<div class="col-md-12">
<label class="form-label">Dirección</label>
<input name="direccion" class="form-control">
</div>

<div class="col-md-4">
<label class="form-label">Latitud (opcional)</label>
<input name="latitud" type="number" step="any" class="form-control">
</div>

<div class="col-md-4">
<label class="form-label">Longitud (opcional)</label>
<input name="longitud" type="number" step="any" class="form-control">
</div>

<div class="col-md-4">
<label class="form-label">Pasajeros esperando (demo)</label>
<input name="demanda" type="number" min="0" value="0" class="form-control" required>
</div>

<div class="col-12">
<button class="btn btn-brand">Guardar estación</button>
</div>
</form>
</div></div>

<div class="card"><div class="card-body p-4">
<h4>Estaciones registradas</h4>
<div class="table-responsive">
<table class="table table-hover">
<thead><tr>
<th>ID</th><th>Estación</th><th>Municipalidad</th>
<th>Dirección</th><th>Demanda</th>
</tr></thead>
<tbody>
<?php foreach ($estaciones as $e): ?>
<tr>
<td><?= (int)$e['id_estacion'] ?></td>
<td><?= h($e['nombre']) ?></td>
<td><?= h($e['municipalidad']) ?></td>
<td><?= h($e['direccion']) ?></td>
<td><?= (int)$e['demanda_espera'] ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$estaciones): ?>
<tr><td colspan="5">No hay estaciones registradas.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div></div>
</main>
</body>
</html>
