
<?php
session_start();
require_once 'conexion.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Ingresa un correo y contraseña válidos.';
    } else {
        $stmt = $conexion->prepare(
            "SELECT id_usuario, nombre, correo, password_hash, rol
             FROM usuarios
             WHERE correo = ? AND activo = 1
             LIMIT 1"
        );
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Transmetro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #14253e, #294b74);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 15px 40px #0003;
        }
        .btn-transmetro {
            background: #f0a72e;
            color: #14253e;
            font-weight: bold;
        }
        .btn-transmetro:hover {
            background: #df961b;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="fw-bold">Transmetro Info</h2>
        <p class="text-muted">Sistema de Información y Monitoreo</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="correo" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-transmetro w-100">
            Iniciar sesión
        </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        Proyecto académico · No es un sitio oficial
    </p>
</div>
</body>
</html>
