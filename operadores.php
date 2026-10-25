<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
if (($_SESSION['rol'] ?? '') !== 'administrador') { http_response_code(403); exit('Acceso restringido a administradores.'); }
function h($x) { return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = '';
$ok = isset($_GET['ok']);
$titulo = 'Operadores de estaciones';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Solicitud inválida.'); }
 $nombre=trim((string)($_POST['nombre'] ?? '')); $telefono=trim((string)($_POST['telefono'] ?? ''));
 $usuario=trim((string)($_POST['usuario'] ?? ''));
 if ($nombre==='' || mb_strlen($nombre)>160 || mb_strlen($telefono)>35 || ($usuario!=='' && (!ctype_digit($usuario) || (int)$usuario<1))) $error='Revisa los datos del operador.';
 else { try {
  $stmt=$conexion->prepare('INSERT INTO operadores (id_usuario,nombre,telefono) VALUES (?,?,?)');
  $stmt->execute([$usuario===''?null:(int)$usuario,$nombre,$telefono]);
  header('Location: operadores.php?ok=1'); exit;
 } catch (PDOException $e) { error_log($e->getMessage()); $error='No se pudo guardar el operador. Verifica el usuario seleccionado.'; } }
}
$usuarios=$conexion->query("SELECT id_usuario,nombre,correo FROM usuarios WHERE activo=1 ORDER BY nombre")->fetchAll();
$registros=$conexion->query('SELECT o.*,u.correo AS correo_usuario FROM operadores o LEFT JOIN usuarios u ON u.id_usuario=o.id_usuario ORDER BY o.id_operador DESC')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($titulo) ?> | Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb;color:#23334c}.navbar{background:#14253e}.card{border:0;border-radius:16px;box-shadow:0 6px 24px #14253e13}.btn-brand{background:#f0a72e;color:#14253e;font-weight:700}.btn-brand:hover{background:#e4a029}</style></head><body><nav class="navbar navbar-dark"><div class="container"><a href="panel.php" class="navbar-brand fw-bold">Transmetro Info</a><a href="panel.php" class="btn btn-outline-light btn-sm">Volver al panel</a></div></nav><main class="container py-5"><h1 class="h3 fw-bold mb-2"><?= h($titulo) ?></h1><p class="text-secondary">Módulo administrativo · Proyecto académico</p><?php if ($ok): ?><div class="alert alert-success">Registro guardado correctamente.</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?><div class="card mb-4"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Nuevo registro</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><div class="col-md-6"><label class="form-label">Nombre del operador</label><input name="nombre" maxlength="160" class="form-control" required></div><div class="col-md-6"><label class="form-label">Teléfono</label><input name="telefono" maxlength="35" class="form-control"></div><div class="col-12"><label class="form-label">Cuenta de usuario (opcional)</label><select name="usuario" class="form-select"><option value="">Sin cuenta vinculada</option><?php foreach($usuarios as $x): ?><option value="<?= (int)$x['id_usuario'] ?>"><?= h($x['nombre'].' - '.$x['correo']) ?></option><?php endforeach; ?></select><div class="form-text">El operador puede registrarse sin cuenta. Si seleccionas una, se vincula a su usuario.</div></div><div class="col-12"><button class="btn btn-brand" type="submit">Guardar registro</button></div></form></div></div><div class="card"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Registros existentes</h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>ID</th><th>Operador</th><th>Teléfono</th><th>Cuenta vinculada</th></tr></thead><tbody>
<?php foreach ($registros as $r): ?>
<tr><td><?= h($r['id_operador']) ?></td><td><?= h($r['nombre']) ?></td><td><?= h($r['telefono']) ?></td><td><?= h($r['correo_usuario']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$registros): ?><tr><td colspan="4">No hay registros todavía.</td></tr><?php endif; ?>
</tbody></table></div></div></div></main></body></html>