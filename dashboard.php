<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (empty($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$admin = ($_SESSION['rol'] ?? '') === 'administrador';
$nombre = $_SESSION['nombre'] ?? 'Usuario';
function total($db, $table) {
    // Table names are constants defined by the application, never user input.
    return (int)$db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}
$stats = [];
foreach (['lineas'=>'Líneas','estaciones'=>'Estaciones','buses'=>'Buses','pilotos'=>'Pilotos','alertas'=>'Alertas'] as $tabla=>$etiqueta) {
    $stats[] = ['label'=>$etiqueta, 'value'=>total($conexion, $tabla)];
}
$reportes = $conexion->query("SELECT l.id_linea, l.nombre, l.estado, l.distancia_total_km,
 (SELECT COUNT(*) FROM linea_estacion le WHERE le.id_linea=l.id_linea) AS estaciones,
 (SELECT COUNT(*) FROM buses b WHERE b.id_linea=l.id_linea) AS buses
 FROM lineas l ORDER BY l.nombre")->fetchAll();
$alertas = $conexion->query("SELECT a.titulo, a.tipo, a.estado, a.fecha, l.nombre AS linea
 FROM alertas a LEFT JOIN lineas l ON l.id_linea=a.id_linea
 ORDER BY a.fecha DESC LIMIT 6")->fetchAll();
$modules = [
 ['label'=>'Líneas','file'=>'lineas.php','icon'=>'🛣️'],
 ['label'=>'Estaciones','file'=>'estaciones.php','icon'=>'📍'],
 ['label'=>'Orden de estaciones','file'=>'recorrido.php','icon'=>'🗺️'],
 ['label'=>'Buses','file'=>'buses.php','icon'=>'🚌'],
 ['label'=>'Parqueos','file'=>'parqueos.php','icon'=>'🅿️'],
 ['label'=>'Pilotos','file'=>'pilotos.php','icon'=>'👨‍✈️'],
 ['label'=>'Historial educativo','file'=>'historial_educativo.php','icon'=>'🎓'],
 ['label'=>'Guardias','file'=>'guardias.php','icon'=>'🛡️'],
 ['label'=>'Accesos','file'=>'accesos.php','icon'=>'🚪'],
 ['label'=>'Operadores','file'=>'operadores.php','icon'=>'👥'],
 ['label'=>'Equipos','file'=>'equipos_estacion.php','icon'=>'💻'],
 ['label'=>'Recorridos de buses','file'=>'recorridos_buses.php','icon'=>'🧭'],
 ['label'=>'Visitas de buses','file'=>'visitas_buses.php','icon'=>'🕒'],
 ['label'=>'Alertas','file'=>'alertas_admin.php','icon'=>'⚠️'],
 ['label'=>'Historial de parqueos','file'=>'historial_parqueos.php','icon'=>'🔄'],
 ['label'=>'Conectividad','file'=>'conectividad.php','icon'=>'🌐']
];
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Panel | Transmetro Info</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{--navy:#152a46;--gold:#eeb44b;--ink:#24344a}body{background:#f4f6fb;color:var(--ink);font-family:system-ui,-apple-system,Segoe UI,sans-serif}
.sidebar{background:var(--navy);color:#fff;min-height:100vh}.sidebar a{color:#e5edf8;text-decoration:none;display:block;padding:10px 14px;border-radius:10px}.sidebar a:hover{background:#ffffff18;color:#fff}
.brand{font-size:1.3rem;font-weight:800}.brand span{color:var(--gold)}.card{border:0;border-radius:17px;box-shadow:0 7px 24px #1c2e4a0c}.stat{font-size:2rem;font-weight:800}.muted{color:#748198}.tag{background:#eaf0fa;border-radius:20px;padding:4px 9px;font-size:.8rem}
@media(min-width:992px){.sidebar{position:sticky;top:0;height:100vh;overflow-y:auto}}
</style></head><body>
<div class="container-fluid"><div class="row">
<aside class="col-lg-3 col-xl-2 sidebar p-4">
<div class="brand mb-2">Transmetro <span>Info</span></div><div class="small opacity-75 mb-4">Proyecto académico · Demostración</div>
<a href="dashboard.php">📊 Panel principal</a><a href="#reportes">📑 Reportes</a><a href="#avisos">🔔 Avisos</a>
<?php if ($admin): ?><hr class="border-light opacity-25"><div class="small text-uppercase opacity-75 mb-2">Administración</div>
<?php foreach($modules as $m): ?><a href="<?=h($m['file'])?>"><?=h($m['icon'].' '.$m['label'])?></a><?php endforeach; ?>
<?php endif; ?>
<hr class="border-light opacity-25"><a href="logout.php">↪ Cerrar sesión</a>
</aside>
<main class="col-lg-9 col-xl-10 px-3 px-md-5 py-4 py-md-5">
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4"><div><div class="small muted">SISTEMA INTELIGENTE DE INFORMACIÓN Y MONITOREO</div><h1 class="fw-bold mt-2">¡Bienvenida, <?=h($nombre)?>!</h1><p class="muted">Consulta la información registrada en MySQL desde un solo lugar.</p></div><span class="tag"><?= $admin?'Administrador':'Usuario de consulta' ?></span></div>
<div class="alert alert-warning border-0 rounded-4">Datos de demostración. Este proyecto no es un sitio oficial ni proporciona ubicación o tiempos reales de Transmetro.</div>
<div class="row g-3 mb-5"><?php foreach($stats as $s): ?><div class="col-6 col-xl"><div class="card h-100"><div class="card-body"><div class="small muted"><?=h($s['label'])?></div><div class="stat"><?=h($s['value'])?></div></div></div></div><?php endforeach; ?></div>
<section id="reportes"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h2 class="h4 fw-bold m-0">Reporte de líneas, estaciones y buses</h2><button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Imprimir reporte</button></div>
<div class="card mb-5"><div class="card-body p-4"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Línea</th><th>Estaciones</th><th>Buses asignados</th><th>Distancia (km)</th><th>Estado</th><th>Validación de flota</th></tr></thead><tbody>
<?php foreach($reportes as $r): $n=(int)$r['estaciones']; $b=(int)$r['buses']; $ok=$n>0 && $b >= $n && $b <= 2*$n; ?>
<tr><td class="fw-semibold"><?=h($r['nombre'])?></td><td><?=$n?></td><td><?=$b?></td><td><?=h($r['distancia_total_km'])?></td><td><?=h($r['estado'])?></td><td><span class="badge <?=$ok?'text-bg-success':'text-bg-warning'?>"><?=$ok?'Cumple':'Pendiente'?></span></td></tr>
<?php endforeach; ?><?php if(!$reportes): ?><tr><td colspan="6" class="text-secondary">Aún no hay líneas registradas.</td></tr><?php endif; ?></tbody></table></div>
<p class="small muted mb-0">Regla de flota: mínimo un bus por estación y máximo dos. El reporte señala incumplimientos; no reemplaza la validación al editar o asignar unidades.</p></div></div></section>
<section id="avisos"><h2 class="h4 fw-bold mb-3">Avisos recientes</h2><div class="row g-3"><?php foreach($alertas as $a): ?><div class="col-md-6"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between gap-2"><strong><?=h($a['titulo'])?></strong><span class="badge text-bg-light"><?=h($a['estado'])?></span></div><div class="small muted mt-2"><?=h($a['linea'] ?: 'General')?> · <?=h($a['tipo'])?> · <?=h($a['fecha'])?></div></div></div></div><?php endforeach; ?><?php if(!$alertas): ?><div class="col-12"><p class="muted">Todavía no hay avisos registrados.</p></div><?php endif; ?></div></section>
<footer class="mt-5 small muted">Proyecto académico · Transmetro Info · No oficial</footer>
</main></div></div>
</body></html>
