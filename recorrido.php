
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

    $linea = filter_var($_POST['linea'] ?? null, FILTER_VALIDATE_INT);
    $estacion = filter_var($_POST['estacion'] ?? null, FILTER_VALIDATE_INT);
    $orden = filter_var($_POST['orden'] ?? null, FILTER_VALIDATE_INT);
    $distancia = $_POST['distancia'] ?? '';

    if (!$linea || !$estacion || !$orden || $orden < 1 ||
        !is_numeric($distancia) || (float)$distancia < 0) {
        $error = 'Revisa los datos del recorrido.';
    } else {
        try {
            // Verificar que línea y estación pertenezcan a la misma municipalidad.
            $verificar = $conexion->prepare(
                "SELECT COUNT(*)
                 FROM lineas l
                 JOIN estaciones e
                   ON e.id_municipalidad=l.id_municipalidad
                 WHERE l.id_linea=? AND e.id_estacion=?"
            );
            $verificar->execute([$linea,$estacion]);

            if (!$verificar->fetchColumn()) {
                $error = 'La línea y estación deben pertenecer a la misma municipalidad.';
            } else {
                $stmt = $conexion->prepare(
                    "INSERT INTO linea_estacion
                    (id_linea,id_estacion,orden,distancia_siguiente_km)
                    VALUES (?,?,?,?)"
                );
                $stmt->execute([$linea,$estacion,$orden,$distancia]);
                header('Location: recorrido.php?guardado=1');
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'No se pudo guardar. Revisa si la estación o el orden ya están asignados.';
        }
    }
}

$lineas = $conexion->query(
    "SELECT id_linea,nombre FROM lineas ORDER BY nombre"
)->fetchAll();

$estaciones = $conexion->query(
    "SELECT id_estacion,nombre FROM estaciones ORDER BY nombre"
)->fetchAll();

$recorridos = $conexion->query(
    "SELECT l.nombre AS linea,e.nombre AS estacion,
            le.orden,le.distancia_siguiente_km
     FROM linea_estacion le
     JOIN lineas l ON l.id_linea=le.id_linea
     JOIN estaciones e ON e.id_estacion=le.id_estacion
     ORDER BY l.nombre,le.orden"
)->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recorridos | Transmetro</title>
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
<h2 class="fw-bold">Recorridos de Transmetro</h2>
<p class="text-secondary">Asigna estaciones a cada línea y establece su orden.</p>

<?php if (isset($_GET['guardado'])): ?>
<div class="alert alert-success">Estación asignada correctamente.</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="card mb-4"><div class="card-body p-4">
<h4>Asignar estación a una línea</h4>
<form method="POST" class="row g-3">
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

<div class="col-md-6">
<label class="form-label">Línea</label>
<select name="linea" class="form-select" required>
<option value="">Seleccionar línea</option>
<?php foreach ($lineas as $l): ?>
<option value="<?= (int)$l['id_linea'] ?>"><?= h($l['nombre']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Estación</label>
<select name="estacion" class="form-select" required>
<option value="">Seleccionar estación</option>
<?php foreach ($estaciones as $e): ?>
<option value="<?= (int)$e['id_estacion'] ?>"><?= h($e['nombre']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-6">
<label class="form-label">Orden de la estación</label>
<input name="orden" type="number" min="1" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Distancia a la siguiente estación (km)</label>
<input name="distancia" type="number" step="0.01" min="0" value="0" class="form-control" required>
</div>

<div class="col-12">
<button class="btn btn-brand">Guardar recorrido</button>
</div>
</form>
</div></div>

<div class="card"><div class="card-body p-4">
<h4>Estaciones asignadas</h4>
<div class="table-responsive">
<table class="table table-hover">
<thead><tr>
<th>Línea</th><th>Orden</th><th>Estación</th><th>Distancia siguiente</th>
</tr></thead>
<tbody>
<?php foreach ($recorridos as $r): ?>
<tr>
<td><?= h($r['linea']) ?></td>
<td><?= (int)$r['orden'] ?></td>
<td><?= h($r['estacion']) ?></td>
<td><?= h($r['distancia_siguiente_km']) ?> km</td>
</tr>
<?php endforeach; ?>
<?php if (!$recorridos): ?>
<tr><td colspan="4">Todavía no hay recorridos registrados.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div></div>
</main>
</body>
</html>
