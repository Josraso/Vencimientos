<?php
// debug.php - Archivo para hacer debugging paso a paso
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>?? Debug del Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .debug-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .success { border-left: 5px solid #4caf50; }
        .error { border-left: 5px solid #f44336; background: #ffebee; }
        .warning { border-left: 5px solid #ff9800; background: #fff3e0; }
        .info { border-left: 5px solid #2196f3; background: #e3f2fd; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>?? Sistema de Debug</h1>

    <div class="debug-box info">
        <h3>?? Información del Sistema</h3>
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?></p>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        <p><strong>Script actual:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></p>
        <p><strong>Fecha/Hora:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="debug-box">
        <h3>?? Test 1: Activación de Errores</h3>
        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        echo "<p class='success'>? Errores PHP activados correctamente</p>";
        ?>
    </div>

    <div class="debug-box">
        <h3>?? Test 2: Extensiones PHP</h3>
        <?php
        $extensiones = ['pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'json'];
        foreach ($extensiones as $ext) {
            $loaded = extension_loaded($ext);
            $class = $loaded ? 'success' : 'error';
            $icon = $loaded ? '?' : '?';
            echo "<p class='$class'>$icon $ext: " . ($loaded ? 'INSTALADA' : 'NO INSTALADA') . "</p>";
        }
        ?>
    </div>

    <div class="debug-box">
        <h3>?? Test 3: Archivos del Sistema</h3>
        <?php
        $archivos = [
            'config.php' => 'Configuración principal',
            'index.php' => 'Interfaz principal',
            'ajax.php' => 'Manejador AJAX',
            'cron_notifier.php' => 'Notificaciones automáticas'
        ];
        
        foreach ($archivos as $archivo => $descripcion) {
            $existe = file_exists($archivo);
            $class = $existe ? 'success' : 'error';
            $icon = $existe ? '?' : '?';
            echo "<p class='$class'>$icon $archivo ($descripcion): " . ($existe ? 'EXISTE' : 'NO ENCONTRADO') . "</p>";
            
            if ($existe) {
                $permisos = substr(sprintf('%o', fileperms($archivo)), -4);
                echo "<small>Permisos: $permisos</small><br>";
            }
        }
        ?>
    </div>

    <div class="debug-box">
        <h3>?? Test 4: Test de Conexión a Base de Datos</h3>
        <?php
        echo "<p><strong>Intentando incluir config.php...</strong></p>";
        
        try {
            require_once 'config.php';
            echo "<p class='success'>? config.php incluido correctamente</p>";
        } catch (Exception $e) {
            echo "<p class='error'>? Error incluyendo config.php: " . $e->getMessage() . "</p>";
        } catch (Error $e) {
            echo "<p class='error'>? Error fatal en config.php: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="debug-box">
        <h3>?? Test 5: Variables de Configuración</h3>
        <?php
        if (defined('DB_HOST')) {
            echo "<p class='success'>? DB_HOST: " . DB_HOST . "</p>";
            echo "<p class='success'>? DB_NAME: " . DB_NAME . "</p>";
            echo "<p class='success'>? DB_USER: " . DB_USER . "</p>";
            echo "<p class='info'>?? DB_PASS: " . str_repeat('*', strlen(DB_PASS)) . " (" . strlen(DB_PASS) . " caracteres)</p>";
        } else {
            echo "<p class='error'>? Constantes de BD no definidas</p>";
        }
        ?>
    </div>

    <div class="debug-box">
        <h3>?? Test 6: Conexión PDO</h3>
        <?php
        if (isset($pdo)) {
            echo "<p class='success'>? Variable \$pdo existe</p>";
            
            try {
                $stmt = $pdo->query("SELECT DATABASE() as db_actual");
                $result = $stmt->fetch();
                echo "<p class='success'>? Conexión PDO activa - BD actual: " . $result['db_actual'] . "</p>";
                
                // Test de tablas
                $stmt = $pdo->query("SHOW TABLES");
                $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<p class='success'>? Tablas encontradas (" . count($tablas) . "):</p>";
                echo "<pre>" . implode(', ', $tablas) . "</pre>";
                
                // Test de datos
                foreach (['clientes', 'tipos_servicios', 'configuracion'] as $tabla) {
                    if (in_array($tabla, $tablas)) {
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
                        $count = $stmt->fetch()['total'];
                        echo "<p class='info'>?? $tabla: $count registros</p>";
                    }
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>? Error en consulta PDO: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='error'>? Variable \$pdo no existe</p>";
        }
        ?>
    </div>

    <div class="debug-box">
        <h3>?? Test 7: Memoria y Límites</h3>
        <?php
        echo "<p><strong>Memoria utilizada:</strong> " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB</p>";
        echo "<p><strong>Límite de memoria:</strong> " . ini_get('memory_limit') . "</p>";
        echo "<p><strong>Tiempo límite:</strong> " . ini_get('max_execution_time') . " segundos</p>";
        echo "<p><strong>Tamaño máximo de POST:</strong> " . ini_get('post_max_size') . "</p>";
        echo "<p><strong>Tamaño máximo de archivo:</strong> " . ini_get('upload_max_filesize') . "</p>";
        ?>
    </div>

    <div class="debug-box">
        <h3>?? Test 8: Prueba de index.php</h3>
        <p><strong>Si todos los tests anteriores están OK, prueba estos enlaces:</strong></p>
        <p><a href="index.php" target="_blank" style="color: #2196f3;">?? Abrir index.php en nueva pestaña</a></p>
        <p><a href="ajax.php" target="_blank" style="color: #2196f3;">?? Abrir ajax.php en nueva pestaña</a></p>
        
        <p><strong>Si index.php sigue en blanco:</strong></p>
        <ol>
            <li>Abre las herramientas de desarrollador (F12)</li>
            <li>Ve a la pestaña "Console"</li>
            <li>Busca errores de JavaScript</li>
            <li>Ve a "Network" y recarga para ver errores 500</li>
        </ol>
    </div>

    <div class="debug-box warning">
        <h3>?? Si hay errores:</h3>
        <ol>
            <li><strong>Error de BD:</strong> Cambia las constantes DB_* en config.php</li>
            <li><strong>Archivos no encontrados:</strong> Sube todos los archivos al servidor</li>
            <li><strong>Permisos:</strong> Cambia permisos a 644 para .php y 755 para directorios</li>
            <li><strong>Extensiones PHP:</strong> Instala PDO y PDO_MySQL</li>
            <li><strong>Límites PHP:</strong> Aumenta memory_limit si es necesario</li>
        </ol>
    </div>

    <div class="debug-box info">
        <h3>?? Log de Errores PHP</h3>
        <p>Si existe un archivo error.log en este directorio:</p>
        <?php
        if (file_exists('error.log')) {
            echo "<p class='warning'>?? Archivo error.log encontrado:</p>";
            echo "<pre>" . htmlspecialchars(file_get_contents('error.log')) . "</pre>";
        } else {
            echo "<p class='success'>? No hay archivo error.log (buena señal)</p>";
        }
        ?>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <button onclick="location.reload()" style="padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 5px; cursor: pointer;">
            ?? Recargar Tests
        </button>
    </div>

</body>
</html>