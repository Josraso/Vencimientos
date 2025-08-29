<?php
// includes/data_logic.php - Lógica de datos y procesamiento de formularios

function procesarFormulario($post, $pdo) {
    $mensaje = '';
    
    switch ($post['action']) {
        case 'add_cliente':
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre, apellidos, telefono, empresa, email, direccion, ciudad, codigo_postal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $post['nombre'], $post['apellidos'], $post['telefono'], 
                $post['empresa'], $post['email'], $post['direccion'], 
                $post['ciudad'], $post['codigo_postal']
            ]);
            $mensaje = "Cliente añadido correctamente";
            break;
            
        case 'edit_cliente':
            $stmt = $pdo->prepare("UPDATE clientes SET nombre=?, apellidos=?, telefono=?, empresa=?, email=?, direccion=?, ciudad=?, codigo_postal=? WHERE id=?");
            $stmt->execute([
                $post['nombre'], $post['apellidos'], $post['telefono'], 
                $post['empresa'], $post['email'], $post['direccion'], 
                $post['ciudad'], $post['codigo_postal'], $post['id']
            ]);
            $mensaje = "Cliente actualizado correctamente";
            break;
            
        case 'delete_cliente':
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM servicios_cliente WHERE cliente_id = ? AND activo = 1");
            $stmt->execute([$post['cliente_id']]);
            $servicios_activos = $stmt->fetch()['total'];
            
            if ($servicios_activos > 0) {
                throw new Exception("No se puede eliminar el cliente porque tiene $servicios_activos servicios activos.");
            }
            
            $stmt = $pdo->prepare("UPDATE clientes SET activo = 0 WHERE id = ?");
            $stmt->execute([$post['cliente_id']]);
            $mensaje = "Cliente desactivado correctamente";
            break;
            
        case 'add_servicio_cliente':
            $fecha_inicio = $post['fecha_inicio'];
            $tipo_vencimiento_id = $post['tipo_vencimiento_id'];
            
            $stmt = $pdo->prepare("SELECT dias FROM tipos_vencimiento WHERE id = ?");
            $stmt->execute([$tipo_vencimiento_id]);
            $dias = $stmt->fetch()['dias'];
            
            $fecha_vencimiento = date('Y-m-d', strtotime($fecha_inicio . " + $dias days"));
            $precio_final = !empty($post['precio_personalizado']) ? $post['precio_personalizado'] : null;
            
            $stmt = $pdo->prepare("INSERT INTO servicios_cliente (cliente_id, tipo_servicio_id, tipo_vencimiento_id, fecha_inicio, fecha_proximo_vencimiento, precio_personalizado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $post['cliente_id'], $post['tipo_servicio_id'], $tipo_vencimiento_id, 
                $fecha_inicio, $fecha_vencimiento, $precio_final, $post['observaciones']
            ]);
            $mensaje = "Servicio asignado correctamente";
            break;
            
        case 'update_config':
            $configs = [
                'dias_aviso', 'email_admin', 'emails_copia', 'empresa_nombre',
                'empresa_direccion', 'empresa_ciudad', 'empresa_codigo_postal',
                'empresa_telefono', 'empresa_email', 'empresa_cif', 'empresa_web',
                'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass'
            ];
            
            foreach ($configs as $config) {
                if (isset($post[$config])) {
                    updateConfig($config, $post[$config]);
                }
            }
            $mensaje = "Configuración actualizada correctamente";
            break;
    }
    
    return $mensaje;
}

