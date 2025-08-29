<?php
require_once 'config.php';
require_once 'includes/data_logic.php';

$mensaje = '';
$error = '';

// Procesar formularios POST
if ($_POST) {
    try {
        $resultado = procesarFormulario($_POST, $pdo);
        if ($resultado) {
            $mensaje = $resultado;
        }
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Cargar todos los datos necesarios
$datos = cargarDatosSistema($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Servicios Profesional</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/alerts.php'; ?>
        <?php include 'includes/tabs.php'; ?>
        <?php include 'includes/tab_content.php'; ?>
    </div>

    <?php include 'includes/modals.php'; ?>
    
    <script>
        // Datos iniciales para JavaScript
        const DATOS_SISTEMA = <?php echo json_encode($datos); ?>;
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>