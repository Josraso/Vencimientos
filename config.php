<?php
// config.php - Configuración de la base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'vencidos');
define('DB_USER', 'root');
define('DB_PASS', 'root');

$pdo = null;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("<h2>Error de conexión a la base de datos:</h2><p>" . $e->getMessage() . "</p><p>Revisa los datos de conexión en config.php</p>");
}

// Función para obtener configuración
function getConfig($clave) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = ?");
        $stmt->execute([$clave]);
        $result = $stmt->fetch();
        return $result ? $result['valor'] : null;
    } catch(PDOException $e) {
        return null;
    }
}

// Función para actualizar configuración
function updateConfig($clave, $valor) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        return $stmt->execute([$valor, $clave]);
    } catch(PDOException $e) {
        return false;
    }
}
?>