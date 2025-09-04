<?php
// includes/data_logic.php - Lógica de datos y procesamiento de formularios

function procesarFormulario($post, $pdo) {
    $action = $post['action'] ?? '';
    
    switch ($action) {
        case 'add_cliente':
            return procesarAddCliente($post, $pdo);
            
        case 'edit_cliente':
            return procesarEditCliente($post, $pdo);
            
        case 'delete_cliente':
            return procesarDeleteCliente($post, $pdo);
            
        case 'add_servicio_cliente':
            return procesarAddServicioCliente($post, $pdo);
            
        case 'add_servicio_tipo':
            return procesarAddTipoServicio($post, $pdo);
            
        case 'edit_servicio_tipo':
            return procesarEditTipoServicio($post, $pdo);
            
        case 'update_config':
            return procesarUpdateConfig($post, $pdo);
            
        default:
            throw new Exception('Acción no válida');
    }
}

function procesarAddCliente($post, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO clientes (nombre, apellidos, dni, telefono, empresa, email, direccion, ciudad, codigo_postal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $post['nombre'],
        $post['apellidos'], 
        $post['dni'] ?? '',
        $post['telefono'] ?? '',
        $post['empresa'] ?? '',
        $post['email'],
        $post['direccion'] ?? '',
        $post['ciudad'] ?? '',
        $post['codigo_postal'] ?? ''
    ]);
    
    return 'Cliente añadido correctamente';
}

function procesarEditCliente($post, $pdo) {
    $stmt = $pdo->prepare("UPDATE clientes SET nombre=?, apellidos=?, dni=?, telefono=?, empresa=?, email=?, direccion=?, ciudad=?, codigo_postal=? WHERE id=?");
    
    $stmt->execute([
        $post['nombre'],
        $post['apellidos'],
        $post['dni'] ?? '',
        $post['telefono'] ?? '',
        $post['empresa'] ?? '',
        $post['email'],
        $post['direccion'] ?? '',
        $post['ciudad'] ?? '',
        $post['codigo_postal'] ?? '',
        $post['id']
    ]);
    
    return 'Cliente actualizado correctamente';
}

function procesarDeleteCliente($post, $pdo) {
    // Verificar si tiene servicios activos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios_cliente WHERE cliente_id = ? AND activo = 1");
    $stmt->execute([$post['cliente_id']]);
    $servicios_activos = $stmt->fetchColumn();
    
    if ($servicios_activos > 0) {
        throw new Exception('No se puede eliminar un cliente con servicios activos');
    }
    
    // Eliminar cliente (cascade eliminará sus servicios inactivos)
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$post['cliente_id']]);
    
    return 'Cliente eliminado correctamente';
}

function procesarAddServicioCliente($post, $pdo) {
    // Obtener días del tipo de vencimiento
    $stmt = $pdo->prepare("SELECT dias FROM tipos_vencimiento WHERE id = ?");
    $stmt->execute([$post['tipo_vencimiento_id']]);
    $dias = $stmt->fetchColumn();
    
    if (!$dias) {
        throw new Exception('Tipo de vencimiento no encontrado');
    }
    
    // Calcular fecha de vencimiento
    $fecha_inicio = $post['fecha_inicio'];
    $fecha_vencimiento = date('Y-m-d', strtotime($fecha_inicio . ' + ' . $dias . ' days'));
    
    $stmt = $pdo->prepare("INSERT INTO servicios_cliente (cliente_id, tipo_servicio_id, tipo_vencimiento_id, fecha_inicio, fecha_proximo_vencimiento, precio_personalizado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $post['cliente_id'],
        $post['tipo_servicio_id'],
        $post['tipo_vencimiento_id'],
        $fecha_inicio,
        $fecha_vencimiento,
        !empty($post['precio_personalizado']) ? $post['precio_personalizado'] : null,
        $post['observaciones'] ?? ''
    ]);
    
    return 'Servicio asignado correctamente';
}

function procesarAddTipoServicio($post, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO tipos_servicios (nombre, descripcion, precio) VALUES (?, ?, ?)");
    
    $stmt->execute([
        $post['nombre'],
        $post['descripcion'] ?? '',
        $post['precio']
    ]);
    
    return 'Tipo de servicio añadido correctamente';
}

