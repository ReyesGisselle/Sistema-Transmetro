<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (empty($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') { http_response_code(403); exit('Acceso restringido.'); }
function h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf_tm'])) $_SESSION['csrf_tm'] = bin2hex(random_bytes(32));
function check_post() { if (!hash_equals($_SESSION['csrf_tm'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Solicitud inválida'); } }
function num_id($v) { $x=filter_var($v,FILTER_VALIDATE_INT); return $x && $x>0 ? (int)$x : null; }
function opt_id($v) { return ($v === '' || $v === null) ? null : num_id($v); }
function choices($sql) { global $conexion; return $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
function dt($v) { if (!$v) return null; $d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $v); return $d && $d->format('Y-m-d\TH:i') === $v ? $d->format('Y-m-d H:i:s') : false; }
$error='';
$success=isset($_GET['ok']);
$buses=choices('SELECT id_bus,codigo FROM buses ORDER BY codigo');$parqueos=choices('SELECT id_parqueo,nombre FROM parqueos ORDER BY nombre');
if ($_SERVER['REQUEST_METHOD']==='POST') {check_post();$bus=num_id($_POST['bus']??null);$nuevo=num_id($_POST['nuevo']??null);
 if (!$bus || !$nuevo) $error='Selecciona un bus y parqueo.';
 else {try {
  $conexion->beginTransaction();
  $s=$conexion->prepare('SELECT id_parqueo FROM buses WHERE id_bus=? FOR UPDATE');$s->execute([$bus]);$anterior=$s->fetchColumn();
  if ($anterior===false) throw new RuntimeException('Bus no encontrado.');
  if ((int)$anterior===$nuevo) throw new RuntimeException('El bus ya está en ese parqueo.');
  $s=$conexion->prepare('SELECT capacidad FROM parqueos WHERE id_parqueo=? FOR UPDATE');$s->execute([$nuevo]);$cap=$s->fetchColumn();
  if ($cap===false) throw new RuntimeException('Parqueo no encontrado.');
  $s=$conexion->prepare('SELECT COUNT(*) FROM buses WHERE id_parqueo=?');$s->execute([$nuevo]);
  if ((int)$s->fetchColumn()>=(int)$cap) throw new RuntimeException('El parqueo de destino está lleno.');
  $s=$conexion->prepare('UPDATE buses SET id_parqueo=? WHERE id_bus=?');$s->execute([$nuevo,$bus]);
  $s=$conexion->prepare('INSERT INTO historial_parqueo(id_bus,id_parqueo_anterior,id_parqueo_nuevo) VALUES (?,?,?)');$s->execute([$bus,$anterior,$nuevo]);
  $conexion->commit();header('Location: historial_parqueos.php?ok=1');exit;
 }catch(Throwable $e){if($conexion->inTransaction())$conexion->rollBack();error_log($e->getMessage());$error=$e instanceof RuntimeException ? $e->getMessage() : 'No se pudo cambiar el parqueo.';}}
}
$rows=choices('SELECT h.*,b.codigo,p1.nombre AS anterior,p2.nombre AS nuevo FROM historial_parqueo h JOIN buses b ON b.id_bus=h.id_bus LEFT JOIN parqueos p1 ON p1.id_parqueo=h.id_parqueo_anterior JOIN parqueos p2 ON p2.id_parqueo=h.id_parqueo_nuevo ORDER BY h.id_movimiento DESC LIMIT 150');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cambios de parqueo · Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb;color:#1c2d45}.navbar{background:#14253e}.card{border:0;border-radius:18px;box-shadow:0 8px 25px #152e4a13}.btn-brand{background:#f0a72e;font-weight:700}.table{font-size:.91rem}</style></head><body><nav class="navbar navbar-dark"><div class="container"><a class="navbar-brand fw-bold" href="panel.php">Transmetro Info</a><a class="btn btn-outline-light btn-sm" href="panel.php">Volver al panel</a></div></nav><main class="container py-5"><h1 class="h2 fw-bold">Cambios de parqueo</h1><p class="text-secondary">Proyecto académico · Datos de demostración</p><?php if ($success): ?><div class="alert alert-success">Registro guardado correctamente.</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?><div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Nuevo registro</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_tm'])?>"><div class="col-md-6"><label class="form-label">Bus</label><select class="form-select" name="bus" required><option value="">Seleccionar</option><?php foreach ($buses as $v): ?><option value="<?=h($v['id_bus'])?>"><?=h($v['codigo'])?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Nuevo parqueo</label><select class="form-select" name="nuevo" required><option value="">Seleccionar</option><?php foreach ($parqueos as $v): ?><option value="<?=h($v['id_parqueo'])?>"><?=h($v['nombre'])?></option><?php endforeach; ?></select></div><div class="col-12"><div class="alert alert-info mb-0">El cambio actualiza el parqueo del bus y registra el movimiento en una sola transacción.</div></div><div class="col-12"><button class="btn btn-brand">Guardar</button></div></form></div></div><div class="card"><div class="card-body p-4"><h2 class="h5">Registros</h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Fecha</th><th>Bus</th><th>Anterior</th><th>Nuevo</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><?=h($r['fecha_cambio'])?></td><td><?=h($r['codigo'])?></td><td><?=h($r['anterior'])?></td><td><?=h($r['nuevo'])?></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="4">Aún no hay registros.</td></tr><?php endif; ?></tbody></table></div></div></div></main></body></html>