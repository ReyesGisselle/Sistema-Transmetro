<?php
// Copia este archivo como conexion.php y configura tus credenciales locales.
$host = 'TU_SERVIDOR_MYSQL';
$usuario = 'TU_USUARIO_MYSQL';
$base_datos = 'TU_BASE_DE_DATOS';
$contrasena = 'TU_CONTRASENA_MYSQL';
try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$base_datos;charset=utf8mb4",
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('Error de conexión MySQL: ' . $e->getMessage());
    http_response_code(500);
    exit('Error de conexión con la base de datos.');
}
