<?php
// cron_notifier.php - Sistema de notificaciones corregido
require_once 'config.php';

// Activar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log de actividad
$log_file = 'logs/cron_notifier.log';
$log_dir = dirname($log_file);

if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage; // También mostrar en pantalla para debugging
}

writeLog("=== INICIO PROCESO NOTIFICACIONES ===");

try {
    // Obtener configuración
    $dias_aviso = getConfig('dias_aviso') ?: 5;
    $email_admin = getConfig('email_admin');
    $emails_copia = getConfig('emails_copia');
    $smtp_host = getConfig('smtp_host') ?: 'localhost';
    $smtp_port = getConfig('smtp_port') ?: 25;
    $smtp_user = getConfig('smtp_user');
    $smtp_pass = getConfig('smtp_pass');

    writeLog("Configuración cargada - Días aviso: $dias_aviso, Email: $email_admin");

    if (empty($email_admin)) {
        throw new Exception("Email administrativo no configurado");
    }

    // Función mejorada para enviar email
    function enviarEmailSMTP($to, $subject, $htmlMessage, $from_email, $from_name = '') {
        global $smtp_host, $smtp_port, $smtp_user, $smtp_pass;
        
        // Si no hay configuración SMTP, usar mail() básico
        if (empty($smtp_host) || $smtp_host === 'localhost') {
            $headers = [
                "MIME-Version: 1.0",
                "Content-type: text/html; charset=UTF-8",
                "From: $from_name <$from_email>",
                "Reply-To: $from_email",
                "X-Mailer: PHP/" . phpversion()
            ];
            
            $success = mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
            writeLog($success ? "Mail enviado a: $to" : "Error mail a: $to");
            return $success;
        }

        // Usar PHPMailer si está disponible o SMTP básico
        // Por simplicidad, usar mail() por ahora
        return enviarEmailBasico($to, $subject, $htmlMessage, $from_email, $from_name);
    }

    function enviarEmailBasico($to, $subject, $htmlMessage, $from_email, $from_name = '') {
        $headers = [
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=UTF-8",
            "From: $from_name <$from_email>",
            "Reply-To: $from_email",
            "X-Mailer: Sistema Gestion Servicios"
        ];
        
        $success = mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
        writeLog($success ? "Email enviado a: $to" : "Error enviando a: $to");
        return $success;
    }

    // Obtener servicios próximos a vencer
    $fecha_limite = date('Y-m-d', strtotime("+$dias_aviso days"));
    
    $stmt = $pdo->prepare("
        SELECT sc.*, c.nombre, c.apellidos, c.empresa, c.email, c.telefono,
               ts.nombre as servicio_nombre, ts.descripcion, 
               COALESCE(sc.precio_personalizado, ts.precio) as precio_final,
               tv.nombre as vencimiento_tipo,
               DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) as dias_restantes
        FROM servicios_cliente sc
        JOIN clientes c ON sc.cliente_id = c.id AND c.activo = 1
        JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
        JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
        LEFT JOIN notificaciones_enviadas ne ON sc.id = ne.servicio_cliente_id 
            AND DATE(ne.fecha_envio) = CURDATE()
        WHERE sc.fecha_proximo_vencimiento <= ? 
            AND sc.activo = 1 
            AND ne.id IS NULL
        ORDER BY c.id, sc.fecha_proximo_vencimiento ASC
    ");

    $stmt->execute([$fecha_limite]);
    $servicios_vencer = $stmt->fetchAll();

    writeLog("Encontrados " . count($servicios_vencer) . " servicios para notificar");

    if (empty($servicios_vencer)) {
        writeLog("No hay servicios próximos a vencer");
        writeLog("=== PROCESO COMPLETADO SIN ACCIONES ===");
        exit(0);
    }

    // Agrupar por cliente
    $servicios_agrupados = [];
    foreach ($servicios_vencer as $servicio) {
        $cliente_id = $servicio['cliente_id'];
        if (!isset($servicios_agrupados[$cliente_id])) {
            $servicios_agrupados[$cliente_id] = [
                'cliente' => $servicio,
                'servicios' => []
            ];
        }
        $servicios_agrupados[$cliente_id]['servicios'][] = $servicio;
    }

    $total_emails = 0;
    $total_clientes = 0;

    // Datos empresa
    $empresa = [
        'nombre' => getConfig('empresa_nombre') ?: 'Tu Empresa',
        'email' => getConfig('empresa_email') ?: $email_admin,
        'telefono' => getConfig('empresa_telefono') ?: '',
        'web' => getConfig('empresa_web') ?: ''
    ];

    foreach ($servicios_agrupados as $grupo) {
        $cliente = $grupo['cliente'];
        $servicios = $grupo['servicios'];
        $total_precio = array_sum(array_column($servicios, 'precio_final'));
        
        writeLog("Procesando cliente: {$cliente['nombre']} {$cliente['apellidos']}");
        
        // Generar email
        $subject = "Aviso de Vencimiento - " . ($cliente['empresa'] ?: $cliente['nombre'] . ' ' . $cliente['apellidos']);
        $html = generarEmailHTML($cliente, $servicios, $total_precio, $empresa);
        
        // Enviar a destinatarios
        $destinatarios = [$email_admin];
        if (!empty($emails_copia)) {
            $extras = array_filter(array_map('trim', explode(',', $emails_copia)));
            $destinatarios = array_merge($destinatarios, $extras);
        }

        $emails_enviados = 0;
        foreach ($destinatarios as $email) {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if (enviarEmailSMTP($email, $subject, $html, $empresa['email'], $empresa['nombre'])) {
                    $emails_enviados++;
                    $total_emails++;
                }
            }
        }
        
        // Marcar como enviado si al menos un email se envió
        if ($emails_enviados > 0) {
            foreach ($servicios as $servicio) {
                $stmt = $pdo->prepare("INSERT INTO notificaciones_enviadas (servicio_cliente_id, tipo_notificacion) VALUES (?, 'vencimiento')");
                $stmt->execute([$servicio['id']]);
            }
            $total_clientes++;
            writeLog("Notificación enviada correctamente para cliente {$cliente['id']}");
        } else {
            writeLog("ERROR: No se pudo enviar ningún email para cliente {$cliente['id']}");
        }
    }

    // Actualizar servicios vencidos
    $stmt = $pdo->prepare("
        SELECT sc.id, sc.fecha_proximo_vencimiento, tv.dias, c.nombre, c.apellidos
        FROM servicios_cliente sc
        JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
        JOIN clientes c ON sc.cliente_id = c.id
        WHERE sc.fecha_proximo_vencimiento < CURDATE() AND sc.activo = 1
    ");
    $stmt->execute();
    $vencidos = $stmt->fetchAll();

    $renovados = 0;
    foreach ($vencidos as $servicio) {
        $nueva_fecha = date('Y-m-d', strtotime($servicio['fecha_proximo_vencimiento'] . ' + ' . $servicio['dias'] . ' days'));
        
        $stmt = $pdo->prepare("UPDATE servicios_cliente SET fecha_proximo_vencimiento = ? WHERE id = ?");
        if ($stmt->execute([$nueva_fecha, $servicio['id']])) {
            $renovados++;
            writeLog("Renovado servicio para {$servicio['nombre']} {$servicio['apellidos']}: $nueva_fecha");
        }
    }

    // Resumen final
    writeLog("=== RESUMEN FINAL ===");
    writeLog("Clientes procesados: $total_clientes");
    writeLog("Emails enviados: $total_emails");
    writeLog("Servicios renovados: $renovados");
    writeLog("=== PROCESO COMPLETADO EXITOSAMENTE ===");

} catch (Exception $e) {
    writeLog("ERROR CRÍTICO: " . $e->getMessage());
    writeLog("Trace: " . $e->getTraceAsString());
    exit(1);
}