function cargarDatosSistema($pdo) {
    $search = $_GET['search'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    $clientes_data = cargarClientesPaginados($pdo, $search, $per_page, $offset);
    
    $datos = [
        'clientes' => $clientes_data['clientes'],
        'total_clientes' => $clientes_data['total'],
        'total_pages' => $clientes_data['total_pages'],
        'current_page' => $page,
        'search' => $search,
        'tipos_servicios' => cargarTiposServicios($pdo),
        'tipos_vencimiento' => cargarTiposVencimiento($pdo),
        'estadisticas_servicios' => cargarEstadisticasServicios($pdo),
        'proximos_vencimientos' => cargarProximosVencimientos($pdo),
        'todos_servicios' => [], // No cargar automáticamente, solo cuando se necesite
        'albaranes_recientes' => cargarAlbaranesRecientes($pdo),
        'estadisticas_generales' => calcularEstadisticasGenerales($pdo)
    ];
    
    return $datos;
}

function cargarClientesPaginados($pdo, $search, $per_page, $offset) {
    $where_conditions = ["c.activo = 1"];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(c.nombre LIKE ? OR c.apellidos LIKE ? OR c.empresa LIKE ? OR c.email LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }

    $where_clause = "WHERE " . implode(" AND ", $where_conditions);

    $clientes_query = "
        SELECT c.*, 
               COUNT(sc.id) as total_servicios,
               SUM(CASE WHEN sc.activo = 1 THEN 1 ELSE 0 END) as servicios_activos,
               MAX(sc.fecha_proximo_vencimiento) as proximo_vencimiento,
               SUM(CASE WHEN sc.activo = 1 THEN 
                   (SELECT COUNT(*) FROM albaranes a JOIN albaran_lineas al ON a.id = al.albaran_id WHERE al.servicio_cliente_id = sc.id)
                   ELSE 0 END) as albaranes_generados
        FROM clientes c 
        LEFT JOIN servicios_cliente sc ON c.id = sc.cliente_id
        $where_clause
        GROUP BY c.id
        ORDER BY c.nombre, c.apellidos
        LIMIT $per_page OFFSET $offset
    ";

    $stmt = $pdo->prepare($clientes_query);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll();

    $total_query = "SELECT COUNT(DISTINCT c.id) as total FROM clientes c $where_clause";
    $stmt = $pdo->prepare($total_query);
    $stmt->execute($params);
    $total_clientes = $stmt->fetch()['total'];

    return [
        'clientes' => $clientes,
        'total' => $total_clientes,
        'total_pages' => ceil($total_clientes / $per_page)
    ];
}

function cargarTiposServicios($pdo) {
    return $pdo->query("SELECT * FROM tipos_servicios WHERE activo = 1 ORDER BY nombre")->fetchAll();
}

function cargarTiposVencimiento($pdo) {
    return $pdo->query("SELECT * FROM tipos_vencimiento WHERE activo = 1 ORDER BY dias")->fetchAll();
}

function cargarEstadisticasServicios($pdo) {
    return $pdo->query("
        SELECT ts.id, ts.nombre, ts.precio, ts.descripcion,
               COUNT(sc.id) as total_contrataciones,
               COUNT(DISTINCT sc.cliente_id) as clientes_distintos,
               SUM(CASE WHEN sc.activo = 1 THEN 1 ELSE 0 END) as activas,
               SUM(CASE WHEN sc.activo = 1 THEN 
                   COALESCE(sc.precio_personalizado, ts.precio) 
                   ELSE 0 END) as ingresos_mensuales,
               COUNT(CASE WHEN sc.activo = 1 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= 7 THEN 1 END) as proximos_vencer
        FROM tipos_servicios ts
        LEFT JOIN servicios_cliente sc ON ts.id = sc.tipo_servicio_id
        WHERE ts.activo = 1
        GROUP BY ts.id
        ORDER BY ts.nombre
    ")->fetchAll();
}

function cargarProximosVencimientos($pdo) {
    $dias_aviso = getConfig('dias_aviso') ?: 5;
    $fecha_limite = date('Y-m-d', strtotime("+$dias_aviso days"));

    $stmt = $pdo->prepare("
        SELECT sc.*, c.nombre, c.apellidos, c.empresa, c.email, c.telefono,
               ts.nombre as servicio_nombre, ts.descripcion, 
               COALESCE(sc.precio_personalizado, ts.precio) as precio_final,
               tv.nombre as vencimiento_tipo,
               DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) as dias_restantes,
               (SELECT COUNT(*) FROM albaranes a 
                JOIN albaran_lineas al ON a.id = al.albaran_id 
                WHERE al.servicio_cliente_id = sc.id) as tiene_albaran
        FROM servicios_cliente sc
        JOIN clientes c ON sc.cliente_id = c.id
        JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
        JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
        WHERE sc.fecha_proximo_vencimiento <= ? AND sc.activo = 1 AND c.activo = 1
        ORDER BY sc.fecha_proximo_vencimiento ASC, c.nombre
    ");
    $stmt->execute([$fecha_limite]);
    return $stmt->fetchAll();
}

function cargarTodosServicios($pdo) {
    return $pdo->query("
        SELECT sc.*, c.nombre, c.apellidos, c.empresa, c.email, c.telefono,
               ts.nombre as servicio_nombre, ts.descripcion, 
               COALESCE(sc.precio_personalizado, ts.precio) as precio_final,
               tv.nombre as vencimiento_tipo,
               DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) as dias_restantes,
               (SELECT COUNT(*) FROM albaranes a 
                JOIN albaran_lineas al ON a.id = al.albaran_id 
                WHERE al.servicio_cliente_id = sc.id) as tiene_albaran
        FROM servicios_cliente sc
        JOIN clientes c ON sc.cliente_id = c.id
        JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
        JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
        WHERE sc.activo = 1 AND c.activo = 1
        ORDER BY sc.fecha_proximo_vencimiento ASC, c.nombre
    ")->fetchAll();
}

function cargarAlbaranesRecientes($pdo) {
    return $pdo->query("
        SELECT a.*, c.nombre, c.apellidos, c.empresa,
               COUNT(al.id) as total_lineas,
               GROUP_CONCAT(ts.nombre SEPARATOR ', ') as servicios_nombres
        FROM albaranes a
        JOIN clientes c ON a.cliente_id = c.id
        LEFT JOIN albaran_lineas al ON a.id = al.albaran_id
        LEFT JOIN servicios_cliente sc ON al.servicio_cliente_id = sc.id
        LEFT JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
        GROUP BY a.id
        ORDER BY a.created_at DESC
        LIMIT 20
    ")->fetchAll();
}

function calcularEstadisticasGenerales($pdo) {
    $stats = [];
    
    $stats['total_clientes'] = $pdo->query("SELECT COUNT(*) FROM clientes WHERE activo = 1")->fetchColumn();
    $stats['total_servicios'] = $pdo->query("SELECT COUNT(*) FROM servicios_cliente WHERE activo = 1")->fetchColumn();
    $stats['total_albaranes'] = $pdo->query("SELECT COUNT(*) FROM albaranes")->fetchColumn();
    
    $dias_aviso = getConfig('dias_aviso') ?: 5;
    $fecha_limite = date('Y-m-d', strtotime("+$dias_aviso days"));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios_cliente sc JOIN clientes c ON sc.cliente_id = c.id WHERE sc.fecha_proximo_vencimiento <= ? AND sc.activo = 1 AND c.activo = 1");
    $stmt->execute([$fecha_limite]);
    $stats['proximos_vencimientos'] = $stmt->fetchColumn();
    
    $stats['ingresos_mensuales'] = $pdo->query("
        SELECT SUM(COALESCE(sc.precio_personalizado, ts.precio)) 
        FROM servicios_cliente sc 
        JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id 
        WHERE sc.activo = 1
    ")->fetchColumn() ?: 0;
    
    $stats['servicios_urgentes'] = $pdo->query("
        SELECT COUNT(*) FROM servicios_cliente sc 
        JOIN clientes c ON sc.cliente_id = c.id 
        WHERE DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= 2 
        AND sc.activo = 1 AND c.activo = 1
    ")->fetchColumn();
    
    $stats['servicios_sin_facturar'] = $pdo->query("
        SELECT COUNT(*) FROM servicios_cliente sc 
        JOIN clientes c ON sc.cliente_id = c.id 
        WHERE sc.activo = 1 AND c.activo = 1
        AND NOT EXISTS (SELECT 1 FROM albaranes a JOIN albaran_lineas al ON a.id = al.albaran_id WHERE al.servicio_cliente_id = sc.id)
    ")->fetchColumn();
    
    $stats['facturacion_pendiente'] = $pdo->query("
        SELECT SUM(COALESCE(sc.precio_personalizado, ts.precio)) 
        FROM servicios_cliente sc 
        JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id 
        JOIN clientes c ON sc.cliente_id = c.id 
        WHERE sc.activo = 1 AND c.activo = 1
        AND NOT EXISTS (SELECT 1 FROM albaranes a JOIN albaran_lineas al ON a.id = al.albaran_id WHERE al.servicio_cliente_id = sc.id)
    ")->fetchColumn() ?: 0;
    
    return $stats;
}
?>