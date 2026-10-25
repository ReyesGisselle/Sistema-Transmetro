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
$recorridos=choices("SELECT r.id_recorrido,CONCAT('Recorrido #',r.id_recorrido,' · ',b.codigo,' · ',l.nombre) AS etiqueta FROM recorridos r JOIN buses b ON b.id_bus=r.id_bus JOIN lineas l ON l.id_linea=r.id_linea ORDER BY r.id_recorrido DESC LIMIT 300");
$estaciones=choices('SELECT id_estacion,nombre FROM estaciones ORDER BY nombre');
if ($_SERVER['REQUEST_METHOD']==='POST') {check_post();
 $rec=num_id($_POST['recorrido']??null);$est=num_id($_POST['estacion']??null);$orden=num_id($_POST['orden']??null);
 $prev=dt($_POST['prevista']??'');$real=dt($_POST['real']??'');$salida=dt($_POST['salida']??'');
 if (!$rec || !$est || !$orden || $prev===false || $real===false || $salida===false || ($real && $salida && $salida<$real)) $error='Revisa el recorrido, orden y horarios.';
 else {try {
  $s=$conexion->prepare('SELECT COUNT(*) FROM recorridos r JOIN linea_estacion le ON le.id_linea=r.id_linea WHERE r.id_recorrido=? AND le.id_estacion=? AND le.orden=?');$s->execute([$rec,$est,$orden]);
  if (!$s->fetchColumn()) $error='La estación y su orden deben coincidir con la ruta configurada para esta línea.';
  else {
   $s=$conexion->prepare('SELECT b.capacidad_maxima,b.pasajeros_actuales FROM recorridos r JOIN buses b ON b.id_bus=r.id_bus WHERE r.id_recorrido=?');$s->execute([$rec]);$bus=$s->fetch(PDO::FETCH_ASSOC);
   if (!$bus || (int)$bus['capacidad_maxima']<1) $error='Bus sin capacidad válida.';
   else { $espera=((int)$bus['pasajeros_actuales']*4 < (int)$bus['capacidad_maxima']) ? 5 : 0;
    $s=$conexion->prepare('INSERT INTO visitas_estacion(id_recorrido,id_estacion,orden_visita,llegada_prevista,llegada_real,salida_real,espera_adicional_min) VALUES (?,?,?,?,?,?,?)');
    $s->execute([$rec,$est,$orden,$prev,$real,$salida,$espera]);header('Location: visitas_buses.php?ok=1');exit;
   }
  }
 }catch(PDOException $e){error_log($e->getMessage());$error='No se pudo registrar la visita. Comprueba que el orden no esté duplicado.';}}
}
$rows=choices('SELECT v.*,e.nombre AS estacion,b.codigo FROM visitas_estacion v JOIN estaciones e ON e.id_estacion=v.id_estacion JOIN recorridos r ON r.id_recorrido=v.id_recorrido JOIN buses b ON b.id_bus=r.id_bus ORDER BY v.id_visita DESC LIMIT 150');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Visitas a estaciones · Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb;color:#1c2d45}.navbar{background:#14253e}.card{border:0;border-radius:18px;box-shadow:0 8px 25px #152e4a13}.btn-brand{background:#f0a72e;font-weight:700}.table{font-size:.91rem}</style></head><body><nav class="navbar navbar-dark"><div class="container"><a class="navbar-brand fw-bold" href="panel.php">Transmetro Info</a><a class="btn btn-outline-light btn-sm" href="panel.php">Volver al panel</a></div></nav><main class="container py-5"><h1 class="h2 fw-bold">Visitas a estaciones</h1><p class="text-secondary">Proyecto académico · Datos de demostración</p><?php if ($success): ?><div class="alert alert-success">Registro guardado correctamente.</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?><div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Nuevo registro</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_tm'])?>"><div class="col-md-6"><label class="form-label">Recorrido</label><select class="form-select" name="recorrido" required><option value="">Seleccionar</option><?php foreach ($recorridos as $v): ?><option value="<?=h($v['id_recorrido'])?>"><?=h($v['etiqueta'])?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Estación</label><select class="form-select" name="estacion" required><option value="">Seleccionar</option><?php foreach ($estaciones as $v): ?><option value="<?=h($v['id_estacion'])?>"><?=h($v['nombre'])?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Orden de visita</label><input class="form-control" type="number" name="orden" min="1" required></div><div class="col-md-6"><label class="form-label">Llegada prevista</label><input class="form-control" type="datetime-local" name="prevista" ></div><div class="col-md-6"><label class="form-label">Llegada real</label><input class="form-control" type="datetime-local" name="real" ></div><div class="col-md-6"><label class="form-label">Salida real</label><input class="form-control" type="datetime-local" name="salida" ></div><div class="col-12"><div class="alert alert-info mb-0">Si la ocupación actual del bus es inferior al 25 %, se registran 5 minutos adicionales de espera. Es una regla demostrativa calculada al guardar la visita.</div></div><div class="col-12"><button class="btn btn-brand">Guardar</button></div></form></div></div><div class="card"><div class="card-body p-4"><h2 class="h5">Registros</h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Recorrido</th><th>Bus</th><th>Estación</th><th>Orden</th><th>Prevista</th><th>Real</th><th>Espera extra</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><?=h($r['id_recorrido'])?></td><td><?=h($r['codigo'])?></td><td><?=h($r['estacion'])?></td><td><?=h($r['orden_visita'])?></td><td><?=h($r['llegada_prevista'])?></td><td><?=h($r['llegada_real'])?></td><td><?=h($r['espera_adicional_min'])?> min</td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="7">Aún no hay registros.</td></tr><?php endif; ?></tbody></table></div></div></div></main></body></html>