function generarEmailHTML($cliente, $servicios, $total_precio, $empresa) {
    $primer_servicio = $servicios[0];
    $dias_restantes = $primer_servicio['dias_restantes'];
    
    $urgencia = $dias_restantes <= 2 ? 'urgente' : ($dias_restantes <= 7 ? 'proximo' : 'normal');
    $color_urgencia = [
        'urgente' => '#f44336',
        'proximo' => '#ff9800', 
        'normal' => '#4caf50'
    ];
    
    $html = "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Aviso de Vencimiento</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
            .urgencia { background: {$color_urgencia[$urgencia]}; color: white; padding: 15px; text-align: center; font-weight: bold; }
            .content { padding: 30px; }
            .cliente-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
            .servicio { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 15px 0; }
            .servicio.urgente { border-left: 5px solid #f44336; background: #ffebee; }
            .servicio.proximo { border-left: 5px solid #ff9800; background: #fff3e0; }
            .total { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; margin: 25px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; border-top: 1px solid #e2e8f0; }
            .precio { font-size: 18px; font-weight: bold; color: #48bb78; }
            .dias-restantes { font-size: 20px; font-weight: bold; color: {$color_urgencia[$urgencia]}; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Aviso de Vencimiento</h1>
                <p>Sistema de Gestión de Servicios</p>
            </div>
            
            <div class='urgencia'>
                " . ($urgencia === 'urgente' ? 'URGENTE' : ($urgencia === 'proximo' ? 'PRÓXIMO' : 'AVISO')) . " - 
                Servicios que vencen en <span class='dias-restantes'>$dias_restantes días</span>
            </div>
            
            <div class='content'>
                <div class='cliente-info'>
                    <h3>Cliente: {$cliente['nombre']} {$cliente['apellidos']}</h3>
                    " . ($cliente['empresa'] ? "<p><strong>Empresa:</strong> {$cliente['empresa']}</p>" : "") . "
                    <p><strong>Email:</strong> {$cliente['email']}</p>
                    <p><strong>Teléfono:</strong> {$cliente['telefono']}</p>
                </div>
                
                <h4>Servicios que vencen:</h4>";
    
    foreach ($servicios as $servicio) {
        $clase_servicio = $servicio['dias_restantes'] <= 2 ? 'urgente' : 'proximo';
        $fecha_venc = date('d/m/Y', strtotime($servicio['fecha_proximo_vencimiento']));
        
        $html .= "
                <div class='servicio $clase_servicio'>
                    <h4>{$servicio['servicio_nombre']}</h4>
                    <p>{$servicio['descripcion']}</p>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin-top: 15px;'>
                        <div>
                            <strong>Vence:</strong> $fecha_venc<br>
                            <strong>Tipo:</strong> {$servicio['vencimiento_tipo']}
                        </div>
                        <div class='precio'>€" . number_format($servicio['precio_final'], 2) . "</div>
                    </div>
                </div>";
    }
    
    $html .= "
                <div class='total'>
                    <h3>Total a renovar: €" . number_format($total_precio, 2) . "</h3>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p><strong>Acciones recomendadas:</strong></p>
                    <p>Contactar con el cliente para coordinar la renovación</p>
                    <p>Generar albarán desde el sistema</p>
                    <p>Programar seguimiento si es necesario</p>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>{$empresa['nombre']}</strong></p>
                <p>{$empresa['email']}" . ($empresa['telefono'] ? " | {$empresa['telefono']}" : "") . "</p>
                " . ($empresa['web'] ? "<p>{$empresa['web']}</p>" : "") . "
                <p style='margin-top: 20px; font-size: 12px; color: #999;'>
                    Email generado automáticamente el " . date('d/m/Y H:i') . "
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}

echo "\nProceso completado. Revisa el log para más detalles.\n";
?>