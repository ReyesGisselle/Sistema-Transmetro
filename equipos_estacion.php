<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
if (($_SESSION['rol'] ?? '') !== 'administrador') { http_response_code(403); exit('Acceso restringido a administradores.'); }
function h($x) { return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = '';
$ok = isset($_GET['ok']);
$titulo = 'Equipos de estaciones';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Solicitud inválida.'); }
 $estacion=filter_var($_POST['estacion'] ?? null,FILTER_VALIDATE_INT);
 $operador=trim((string)($_POST['operador'] ?? ''));
 $codigo=trim((string)($_POST['codigo'] ?? '')); $estado=(string)($_POST['estado'] ?? 'operativo');
 if (!$estacion || ($operador!=='' && (!ctype_digit($operador) || (int)$operador<1)) || $codigo==='' || mb_strlen($codigo)>90 || !in_array($estado,['operativo','mantenimiento','baja'],true)) $error='Revisa los datos del equipo.';
 else { try {
  $stmt=$conexion->prepare('INSERT INTO equipos_estacion (id_estacion,id_operador,codigo_inventario,estado) VALUES (?,?,?,?)');
  $stmt->execute([$estacion,$operador===''?null:(int)$operador,$codigo,$estado]);
  header('Location: equipos_estacion.php?ok=1'); exit;
 } catch (PDOException $e) { error_log($e->getMessage()); $error='No se pudo guardar. Revisa si el código de inventario ya existe.'; } }
}
$estaciones=$conexion->query('SELECT id_estacion,nombre FROM estaciones ORDER BY nombre')->fetchAll();
$operadores=$conexion->query('SELECT id_operador,nombre FROM operadores ORDER BY nombre')->fetchAll();
$registros=$conexion->query('SELECT q.*,e.nombre AS estacion,o.nombre AS operador FROM equipos_estacion q JOIN estaciones e ON e.id_estacion=q.id_estacion LEFT JOIN operadores o ON o.id_operador=q.id_operador ORDER BY q.id_equipo DESC')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($titulo) ?> | Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb;color:#23334c}.navbar{background:#14253e}.card{border:0;border-radius:16px;box-shadow:0 6px 24px #14253e13}.btn-brand{background:#f0a72e;color:#14253e;font-weight:700}.btn-brand:hover{background:#e4a029}</style></head><body><nav class="navbar navbar-dark"><div class="container"><a href="panel.php" class="navbar-brand fw-bold">Transmetro Info</a><a href="panel.php" class="btn btn-outline-light btn-sm">Volver al panel</a></div></nav><main class="container py-5"><h1 class="h3 fw-bold mb-2"><?= h($titulo) ?></h1><p class="text-secondary">Módulo administrativo · Proyecto académico</p><?php if ($ok): ?><div class="alert alert-success">Registro guardado correctamente.</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?><div class="card mb-4"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Nuevo registro</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><div class="col-md-6"><label class="form-label">Estación</label><select name="estacion" class="form-select" required><option value="">Seleccionar estación</option><?php foreach($estaciones as $x): ?><option value="<?= (int)$x['id_estacion'] ?>"><?= h($x['nombre']) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Operador responsable (opcional)</label><select name="operador" class="form-select"><option value="">Pendiente de asignación</option><?php foreach($operadores as $x): ?><option value="<?= (int)$x['id_operador'] ?>"><?= h($x['nombre']) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Código de inventario</label><input name="codigo" maxlength="90" class="form-control" placeholder="Ej. PC-EST-001" required></div><div class="col-md-6"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="operativo">Operativo</option><option value="mantenimiento">Mantenimiento</option><option value="baja">Baja</option></select></div><div class="col-12"><button class="btn btn-brand" type="submit">Guardar registro</button></div></form></div></div><div class="card"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Registros existentes</h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Inventario</th><th>Estación</th><th>Operador</th><th>Estado</th></tr></thead><tbody>
<?php foreach ($registros as $r): ?>
<tr><td><?= h($r['codigo_inventario']) ?></td><td><?= h($r['estacion']) ?></td><td><?= h($r['operador']) ?></td><td><?= h($r['estado']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$registros): ?><tr><td colspan="4">No hay registros todavía.</td></tr><?php endif; ?>
</tbody></table></div></div></div></main></body></html>