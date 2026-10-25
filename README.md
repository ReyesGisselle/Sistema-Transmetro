# Sistema de Gestión Transmetro

Proyecto académico de gestión de líneas, estaciones, buses, recorridos, accesos, personal y alertas.

## Tecnologías
PHP, MySQL, Bootstrap y JavaScript.

## Demostración web
(https://transmetro-info.infinityfreeapp.com/sistema/login.php)

## Configuración local
1. Importa la estructura SQL de la base de datos (se entrega por separado; este ZIP no incluye un respaldo SQL).
2. Copia `sistema/conexion.example.php` a `sistema/conexion.php`.
3. Sustituye los valores de ejemplo por las credenciales de tu propia base de datos.
4. Abre `sistema/login.php` en un servidor con PHP y MySQL.

**Seguridad:** No subas `conexion.php`, contraseñas, respaldos con datos personales ni archivos temporales de configuración. El archivo `.gitignore` excluye la conexión local. Las cuentas de demostración deben desactivarse o cambiarse al finalizar la presentación.

## Estado
Prototipo académico. Algunos módulos registran y consultan información; no todas las reglas del sistema están automatizadas.
