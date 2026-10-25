<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['id_usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') { http_response_code(403); exit('Acceso no autorizado'); }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = '';
$success = isset($_GET['ok']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Solicitud inválida'); }
 try {
  $accion=(string)($_POST['accion']??'');
  if ($accion==='crear') {
   $estacion=filter_var($_POST['id_estacion']??null,FILTER_VALIDATE_INT);
   $nombre=trim((string)($_POST['nombre']??'')); $descripcion=trim((string)($_POST['descripcion']??''));
   $guardia=filter_var($_POST['id_guardia']??null,FILTER_VALIDATE_INT);
   $turno=trim((string)($_POST['turno']??'Diurno'));
   if (!$estacion || !$guardia || $nombre==='' || mb_strlen($nombre)>160 || mb_strlen($descripcion)>255 || $turno==='' || mb_strlen($turno)>60) { $error='Completa estación, nombre y guardia asignado.'; }
   else {
    $conexion->beginTransaction();
    try {
     $s=$conexion->prepare('SELECT COUNT(*) FROM guardias WHERE id_guardia=? AND activo=1'); $s->execute([$guardia]);
     if (!(int)$s->fetchColumn()) throw new RuntimeException('El guardia no existe o está inactivo.');
     $s=$conexion->prepare('INSERT INTO accesos (id_estacion,nombre,descripcion) VALUES (?,?,?)'); $s->execute([$estacion,$nombre,$descripcion?:null]);
     $id=(int)$conexion->lastInsertId();
     $s=$conexion->prepare('INSERT INTO acceso_guardia (id_acceso,id_guardia,turno) VALUES (?,?,?)'); $s->execute([$id,$guardia,$turno]);
     $conexion->commit(); header('Location: accesos.php?ok=1'); exit;
    } catch (Throwable $e) { $conexion->rollBack(); error_log($e->getMessage()); $error='No se pudo crear el acceso. Comprueba la estación y el guardia.'; }
   }
  } elseif ($accion==='asignar') {
   $acceso=filter_var($_POST['id_acceso']??null,FILTER_VALIDATE_INT);
   $guardia=filter_var($_POST['id_guardia']??null,FILTER_VALIDATE_INT);
   $turno=trim((string)($_POST['turno']??'Diurno'));
   if (!$acceso || !$guardia || $turno==='' || mb_strlen($turno)>60) $error='Selecciona un acceso, un guardia y un turno válidos.';
   else { $s=$conexion->prepare('INSERT INTO acceso_guardia (id_acceso,id_guardia,turno) VALUES (?,?,?)'); $s->execute([$acceso,$guardia,$turno]); header('Location: accesos.php?ok=1'); exit; }
  } else $error='Acción no reconocida.';
 } catch (PDOException $e) { error_log($e->getMessage()); $error = 'No se pudo guardar. Revisa los datos o si el registro ya existe.'; }
}
$estaciones=$conexion->query('SELECT id_estacion,nombre FROM estaciones ORDER BY nombre')->fetchAll();
$guardias=$conexion->query('SELECT id_guardia,nombre FROM guardias WHERE activo=1 ORDER BY nombre')->fetchAll();
$accesos=$conexion->query('SELECT a.id_acceso,a.nombre,e.nombre AS estacion FROM accesos a JOIN estaciones e ON e.id_estacion=a.id_estacion ORDER BY e.nombre,a.nombre')->fetchAll();
$asignaciones=$conexion->query('SELECT a.nombre AS acceso,e.nombre AS estacion,g.nombre AS guardia,ag.turno FROM acceso_guardia ag JOIN accesos a ON a.id_acceso=ag.id_acceso JOIN estaciones e ON e.id_estacion=a.id_estacion JOIN guardias g ON g.id_guardia=ag.id_guardia ORDER BY e.nombre,a.nombre,g.nombre')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Accesos y asignación de guardias | Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb;color:#243044}.navbar{background:#14253e}.card{border:0;border-radius:16px;box-shadow:0 6px 22px #14253e13}.btn-brand{background:#f0a72e;color:#14253e;font-weight:700}.btn-brand:hover{background:#e9a01d}</style></head><body><nav class="navbar navbar-dark"><div class="container"><a class="navbar-brand fw-bold" href="panel.php">Transmetro Info</a><a class="btn btn-outline-light btn-sm" href="panel.php">Volver al panel</a></div></nav><main class="container py-5"><h1 class="h3 fw-bold">Accesos y asignación de guardias</h1><p class="text-secondary">Módulo administrativo · Proyecto académico</p><?php if ($success): ?><div class="alert alert-success">Registro guardado correctamente.</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?><div class="alert alert-info">Cada acceso nuevo debe tener al menos un guardia asignado. Registra primero estaciones y guardias.</div><div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Crear acceso con guardia</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="accion" value="crear"><div class="col-md-6"><label class="form-label">Estación *</label><select name="id_estacion" class="form-select" required><option value="">Seleccionar</option><?php foreach($estaciones as $r): ?><option value="<?= (int)$r['id_estacion']?>"><?=h($r['nombre'])?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Nombre del acceso *</label><input name="nombre" maxlength="160" class="form-control" placeholder="Ej. Acceso norte" required></div><div class="col-md-12"><label class="form-label">Descripción</label><input name="descripcion" maxlength="255" class="form-control"></div><div class="col-md-6"><label class="form-label">Guardia inicial *</label><select name="id_guardia" class="form-select" required><option value="">Seleccionar</option><?php foreach($guardias as $r): ?><option value="<?= (int)$r['id_guardia']?>"><?=h($r['nombre'])?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Turno</label><input name="turno" value="Diurno" maxlength="60" class="form-control" required></div><div class="col-12"><button class="btn btn-brand">Crear acceso y asignar guardia</button></div></form></div></div><div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Asignar otro guardia a un acceso</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="accion" value="asignar"><div class="col-md-4"><label class="form-label">Acceso</label><select name="id_acceso" class="form-select" required><option value="">Seleccionar</option><?php foreach($accesos as $r): ?><option value="<?= (int)$r['id_acceso']?>"><?=h($r['estacion'].' - '.$r['nombre'])?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Guardia</label><select name="id_guardia" class="form-select" required><option value="">Seleccionar</option><?php foreach($guardias as $r): ?><option value="<?= (int)$r['id_guardia']?>"><?=h($r['nombre'])?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Turno</label><input name="turno" value="Nocturno" maxlength="60" class="form-control" required></div><div class="col-12"><button class="btn btn-outline-primary">Asignar guardia</button></div></form></div></div><div class="card"><div class="card-body p-4"><h2 class="h5">Guardias por acceso</h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Estación</th><th>Acceso</th><th>Guardia</th><th>Turno</th></tr></thead><tbody><?php foreach($asignaciones as $r): ?><tr><td><?=h($r['estacion'])?></td><td><?=h($r['acceso'])?></td><td><?=h($r['guardia'])?></td><td><?=h($r['turno'])?></td></tr><?php endforeach; ?><?php if(!$asignaciones): ?><tr><td colspan="4">Todavía no hay asignaciones.</td></tr><?php endif; ?></tbody></table></div></div></div></main></body></html>