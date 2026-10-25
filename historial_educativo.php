<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
if (($_SESSION['rol'] ?? '') !== 'administrador') { http_response_code(403); exit('Acceso restringido a administradores.'); }
function h($x) { return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = '';
$ok = isset($_GET['ok']);
$titulo = 'Historial educativo de pilotos';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Solicitud inválida.'); }
 $piloto=filter_var($_POST['piloto'] ?? null,FILTER_VALIDATE_INT);
 $nivel=trim((string)($_POST['nivel'] ?? '')); $institucion=trim((string)($_POST['institucion'] ?? ''));
 $tituloEst=trim((string)($_POST['titulo'] ?? '')); $anio=trim((string)($_POST['anio'] ?? ''));
 if (!$piloto || $nivel==='' || mb_strlen($nivel)>100 || $institucion==='' || mb_strlen($institucion)>160 || mb_strlen($tituloEst)>160 || ($anio!=='' && (!ctype_digit($anio) || (int)$anio<1900 || (int)$anio>(int)date('Y')+1))) $error='Revisa los datos del estudio.';
 else { try {
  $stmt=$conexion->prepare('INSERT INTO historial_educativo_piloto (id_piloto,nivel,institucion,titulo,anio_finalizacion) VALUES (?,?,?,?,?)');
  $stmt->execute([$piloto,$nivel,$institucion,$tituloEst,$anio===''?null:(int)$anio]);
  header('Location: historial_educativo.php?ok=1'); exit;
 } catch (PDOException $e) { error_log($e->getMessage()); $error='No se pudo guardar el historial educativo.'; } }
}
$pilotos=$conexion->query('SELECT id_piloto,nombre FROM pilotos ORDER BY nombre')->fetchAll();
$registros=$conexion->query('SELECT h.*,p.nombre AS piloto FROM historial_educativo_piloto h JOIN pilotos p ON p.id_piloto=h.id_piloto ORDER BY h.id_estudio DESC')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($titulo) ?> | Transmetro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f7fb;color:#23334c}.navbar{background:#14253e}.card{border:0;border-radius:16px;box-shadow:0 6px 24px #14253e13}.btn-brand{background:#f0a72e;color:#14253e;font-weight:700}.btn-brand:hover{background:#e4a029}</style></head><body><nav class="navbar navbar-dark"><div class="container"><a href="panel.php" class="navbar-brand fw-bold">Transmetro Info</a><a href="panel.php" class="btn btn-outline-light btn-sm">Volver al panel</a></div></nav><main class="container py-5"><h1 class="h3 fw-bold mb-2"><?= h($titulo) ?></h1><p class="text-secondary">Módulo administrativo · Proyecto académico</p><?php if ($ok): ?><div class="alert alert-success">Registro guardado correctamente.</div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?><div class="card mb-4"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Nuevo registro</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><div class="col-md-6"><label class="form-label">Piloto</label><select name="piloto" class="form-select" required><option value="">Seleccionar piloto</option><?php foreach($pilotos as $x): ?><option value="<?= (int)$x['id_piloto'] ?>"><?= h($x['nombre']) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Nivel educativo</label><input class="form-control" name="nivel" maxlength="100" placeholder="Ej. Diversificado" required></div><div class="col-md-6"><label class="form-label">Institución</label><input class="form-control" name="institucion" maxlength="160" required></div><div class="col-md-4"><label class="form-label">Título obtenido</label><input class="form-control" name="titulo" maxlength="160"></div><div class="col-md-2"><label class="form-label">Año de finalización</label><input class="form-control" type="number" name="anio" min="1900" max="2100"></div><div class="col-12"><button class="btn btn-brand" type="submit">Guardar registro</button></div></form></div></div><div class="card"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Registros existentes</h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Piloto</th><th>Nivel</th><th>Institución</th><th>Título</th><th>Año</th></tr></thead><tbody>
<?php foreach ($registros as $r): ?>
<tr><td><?= h($r['piloto']) ?></td><td><?= h($r['nivel']) ?></td><td><?= h($r['institucion']) ?></td><td><?= h($r['titulo']) ?></td><td><?= h($r['anio_finalizacion']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$registros): ?><tr><td colspan="5">No hay registros todavía.</td></tr><?php endif; ?>
</tbody></table></div></div></div></main></body></html>