function procesarEditTipoServicio($post, $pdo) {
    $stmt = $pdo->prepare("UPDATE tipos_servicios SET nombre=?, descripcion=?, precio=? WHERE id=?");
    
    $stmt->execute([
        $post['nombre'],
        $post['descripcion'] ?? '',
        $post['precio'],
        $post['id']
    ]);
    
    return 'Tipo de servicio actualizado correctamente';
}

function procesarUpdateConfig($post, $pdo) {
    foreach ($post as $key => $value) {
        if ($key !== 'action') {
            $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
            $stmt->execute([$value, $key]);
        }
    }
    
    return 'Configuración actualizada correctamente';
}

function cargarDatosSistema($pdo) {
    try {
        // Estadísticas generales con mejor cálculo
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT c.id) as total_clientes,
                COUNT(sc.id) as total_servicios,
                COUNT(CASE WHEN DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= 2 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) >= 0 THEN 1 END) as servicios_urgentes,
                COUNT(CASE WHEN DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= 7 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) >= 0 THEN 1 END) as proximos_vencimientos,
                COALESCE(SUM(COALESCE(sc.precio_personalizado, ts.precio)), 0) as ingresos_mensuales,
                COALESCE(SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM albaranes a JOIN albaran_lineas al ON a.id = al.albaran_id WHERE al.servicio_cliente_id = sc.id) THEN COALESCE(sc.precio_personalizado, ts.precio) ELSE 0 END), 0) as facturacion_pendiente
            FROM servicios_cliente sc
            JOIN clientes c ON sc.cliente_id = c.id AND c.activo = 1
            JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
            WHERE sc.activo = 1
        ");
        $estadisticas_generales = $stmt->fetch();
        
        // Asegurar valores por defecto
        $estadisticas_generales = array_merge([
            'total_clientes' => 0,
            'total_servicios' => 0,
            'servicios_urgentes' => 0,
            'proximos_vencimientos' => 0,
            'ingresos_mensuales' => 0,
            'facturacion_pendiente' => 0
        ], $estadisticas_generales ?: []);
        
        // Contar albaranes
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM albaranes");
        $albaranesCount = $stmt->fetch();
        $estadisticas_generales['total_albaranes'] = $albaranesCount['total'] ?? 0;
        
        // Tipos de servicios
        $stmt = $pdo->query("SELECT * FROM tipos_servicios WHERE activo = 1 ORDER BY nombre");
        $tipos_servicios = $stmt->fetchAll() ?: [];
        
        // Tipos de vencimiento
        $stmt = $pdo->query("SELECT * FROM tipos_vencimiento WHERE activo = 1 ORDER BY dias");
        $tipos_vencimiento = $stmt->fetchAll() ?: [];
        
        // Estadísticas de servicios
        $stmt = $pdo->query("
            SELECT ts.*, 
                   COUNT(DISTINCT sc.cliente_id) as clientes_distintos,
                   COUNT(sc.id) as total_contrataciones,
                   COUNT(CASE WHEN sc.activo = 1 THEN 1 END) as activas,
                   COUNT(CASE WHEN sc.activo = 1 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= 7 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) >= 0 THEN 1 END) as proximos_vencer,
                   COALESCE(SUM(CASE WHEN sc.activo = 1 THEN COALESCE(sc.precio_personalizado, ts.precio) ELSE 0 END), 0) as ingresos_mensuales
            FROM tipos_servicios ts
            LEFT JOIN servicios_cliente sc ON ts.id = sc.tipo_servicio_id
            WHERE ts.activo = 1
            GROUP BY ts.id
            ORDER BY ts.nombre
        ");
        $estadisticas_servicios = $stmt->fetchAll() ?: [];
        
        // Clientes con paginación
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $where = "WHERE c.activo = 1";
        $params = [];
        if ($search) {
            $where .= " AND (c.nombre LIKE ? OR c.apellidos LIKE ? OR c.empresa LIKE ? OR c.email LIKE ?)";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        }
        
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COUNT(sc.id) as total_servicios,
                   COUNT(CASE WHEN sc.activo = 1 THEN 1 END) as servicios_activos,
                   MIN(CASE WHEN sc.activo = 1 THEN sc.fecha_proximo_vencimiento END) as proximo_vencimiento,
                   COUNT(DISTINCT a.id) as albaranes_generados
            FROM clientes c
            LEFT JOIN servicios_cliente sc ON c.id = sc.cliente_id
            LEFT JOIN albaran_lineas al ON sc.id = al.servicio_cliente_id
            LEFT JOIN albaranes a ON al.albaran_id = a.id
            $where
            GROUP BY c.id
            ORDER BY c.nombre, c.apellidos
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $clientes = $stmt->fetchAll() ?: [];
        
        // Total de clientes para paginación
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes c $where");
        $stmt->execute($params);
        $total_clientes = $stmt->fetchColumn() ?: 0;
        $total_pages = ceil($total_clientes / $limit);
        
        return [
            'estadisticas_generales' => $estadisticas_generales,
            'tipos_servicios' => $tipos_servicios,
            'tipos_vencimiento' => $tipos_vencimiento,
            'estadisticas_servicios' => $estadisticas_servicios,
            'clientes' => $clientes,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'search' => $search
        ];
        
    } catch (Exception $e) {
        error_log("Error en cargarDatosSistema: " . $e->getMessage());
        
        // En caso de error, devolver datos vacíos
        return [
            'estadisticas_generales' => [
                'total_clientes' => 0,
                'total_servicios' => 0,
                'servicios_urgentes' => 0,
                'proximos_vencimientos' => 0,
                'ingresos_mensuales' => 0,
                'facturacion_pendiente' => 0,
                'total_albaranes' => 0
            ],
            'tipos_servicios' => [],
            'tipos_vencimiento' => [],
            'estadisticas_servicios' => [],
            'clientes' => [],
            'current_page' => 1,
            'total_pages' => 1,
            'search' => ''
        ];
    }
}

