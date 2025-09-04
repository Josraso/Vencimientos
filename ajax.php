<?php
// ajax.php - Manejador de peticiones AJAX corregido
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    die(json_encode(['error' => 'Acción no especificada']));
}

$action = $_POST['action'];
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($action) {
        
        case 'reset_database':
            $clientes = isset($_POST['clientes']) && ($_POST['clientes'] === 'true' || $_POST['clientes'] === true);
            $servicios = isset($_POST['servicios']) && ($_POST['servicios'] === 'true' || $_POST['servicios'] === true);
            $servicios_cliente = isset($_POST['servicios_cliente']) && ($_POST['servicios_cliente'] === 'true' || $_POST['servicios_cliente'] === true);
            $albaranes = isset($_POST['albaranes']) && ($_POST['albaranes'] === 'true' || $_POST['albaranes'] === true);
            $notificaciones = isset($_POST['notificaciones']) && ($_POST['notificaciones'] === 'true' || $_POST['notificaciones'] === true);
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $eliminados = [];
            
            if ($notificaciones) {
                $result = $pdo->exec("DELETE FROM notificaciones_enviadas");
                $eliminados[] = "notificaciones ($result filas)";
            }
            
            if ($albaranes) {
                $result1 = $pdo->exec("DELETE FROM albaran_lineas");
                $result2 = $pdo->exec("DELETE FROM albaranes");
                $pdo->exec("ALTER TABLE albaranes AUTO_INCREMENT = 1");
                updateConfig('siguiente_albaran', '1');
                $eliminados[] = "albaranes ($result2 albaranes, $result1 líneas)";
            }
            
            if ($servicios_cliente || $clientes) {
                $result = $pdo->exec("DELETE FROM servicios_cliente");
                $pdo->exec("ALTER TABLE servicios_cliente AUTO_INCREMENT = 1");
                $eliminados[] = "servicios de clientes ($result filas)";
            }
            
            if ($clientes) {
                $result = $pdo->exec("DELETE FROM clientes");
                $pdo->exec("ALTER TABLE clientes AUTO_INCREMENT = 1");
                $eliminados[] = "clientes ($result filas)";
            }
            
            if ($servicios) {
                $result = $pdo->exec("DELETE FROM tipos_servicios");
                $pdo->exec("ALTER TABLE tipos_servicios AUTO_INCREMENT = 1");
                $eliminados[] = "tipos de servicios ($result filas)";
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $mensaje = count($eliminados) > 0 ? 
                "Eliminados: " . implode(", ", $eliminados) : 
                "No se seleccionó nada para eliminar";
            
            $response['success'] = true;
            $response['message'] = $mensaje;
            break;
            
        case 'get_cliente':
            $cliente_id = $_POST['cliente_id'];
            
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND activo = 1");
            $stmt->execute([$cliente_id]);
            $cliente = $stmt->fetch();
            
            if (!$cliente) {
                throw new Exception('Cliente no encontrado');
            }
            
            // Obtener TODOS los servicios (activos e inactivos)
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
                WHERE sc.cliente_id = ?
                ORDER BY sc.activo DESC, sc.fecha_proximo_vencimiento ASC
            ");
            $stmt->execute([$cliente_id]);
            $servicios = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = [
                'cliente' => $cliente,
                'servicios' => $servicios
            ];
            break;
            
        case 'validar_borrado_cliente':
            $cliente_id = $_POST['cliente_id'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM servicios_cliente WHERE cliente_id = ? AND activo = 1");
            $stmt->execute([$cliente_id]);
            $servicios_activos = $stmt->fetch()['total'];
            
            $servicios_detalle = [];
            if ($servicios_activos > 0) {
                $stmt = $pdo->prepare("
                    SELECT ts.nombre, sc.fecha_proximo_vencimiento, 
                           COALESCE(sc.precio_personalizado, ts.precio) as precio_final
                    FROM servicios_cliente sc
                    JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
                    WHERE sc.cliente_id = ? AND sc.activo = 1
                    ORDER BY sc.fecha_proximo_vencimiento ASC
                ");
                $stmt->execute([$cliente_id]);
                $servicios_detalle = $stmt->fetchAll();
            }
            
            $response['success'] = true;
            $response['data'] = [
                'servicios_activos' => $servicios_activos,
                'servicios_detalle' => $servicios_detalle,
                'puede_borrar' => $servicios_activos == 0
            ];
            break;
            
        case 'get_servicio_cliente':
            $servicio_id = $_POST['servicio_id'];
            
            $stmt = $pdo->prepare("
                SELECT sc.*, ts.nombre as servicio_nombre, ts.descripcion, ts.precio as precio_base,
                       COALESCE(sc.precio_personalizado, ts.precio) as precio_final,
                       tv.id as tipo_vencimiento_id, tv.nombre as vencimiento_tipo, tv.dias,
                       c.nombre as cliente_nombre, c.apellidos as cliente_apellidos
                FROM servicios_cliente sc
                JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
                JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
                JOIN clientes c ON sc.cliente_id = c.id
                WHERE sc.id = ?
            ");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch();
            
            if (!$servicio) {
                throw new Exception('Servicio no encontrado');
            }
            
            $response['success'] = true;
            $response['data'] = $servicio;
            break;
            
        case 'edit_servicio_cliente':
            $servicio_id = $_POST['servicio_id'];
            $tipo_vencimiento_id = $_POST['tipo_vencimiento_id'];
            $precio_personalizado = empty($_POST['precio_personalizado']) ? null : $_POST['precio_personalizado'];
            $observaciones = $_POST['observaciones'];
            $fecha_vencimiento = $_POST['fecha_proximo_vencimiento'] ?? null;
            
            if ($fecha_vencimiento) {
                // Si se especifica fecha, usarla directamente
                $stmt = $pdo->prepare("
                    UPDATE servicios_cliente 
                    SET tipo_vencimiento_id = ?, precio_personalizado = ?, observaciones = ?, 
                        fecha_proximo_vencimiento = ?
                    WHERE id = ?
                ");
                $stmt->execute([$tipo_vencimiento_id, $precio_personalizado, $observaciones, 
                               $fecha_vencimiento, $servicio_id]);
            } else {
                // Si no hay fecha, calcular según el tipo de vencimiento
                $stmt = $pdo->prepare("SELECT dias FROM tipos_vencimiento WHERE id = ?");
                $stmt->execute([$tipo_vencimiento_id]);
                $dias = $stmt->fetch()['dias'];
                
                $nueva_fecha = date('Y-m-d', strtotime("+$dias days"));
                
                $stmt = $pdo->prepare("
                    UPDATE servicios_cliente 
                    SET tipo_vencimiento_id = ?, precio_personalizado = ?, observaciones = ?, 
                        fecha_proximo_vencimiento = ?
                    WHERE id = ?
                ");
                $stmt->execute([$tipo_vencimiento_id, $precio_personalizado, $observaciones, 
                               $nueva_fecha, $servicio_id]);
            }
            
            $response['success'] = true;
            $response['message'] = 'Servicio actualizado correctamente';
            break;
            
        case 'renovar_servicio':
            $servicio_id = $_POST['servicio_id'];
            
            // Obtener datos actuales
            $stmt = $pdo->prepare("
                SELECT sc.*, tv.dias 
                FROM servicios_cliente sc
                JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
                WHERE sc.id = ?
            ");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch();
            
            if (!$servicio) {
                throw new Exception('Servicio no encontrado');
            }
            
            // Calcular nueva fecha desde el vencimiento actual (no desde hoy)
            $fecha_actual = $servicio['fecha_proximo_vencimiento'];
            $nueva_fecha = date('Y-m-d', strtotime($fecha_actual . ' + ' . $servicio['dias'] . ' days'));
            
            $stmt = $pdo->prepare("UPDATE servicios_cliente SET fecha_proximo_vencimiento = ? WHERE id = ?");
            $stmt->execute([$nueva_fecha, $servicio_id]);
            
            $response['success'] = true;
            $response['message'] = 'Servicio renovado hasta el ' . date('d/m/Y', strtotime($nueva_fecha));
            $response['data'] = ['nueva_fecha' => $nueva_fecha];
            break;
            
        case 'renovar_masivo':
            $servicios_ids = $_POST['servicios_ids'];
            
            if (empty($servicios_ids) || !is_array($servicios_ids)) {
                throw new Exception('No se han seleccionado servicios');
            }
            
            $renovados = 0;
            foreach ($servicios_ids as $servicio_id) {
                $stmt = $pdo->prepare("
                    SELECT sc.*, tv.dias 
                    FROM servicios_cliente sc
                    JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
                    WHERE sc.id = ?
                ");
                $stmt->execute([$servicio_id]);
                $servicio = $stmt->fetch();
                
                if ($servicio) {
                    $fecha_actual = $servicio['fecha_proximo_vencimiento'];
                    $nueva_fecha = date('Y-m-d', strtotime($fecha_actual . ' + ' . $servicio['dias'] . ' days'));
                    
                    $stmt = $pdo->prepare("UPDATE servicios_cliente SET fecha_proximo_vencimiento = ? WHERE id = ?");
                    $stmt->execute([$nueva_fecha, $servicio_id]);
                    $renovados++;
                }
            }
            
            $response['success'] = true;
            $response['message'] = "$renovados servicios renovados correctamente";
            break;
            
        case 'toggle_servicio_cliente':
            $servicio_cliente_id = $_POST['servicio_cliente_id'];
            $activo = $_POST['activo'] === 'true' || $_POST['activo'] === '1' ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE servicios_cliente SET activo = ? WHERE id = ?");
            $stmt->execute([$activo, $servicio_cliente_id]);
            
            $response['success'] = true;
            $response['message'] = $activo ? 'Servicio activado' : 'Servicio desactivado';
            break;
            
        case 'eliminar_servicio_cliente':
            $servicio_id = $_POST['servicio_id'];
            
            // Verificar si tiene albaranes
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM albaran_lineas WHERE servicio_cliente_id = ?");
            $stmt->execute([$servicio_id]);
            $tiene_albaran = $stmt->fetch()['total'];
            
            if ($tiene_albaran > 0) {
                throw new Exception('No se puede eliminar un servicio que ya tiene albaranes generados');
            }
            
            $stmt = $pdo->prepare("DELETE FROM servicios_cliente WHERE id = ?");
            $stmt->execute([$servicio_id]);
            
            $response['success'] = true;
            $response['message'] = 'Servicio eliminado correctamente';
            break;
            
        case 'get_clientes_servicio':
            $servicio_id = $_POST['servicio_id'];
            
            $stmt = $pdo->prepare("
                SELECT c.*, sc.id as servicio_cliente_id, sc.fecha_inicio, sc.fecha_proximo_vencimiento,
                       COALESCE(sc.precio_personalizado, ts.precio) as precio_final,
                       tv.nombre as vencimiento_tipo,
                       DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) as dias_restantes,
                       sc.observaciones, sc.activo as servicio_activo,
                       (SELECT COUNT(*) FROM albaranes a 
                        JOIN albaran_lineas al ON a.id = al.albaran_id 
                        WHERE al.servicio_cliente_id = sc.id) as tiene_albaran
                FROM clientes c
                JOIN servicios_cliente sc ON c.id = sc.cliente_id
                JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
                JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
                WHERE sc.tipo_servicio_id = ? AND sc.activo = 1 AND c.activo = 1
                ORDER BY sc.fecha_proximo_vencimiento ASC
            ");
            $stmt->execute([$servicio_id]);
            $clientes = $stmt->fetchAll();
            
            $stmt = $pdo->prepare("SELECT nombre, descripcion, precio FROM tipos_servicios WHERE id = ?");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch();
            
            $response['success'] = true;
            $response['data'] = [
                'servicio' => $servicio,
                'clientes' => $clientes
            ];
            break;
            
        case 'get_tipo_servicio':
            $servicio_id = $_POST['servicio_id'];
            
            $stmt = $pdo->prepare("SELECT * FROM tipos_servicios WHERE id = ?");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch();
            
            if (!$servicio) {
                throw new Exception('Servicio no encontrado');
            }
            
            $response['success'] = true;
            $response['data'] = $servicio;
            break;
            
        case 'add_servicio_tipo':
            $nombre = $_POST['nombre'];
            $descripcion = $_POST['descripcion'];
            $precio = $_POST['precio'];
            
            $stmt = $pdo->prepare("INSERT INTO tipos_servicios (nombre, descripcion, precio) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $precio]);
            
            $response['success'] = true;
            $response['message'] = 'Tipo de servicio añadido correctamente';
            break;
            
        case 'edit_servicio_tipo':
            $id = $_POST['id'];
            $nombre = $_POST['nombre'];
            $descripcion = $_POST['descripcion'];
            $precio = $_POST['precio'];
            
            $stmt = $pdo->prepare("UPDATE tipos_servicios SET nombre=?, descripcion=?, precio=? WHERE id=?");
            $stmt->execute([$nombre, $descripcion, $precio, $id]);
            
            $response['success'] = true;
            $response['message'] = 'Tipo de servicio actualizado correctamente';
            break;
            
        case 'eliminar_tipo_servicio':
            $servicio_id = $_POST['servicio_id'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM servicios_cliente WHERE tipo_servicio_id = ?");
            $stmt->execute([$servicio_id]);
            $tiene_clientes = $stmt->fetch()['total'];
            
            if ($tiene_clientes > 0) {
                throw new Exception('No se puede eliminar un tipo de servicio que tiene clientes asignados');
            }
            
            $stmt = $pdo->prepare("DELETE FROM tipos_servicios WHERE id = ?");
            $stmt->execute([$servicio_id]);
            
            $response['success'] = true;
            $response['message'] = 'Tipo de servicio eliminado correctamente';
            break;
            
        case 'get_todos_servicios':
            $filtro_estado = $_POST['filtro_estado'] ?? '';
            $filtro_servicio = $_POST['filtro_servicio'] ?? '';
            $filtro_cliente = $_POST['filtro_cliente'] ?? '';
            $filtro_fecha_desde = $_POST['filtro_fecha_desde'] ?? '';
            $filtro_fecha_hasta = $_POST['filtro_fecha_hasta'] ?? '';
            $filtro_facturado = $_POST['filtro_facturado'] ?? '';
            
            $where_conditions = ["sc.activo = 1", "c.activo = 1"];
            $params = [];
            
            if (!empty($filtro_estado)) {
                switch ($filtro_estado) {
                    case 'urgente':
                        $where_conditions[] = "DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) <= 2";
                        break;
                    case 'proximo':
                       $where_conditions[] = "DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) BETWEEN 3 AND 7";
                        break;
                    case 'este_mes':
                        $where_conditions[] = "DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) BETWEEN 8 AND 30";
                        break;
                    case 'lejano':
                        $where_conditions[] = "DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) > 30";
                        break;
                }
            }
            
            if (!empty($filtro_servicio)) {
                $where_conditions[] = "sc.tipo_servicio_id = ?";
                $params[] = $filtro_servicio;
            }
            
            if (!empty($filtro_cliente)) {
                $where_conditions[] = "(c.nombre LIKE ? OR c.apellidos LIKE ? OR c.empresa LIKE ?)";
                $like_cliente = "%$filtro_cliente%";
                $params = array_merge($params, [$like_cliente, $like_cliente, $like_cliente]);
            }
            
            if (!empty($filtro_fecha_desde)) {
                $where_conditions[] = "sc.fecha_proximo_vencimiento >= ?";
                $params[] = $filtro_fecha_desde;
            }
            
            if (!empty($filtro_fecha_hasta)) {
                $where_conditions[] = "sc.fecha_proximo_vencimiento <= ?";
                $params[] = $filtro_fecha_hasta;
            }
            
            if ($filtro_facturado === 'si') {
                $where_conditions[] = "EXISTS (SELECT 1 FROM albaranes a JOIN albaran_lineas al ON a.id = al.albaran_id WHERE al.servicio_cliente_id = sc.id)";
            } elseif ($filtro_facturado === 'no') {
                $where_conditions[] = "NOT EXISTS (SELECT 1 FROM albaranes a JOIN albaran_lineas al ON a.id = al.albaran_id WHERE al.servicio_cliente_id = sc.id)";
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            
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
                WHERE $where_clause
                ORDER BY sc.fecha_proximo_vencimiento ASC
            ");
            $stmt->execute($params);
            $servicios = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $servicios;
            break;
            
        case 'get_albaranes':
            $filtro_cliente = $_POST['filtro_cliente'] ?? '';
            $filtro_estado = $_POST['filtro_estado'] ?? '';
            $filtro_fecha_desde = $_POST['filtro_fecha_desde'] ?? '';
            $filtro_fecha_hasta = $_POST['filtro_fecha_hasta'] ?? '';
            $filtro_monto_min = $_POST['filtro_monto_min'] ?? '';
            $filtro_monto_max = $_POST['filtro_monto_max'] ?? '';
            
            $where_conditions = [];
            $params = [];
            
            if (!empty($filtro_cliente)) {
                $where_conditions[] = "(c.nombre LIKE ? OR c.apellidos LIKE ? OR c.empresa LIKE ?)";
                $like_cliente = "%$filtro_cliente%";
                $params = array_merge($params, [$like_cliente, $like_cliente, $like_cliente]);
            }
            
            if (!empty($filtro_estado)) {
                $where_conditions[] = "a.estado = ?";
                $params[] = $filtro_estado;
            }
            
            if (!empty($filtro_fecha_desde)) {
                $where_conditions[] = "a.fecha_albaran >= ?";
                $params[] = $filtro_fecha_desde;
            }
            
            if (!empty($filtro_fecha_hasta)) {
                $where_conditions[] = "a.fecha_albaran <= ?";
                $params[] = $filtro_fecha_hasta;
            }
            
            if (!empty($filtro_monto_min)) {
                $where_conditions[] = "a.total >= ?";
                $params[] = $filtro_monto_min;
            }
            
            if (!empty($filtro_monto_max)) {
                $where_conditions[] = "a.total <= ?";
                $params[] = $filtro_monto_max;
            }
            
            $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
            
            $stmt = $pdo->prepare("
                SELECT a.*, c.nombre, c.apellidos, c.empresa,
                       COUNT(al.id) as total_lineas,
                       GROUP_CONCAT(ts.nombre SEPARATOR ', ') as servicios_nombres
                FROM albaranes a
                JOIN clientes c ON a.cliente_id = c.id
                LEFT JOIN albaran_lineas al ON a.id = al.albaran_id
                LEFT JOIN servicios_cliente sc ON al.servicio_cliente_id = sc.id
                LEFT JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
                $where_clause
                GROUP BY a.id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute($params);
            $albaranes = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $albaranes;
            break;
            
        case 'cambiar_estado_albaran':
            $albaran_id = $_POST['albaran_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $estados_validos = ['borrador', 'generado', 'enviado', 'pagado'];
            if (!in_array($nuevo_estado, $estados_validos)) {
                throw new Exception('Estado no válido');
            }
            
            $stmt = $pdo->prepare("UPDATE albaranes SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $albaran_id]);
            
            $response['success'] = true;
            $response['message'] = "Albarán marcado como $nuevo_estado";
            break;
            
        case 'eliminar_albaran':
            $albaran_id = $_POST['albaran_id'];
            
            $stmt = $pdo->prepare("DELETE FROM albaran_lineas WHERE albaran_id = ?");
            $stmt->execute([$albaran_id]);
            
            $stmt = $pdo->prepare("DELETE FROM albaranes WHERE id = ?");
            $stmt->execute([$albaran_id]);
            
            $response['success'] = true;
            $response['message'] = 'Albarán eliminado correctamente';
            break;
            
        case 'eliminar_albaranes_masivo':
            $albaranes_ids = $_POST['albaranes_ids'];
            
            if (empty($albaranes_ids) || !is_array($albaranes_ids)) {
                throw new Exception('No se han seleccionado albaranes');
            }
            
            $eliminados = 0;
            foreach ($albaranes_ids as $albaran_id) {
                $stmt = $pdo->prepare("DELETE FROM albaran_lineas WHERE albaran_id = ?");
                $stmt->execute([$albaran_id]);
                
                $stmt = $pdo->prepare("DELETE FROM albaranes WHERE id = ?");
                if ($stmt->execute([$albaran_id])) {
                    $eliminados++;
                }
            }
            
            $response['success'] = true;
            $response['message'] = "$eliminados albaranes eliminados correctamente";
            break;
            
        case 'generar_albaran':
            $servicios_ids = $_POST['servicios_ids'];
            
            if (empty($servicios_ids) || !is_array($servicios_ids)) {
                throw new Exception('No se han seleccionado servicios');
            }
            
            $stmt = $pdo->prepare("SELECT cliente_id FROM servicios_cliente WHERE id = ?");
            $stmt->execute([$servicios_ids[0]]);
            $cliente_id = $stmt->fetch()['cliente_id'];
            
            $siguiente = getConfig('siguiente_albaran') ?: 1;
            $numero_albaran = 'ALB-' . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
            
            $placeholders = implode(',', array_fill(0, count($servicios_ids), '?'));
            $stmt = $pdo->prepare("
                SELECT SUM(COALESCE(sc.precio_personalizado, ts.precio)) as total
                FROM servicios_cliente sc
                JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
                WHERE sc.id IN ($placeholders)
            ");
            $stmt->execute($servicios_ids);
            $total = $stmt->fetch()['total'];
            
            $stmt = $pdo->prepare("INSERT INTO albaranes (numero_albaran, cliente_id, fecha_albaran, total, estado) VALUES (?, ?, CURDATE(), ?, 'generado')");
            $stmt->execute([$numero_albaran, $cliente_id, $total]);
            $albaran_id = $pdo->lastInsertId();
            
            foreach ($servicios_ids as $servicio_id) {
                $stmt = $pdo->prepare("
                    SELECT sc.*, ts.nombre, ts.descripcion, 
                           COALESCE(sc.precio_personalizado, ts.precio) as precio_final
                    FROM servicios_cliente sc
                    JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
                    WHERE sc.id = ?
                ");
                $stmt->execute([$servicio_id]);
                $servicio = $stmt->fetch();
                
                $stmt = $pdo->prepare("INSERT INTO albaran_lineas (albaran_id, servicio_cliente_id, descripcion, cantidad, precio_unitario, total_linea) VALUES (?, ?, ?, 1, ?, ?)");
                $stmt->execute([
                    $albaran_id, 
                    $servicio_id, 
                    $servicio['nombre'] . ' - ' . $servicio['descripcion'], 
                    $servicio['precio_final'], 
                    $servicio['precio_final']
                ]);
            }
            
            updateConfig('siguiente_albaran', $siguiente + 1);
            
            $response['success'] = true;
            $response['data'] = [
                'albaran_id' => $albaran_id,
                'numero' => $numero_albaran,
                'total' => $total
            ];
            $response['message'] = "Albarán $numero_albaran generado correctamente";
            break;
            
        case 'enviar_notificacion_individual':
            $servicio_id = $_POST['servicio_id'];
            
            $stmt = $pdo->prepare("
                SELECT sc.*, c.nombre, c.apellidos, c.empresa, c.email, c.telefono,
                       ts.nombre as servicio_nombre, ts.descripcion, 
                       COALESCE(sc.precio_personalizado, ts.precio) as precio_final,
                       tv.nombre as vencimiento_tipo,
                       DATEDIFF(sc.fecha_proximo_vencimiento, CURDATE()) as dias_restantes
                FROM servicios_cliente sc
                JOIN clientes c ON sc.cliente_id = c.id
                JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
                JOIN tipos_vencimiento tv ON sc.tipo_vencimiento_id = tv.id
                WHERE sc.id = ? AND sc.activo = 1 AND c.activo = 1
            ");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch();
            
            if (!$servicio) {
                throw new Exception('Servicio no encontrado');
            }
            
            $empresa = [
                'nombre' => getConfig('empresa_nombre') ?: 'Tu Empresa',
                'email' => getConfig('empresa_email') ?: getConfig('email_admin'),
                'telefono' => getConfig('empresa_telefono') ?: '',
                'web' => getConfig('empresa_web') ?: ''
            ];
            
            $subject_cliente = "Aviso de Vencimiento - {$servicio['servicio_nombre']}";
            $message_cliente = generarEmailClienteHTML($servicio, $empresa);
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$empresa['nombre']} <{$empresa['email']}>\r\n";
            $headers .= "Reply-To: {$empresa['email']}\r\n";
            
            $enviado_cliente = mail($servicio['email'], $subject_cliente, $message_cliente, $headers);
            
            $email_admin = getConfig('email_admin');
            $subject_admin = "Notificación Enviada - {$servicio['servicio_nombre']} - {$servicio['nombre']} {$servicio['apellidos']}";
            $message_admin = "<h3>Notificación Individual Enviada</h3>";
            $message_admin .= "<p><strong>Cliente:</strong> {$servicio['nombre']} {$servicio['apellidos']}</p>";
            $message_admin .= "<p><strong>Email:</strong> {$servicio['email']}</p>";
            $message_admin .= "<p><strong>Servicio:</strong> {$servicio['servicio_nombre']}</p>";
            $message_admin .= "<p><strong>Vence:</strong> " . date('d/m/Y', strtotime($servicio['fecha_proximo_vencimiento'])) . "</p>";
            $message_admin .= "<p><strong>Días restantes:</strong> {$servicio['dias_restantes']}</p>";
            $message_admin .= "<p><strong>Estado envío:</strong> " . ($enviado_cliente ? 'Enviado correctamente' : 'Error en el envío') . "</p>";
            
            $enviado_admin = mail($email_admin, $subject_admin, $message_admin, $headers);
            
            if ($enviado_cliente || $enviado_admin) {
                $stmt = $pdo->prepare("INSERT INTO notificaciones_enviadas (servicio_cliente_id, tipo_notificacion) VALUES (?, 'individual_cliente')");
                $stmt->execute([$servicio_id]);
                
                $response['success'] = true;
                $response['message'] = 'Notificación enviada al cliente' . ($enviado_admin ? ' y al administrador' : '');
            } else {
                throw new Exception('Error enviando los emails');
            }
            break;
            
        case 'test_smtp':
            $test_email = $_POST['test_email'];
            $empresa_nombre = getConfig('empresa_nombre') ?: 'Sistema de Gestión';
            
            $subject = "Test de Configuración SMTP - $empresa_nombre";
            $message = "<h2>Test de Configuración SMTP</h2>";
            $message .= "<p>Si recibes este email, la configuración SMTP está funcionando correctamente.</p>";
            $message .= "<p><strong>Fecha del test:</strong> " . date('d/m/Y H:i:s') . "</p>";
            $message .= "<p><strong>Configuración actual:</strong></p>";
            $message .= "<ul>";
            $message .= "<li><strong>Servidor:</strong> " . getConfig('smtp_host') . "</li>";
            $message .= "<li><strong>Puerto:</strong> " . getConfig('smtp_port') . "</li>";
            $message .= "<li><strong>Usuario:</strong> " . getConfig('smtp_user') . "</li>";
            $message .= "</ul>";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: $empresa_nombre <" . getConfig('smtp_user') . ">\r\n";
            
            if (mail($test_email, $subject, $message, $headers)) {
                $response['success'] = true;
                $response['message'] = 'Email de prueba enviado correctamente';
            } else {
                throw new Exception('Error enviando el email de prueba');
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

function generarEmailClienteHTML($servicio, $empresa) {
    $urgencia = $servicio['dias_restantes'] <= 2 ? 'urgente' : ($servicio['dias_restantes'] <= 7 ? 'proximo' : 'normal');
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
            .servicio { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid {$color_urgencia[$urgencia]}; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; }
            .precio { font-size: 18px; font-weight: bold; color: #48bb78; }
            .dias-restantes { font-size: 20px; font-weight: bold; color: {$color_urgencia[$urgencia]}; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Aviso de Vencimiento</h1>
                <p>{$empresa['nombre']}</p>
            </div>
            
            <div class='urgencia'>
                Estimado/a {$servicio['nombre']} {$servicio['apellidos']}, su servicio vence en <span class='dias-restantes'>{$servicio['dias_restantes']} días</span>
            </div>
            
            <div class='content'>
                <div class='servicio'>
                    <h3>{$servicio['servicio_nombre']}</h3>
                    <p>{$servicio['descripcion']}</p>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin-top: 15px;'>
                        <div>
                            <strong>Fecha de vencimiento:</strong> " . date('d/m/Y', strtotime($servicio['fecha_proximo_vencimiento'])) . "<br>
                            <strong>Tipo de renovación:</strong> {$servicio['vencimiento_tipo']}
                        </div>
                        <div class='precio'>€" . number_format($servicio['precio_final'], 2) . "</div>
                    </div>
                </div>
                
                <div style='text-align: center; margin: 30px 0; background: #e6fffa; padding: 20px; border-radius: 8px;'>
                    <h3>¿Qué hacer ahora?</h3>
                    <p>Póngase en contacto con nosotros para renovar su servicio y evitar interrupciones.</p>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>{$empresa['nombre']}</strong></p>
                <p>{$empresa['email']}" . ($empresa['telefono'] ? " | {$empresa['telefono']}" : "") . "</p>
                " . ($empresa['web'] ? "<p>{$empresa['web']}</p>" : "") . "
                <p style='margin-top: 20px; font-size: 12px; color: #999;'>
                    Este email fue enviado automáticamente el " . date('d/m/Y H:i') . "
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}
?>