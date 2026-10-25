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
  $nombre=trim((string)($_POST['nombre']??'')); $telefono=trim((string)($_POST['telefono']??''));
  if ($nombre==='' || mb_strlen($nombre)>160 || mb_strlen($telefono)>35) { $error='Revisa los datos del guardia.'; }
  else { $s=$conexion->prepare('INSERT INTO guardias (nombre,telefono,activo) VALUES (?,?,1)'); $s->execute([$nombre,$telefono?:null]); header('Location: guardias.php?ok=1'); exit; }
 } catch (PDOException $e) { error_log($e->getMessage()); $error = 'No se pudo guardar. Revisa los datos o si el registro ya existe.'; }
}
$rows=$conexion->query('SELECT id_guardia,nombre,telefono,activo FROM guardias ORDER BY id_guardia DESC')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Registro de guardias | Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb;color:#243044}.navbar{background:#14253e}.card{border:0;border-radius:16px;box-shadow:0 6px 22px #14253e13}.btn-brand{background:#f0a72e;color:#14253e;font-weight:700}.btn-brand:hover{background:#e9a01d}</style></head><body><nav class="navbar navbar-dark"><div class="container"><a class="navbar-brand fw-bold" href="panel.php">Transmetro Info</a><a class="btn btn-outline-light btn-sm" href="panel.php">Volver al panel</a></div></nav><main class="container py-5"><h1 class="h3 fw-bold">Registro de guardias</h1><p class="text-secondary">Módulo administrativo · Proyecto académico</p><?php if ($success): ?><div class="alert alert-success">Registro guardado correctamente.</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?><div class="card mb-4"><div class="card-body p-4"><h2 class="h5">Nuevo guardia</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><div class="col-md-6"><label class="form-label">Nombre completo *</label><input name="nombre" class="form-control" maxlength="160" required></div><div class="col-md-6"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" maxlength="35"></div><div class="col-12"><button class="btn btn-brand">Guardar guardia</button></div></form></div></div><div class="card"><div class="card-body p-4"><h2 class="h5">Guardias registrados</h2><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Estado</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= (int)$r['id_guardia']?></td><td><?=h($r['nombre'])?></td><td><?=h($r['telefono'])?></td><td><?= (int)$r['activo']===1?'Activo':'Inactivo'?></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="4">Sin guardias registrados.</td></tr><?php endif; ?></tbody></table></div></div></div></main></body></html>