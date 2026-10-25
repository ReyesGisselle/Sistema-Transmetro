<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') { http_response_code(403); exit('Acceso no autorizado.'); }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Solicitud inválida.'); }
    $codigo = trim((string)($_POST['codigo'] ?? ''));
    $placa = trim((string)($_POST['placa'] ?? ''));
    $linea = ($_POST['id_linea'] ?? '') === '' ? null : filter_var($_POST['id_linea'], FILTER_VALIDATE_INT);
    $parqueo = filter_var($_POST['id_parqueo'] ?? null, FILTER_VALIDATE_INT);
    $piloto = ($_POST['id_piloto'] ?? '') === '' ? null : filter_var($_POST['id_piloto'], FILTER_VALIDATE_INT);
    $capacidad = filter_var($_POST['capacidad_maxima'] ?? null, FILTER_VALIDATE_INT);
    $pasajeros = filter_var($_POST['pasajeros_actuales'] ?? null, FILTER_VALIDATE_INT);
    $estado = (string)($_POST['estado'] ?? 'activo');
    if ($codigo === '' || mb_strlen($codigo) > 50 || mb_strlen($placa) > 30 || !$parqueo ||
        ($linea === false) || ($piloto === false) || $capacidad === false || $capacidad < 1 ||
        $pasajeros === false || $pasajeros < 0 || $pasajeros > $capacidad ||
        !in_array($estado, ['activo','mantenimiento','fuera_servicio'], true)) {
        $error = 'Revisa los datos del bus y su capacidad.';
    } else {
        try {
            $conexion->beginTransaction();
            // Serializar altas para no superar el límite del parqueo o de la línea.
            $lock = $conexion->query('SELECT id_municipalidad FROM municipalidades ORDER BY id_municipalidad LIMIT 1 FOR UPDATE')->fetchColumn();
            if (!$lock) throw new RuntimeException('Registra primero una municipalidad.');
            $q = $conexion->prepare('SELECT p.capacidad, (SELECT COUNT(*) FROM buses b WHERE b.id_parqueo=p.id_parqueo) AS ocupados FROM parqueos p WHERE p.id_parqueo=?');
            $q->execute([$parqueo]); $p = $q->fetch();
            if (!$p) throw new RuntimeException('El parqueo no existe.');
            if ((int)$p['ocupados'] >= (int)$p['capacidad']) throw new RuntimeException('El parqueo está lleno.');
            if ($linea !== null) {
                $q = $conexion->prepare('SELECT (SELECT COUNT(*) FROM linea_estacion WHERE id_linea=?) AS estaciones, (SELECT COUNT(*) FROM buses WHERE id_linea=?) AS buses, (SELECT COUNT(*) FROM lineas WHERE id_linea=?) AS existe');
                $q->execute([$linea,$linea,$linea]); $l = $q->fetch();
                if (!(int)$l['existe']) throw new RuntimeException('La línea no existe.');
                if ((int)$l['estaciones'] < 1) throw new RuntimeException('Asigna primero estaciones a esta línea.');
                if ((int)$l['buses'] >= 2*(int)$l['estaciones']) throw new RuntimeException('La línea alcanzó el máximo de buses permitido.');
            }
            $stmt = $conexion->prepare('INSERT INTO buses (codigo,placa,id_linea,id_parqueo,id_piloto,capacidad_maxima,pasajeros_actuales,estado) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$codigo,$placa === '' ? null : $placa,$linea,$parqueo,$piloto,$capacidad,$pasajeros,$estado]);
            $conexion->commit();
            header('Location: buses.php?guardado=1'); exit;
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            if ($e instanceof PDOException) { error_log($e->getMessage()); $error='No se pudo guardar. Revisa que el código no esté repetido.'; }
            else $error=$e->getMessage();
        }
    }
}
$lineas = $conexion->query('SELECT id_linea,nombre FROM lineas ORDER BY nombre')->fetchAll();
$parqueos = $conexion->query('SELECT p.id_parqueo,p.nombre,e.nombre AS estacion FROM parqueos p JOIN estaciones e ON e.id_estacion=p.id_estacion ORDER BY p.nombre')->fetchAll();
$pilotos = $conexion->query('SELECT id_piloto,nombre FROM pilotos ORDER BY nombre')->fetchAll();
$buses = $conexion->query('SELECT b.*,l.nombre AS linea,p.nombre AS parqueo,pi.nombre AS piloto FROM buses b LEFT JOIN lineas l ON l.id_linea=b.id_linea JOIN parqueos p ON p.id_parqueo=b.id_parqueo LEFT JOIN pilotos pi ON pi.id_piloto=b.id_piloto ORDER BY b.id_bus DESC')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Buses | Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb}.navbar{background:#14253e}.card{border:0;border-radius:16px;box-shadow:0 6px 22px #14253e12}.btn-brand{background:#f0a72e;color:#14253e;font-weight:700}</style></head><body>
<nav class="navbar navbar-dark"><div class="container"><a class="navbar-brand" href="panel.php">Transmetro Info</a><a class="btn btn-outline-light btn-sm" href="panel.php">Volver al panel</a></div></nav>
<main class="container py-5"><h1 class="h2 fw-bold">Flota de Transmetros</h1><p class="text-secondary">Cada bus debe tener un parqueo. Su asignación a una línea es opcional.</p>
<?php if(isset($_GET['guardado'])): ?><div class="alert alert-success">Bus registrado correctamente.</div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>
<div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Registrar bus</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>">
<div class="col-md-4"><label class="form-label">Código</label><input class="form-control" name="codigo" maxlength="50" placeholder="TM-001" required></div>
<div class="col-md-4"><label class="form-label">Placa</label><input class="form-control" name="placa" maxlength="30"></div>
<div class="col-md-4"><label class="form-label">Línea asignada</label><select class="form-select" name="id_linea"><option value="">Sin asignar</option><?php foreach($lineas as $l): ?><option value="<?=(int)$l['id_linea']?>"><?=h($l['nombre'])?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Parqueo obligatorio</label><select class="form-select" name="id_parqueo" required><option value="">Selecciona parqueo</option><?php foreach($parqueos as $p): ?><option value="<?=(int)$p['id_parqueo']?>"><?=h($p['nombre'].' — '.$p['estacion'])?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Piloto</label><select class="form-select" name="id_piloto"><option value="">Pendiente de asignación</option><?php foreach($pilotos as $p): ?><option value="<?=(int)$p['id_piloto']?>"><?=h($p['nombre'])?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="estado"><option value="activo">Activo</option><option value="mantenimiento">Mantenimiento</option><option value="fuera_servicio">Fuera de servicio</option></select></div>
<div class="col-md-6"><label class="form-label">Capacidad máxima de pasajeros</label><input class="form-control" type="number" name="capacidad_maxima" min="1" required></div>
<div class="col-md-6"><label class="form-label">Pasajeros actuales (demo)</label><input class="form-control" type="number" name="pasajeros_actuales" min="0" value="0" required></div>
<div class="col-12"><button class="btn btn-brand">Guardar bus</button></div></form></div></div>
<div class="card"><div class="card-body p-4"><h2 class="h5">Buses registrados</h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Código</th><th>Placa</th><th>Línea</th><th>Parqueo</th><th>Piloto</th><th>Pasajeros</th><th>Estado</th></tr></thead><tbody><?php foreach($buses as $b): ?><tr><td><?=h($b['codigo'])?></td><td><?=h($b['placa'])?></td><td><?=h($b['linea'] ?? 'Sin asignar')?></td><td><?=h($b['parqueo'])?></td><td><?=h($b['piloto'] ?? 'Pendiente')?></td><td><?=(int)$b['pasajeros_actuales']?> / <?=(int)$b['capacidad_maxima']?></td><td><?=h($b['estado'])?></td></tr><?php endforeach; ?><?php if(!$buses): ?><tr><td colspan="7">Aún no hay buses registrados.</td></tr><?php endif; ?></tbody></table></div></div></div>
</main></body></html>