// Funciones auxiliares para obtener datos específicos
function obtenerProximosVencimientos($pdo, $dias = 7) {
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
        WHERE sc.activo = 1 
        AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= ? 
        AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) >= 0
        ORDER BY sc.fecha_proximo_vencimiento ASC
    ");
    $stmt->execute([$dias]);
    return $stmt->fetchAll();
}

function obtenerClienteCompleto($pdo, $cliente_id) {
    // Datos del cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND activo = 1");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        return null;
    }
    
    // Servicios del cliente
    $stmt = $pdo->prepare("
        SELECT sc.*, ts.nombre as servicio_nombre, ts.descripcion, ts.precio as precio_base,
               COALESCE(sc.precio_personalizado, ts.precio) as precio_final,
               tv.nombre as vencimiento_tipo, tv.dias,
               DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) as dias_restantes,
               (SELECT COUNT(*) FROM albaranes a 
                JOIN albaran_lineas al ON a.id = al.albaran_id 
                WHERE al.servicio_cliente_id = sc.id) as tiene_albaran
        FROM servicios_cliente sc
        JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
        JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
        WHERE sc.cliente_id = ? AND sc.activo = 1
        ORDER BY sc.fecha_proximo_vencimiento ASC
    ");
    $stmt->execute([$cliente_id]);
    $servicios = $stmt->fetchAll();
    
    return [
        'cliente' => $cliente,
        'servicios' => $servicios
    ];
}

function obtenerEstadisticasGenerales($pdo) {
    $stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM clientes WHERE activo = 1) as total_clientes,
            (SELECT COUNT(*) FROM servicios_cliente sc JOIN clientes c ON sc.cliente_id = c.id WHERE sc.activo = 1 AND c.activo = 1) as total_servicios,
            (SELECT COUNT(*) FROM servicios_cliente sc JOIN clientes c ON sc.cliente_id = c.id WHERE sc.activo = 1 AND c.activo = 1 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= 2 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) >= 0) as servicios_urgentes,
            (SELECT COUNT(*) FROM servicios_cliente sc JOIN clientes c ON sc.cliente_id = c.id WHERE sc.activo = 1 AND c.activo = 1 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= 7 AND DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) >= 0) as proximos_vencimientos,
            (SELECT COALESCE(SUM(COALESCE(sc.precio_personalizado, ts.precio)), 0) FROM servicios_cliente sc JOIN clientes c ON sc.cliente_id = c.id JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id WHERE sc.activo = 1 AND c.activo = 1) as ingresos_mensuales,
            (SELECT COUNT(*) FROM albaranes) as total_albaranes
    ");
    return $stmt->fetch();
}
?>