<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') { http_response_code(403); exit('Acceso no autorizado.'); }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Solicitud inválida.'); }
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $estacion = filter_var($_POST['id_estacion'] ?? null, FILTER_VALIDATE_INT);
    $capacidad = filter_var($_POST['capacidad'] ?? null, FILTER_VALIDATE_INT);
    if ($nombre === '' || mb_strlen($nombre) > 160 || !$estacion || $capacidad === false || $capacidad < 1) {
        $error = 'Completa el nombre, estación y capacidad válida.';
    } else {
        try {
            $stmt = $conexion->prepare('INSERT INTO parqueos (id_estacion, nombre, capacidad) VALUES (?, ?, ?)');
            $stmt->execute([$estacion, $nombre, $capacidad]);
            header('Location: parqueos.php?guardado=1'); exit;
        } catch (PDOException $e) { error_log($e->getMessage()); $error = 'No se pudo guardar el parqueo.'; }
    }
}
$estaciones = $conexion->query('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre')->fetchAll();
$parqueos = $conexion->query('SELECT p.id_parqueo,p.nombre,p.capacidad,e.nombre AS estacion,COUNT(b.id_bus) AS ocupados FROM parqueos p JOIN estaciones e ON e.id_estacion=p.id_estacion LEFT JOIN buses b ON b.id_parqueo=p.id_parqueo GROUP BY p.id_parqueo,p.nombre,p.capacidad,e.nombre ORDER BY p.id_parqueo DESC')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Parqueos | Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb}.navbar{background:#14253e}.card{border:0;border-radius:16px;box-shadow:0 6px 22px #14253e12}.btn-brand{background:#f0a72e;color:#14253e;font-weight:700}</style></head><body>
<nav class="navbar navbar-dark"><div class="container"><a class="navbar-brand" href="panel.php">Transmetro Info</a><a class="btn btn-outline-light btn-sm" href="panel.php">Volver al panel</a></div></nav>
<main class="container py-5"><h1 class="h2 fw-bold">Administración de parqueos</h1><p class="text-secondary">Cada parqueo pertenece a una estación. La capacidad indica cuántos buses puede alojar.</p>
<?php if(isset($_GET['guardado'])): ?><div class="alert alert-success">Parqueo registrado correctamente.</div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>
<div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Registrar parqueo</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><div class="col-md-4"><label class="form-label">Nombre del parqueo</label><input class="form-control" name="nombre" maxlength="160" placeholder="Parqueo Central" required></div><div class="col-md-4"><label class="form-label">Estación</label><select class="form-select" name="id_estacion" required><option value="">Selecciona una estación</option><?php foreach($estaciones as $e): ?><option value="<?=(int)$e['id_estacion']?>"><?=h($e['nombre'])?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Capacidad de buses</label><input class="form-control" type="number" name="capacidad" min="1" required></div><div class="col-12"><button class="btn btn-brand">Guardar parqueo</button></div></form></div></div>
<div class="card"><div class="card-body p-4"><h2 class="h5">Parqueos registrados</h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>ID</th><th>Parqueo</th><th>Estación</th><th>Capacidad</th><th>Buses asignados</th></tr></thead><tbody><?php foreach($parqueos as $p): ?><tr><td><?=(int)$p['id_parqueo']?></td><td><?=h($p['nombre'])?></td><td><?=h($p['estacion'])?></td><td><?=(int)$p['capacidad']?></td><td><?=(int)$p['ocupados']?></td></tr><?php endforeach; ?><?php if(!$parqueos): ?><tr><td colspan="5">Aún no hay parqueos.</td></tr><?php endif; ?></tbody></table></div></div></div>
</main></body></html>
