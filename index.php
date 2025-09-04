<?php
require_once 'config.php';
require_once 'includes/data_logic.php';

$mensaje = '';
$error = '';
$tab_activa = $_GET['tab'] ?? 'vencimientos';

// Procesar formularios POST
if ($_POST) {
    try {
        $resultado = procesarFormulario($_POST, $pdo);
        if ($resultado) {
            session_start();
            $_SESSION['mensaje'] = $resultado;
            
            $tab_preservar = $_POST['current_tab'] ?? $tab_activa;
            header("Location: " . $_SERVER['PHP_SELF'] . "?tab=" . $tab_preservar);
            exit();
        }
    } catch(Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

session_start();
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

$datos = cargarDatosSistema($pdo);
$datos['tab_activa'] = $tab_activa;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Servicios Profesional</title>
    <link rel="stylesheet" href="assets/css/styles-new.css">
</head>
<body>
    <div class="header">
        <h1>📊 Gestión de Servicios</h1>
        <div class="stats-mini">
            <div class="stat-mini urgente" data-filter="urgente">
                <strong><?php echo $datos['estadisticas_generales']['servicios_urgentes'] ?? 0; ?></strong>
                <span>Urgentes</span>
            </div>
            <div class="stat-mini" data-filter="proximo">
                <strong><?php echo ($datos['estadisticas_generales']['proximos_vencimientos'] ?? 0) - ($datos['estadisticas_generales']['servicios_urgentes'] ?? 0); ?></strong>
                <span>Esta semana</span>
            </div>
            <div class="stat-mini" data-filter="todos">
                <strong><?php echo $datos['estadisticas_generales']['total_servicios'] ?? 0; ?></strong>
                <span>Total servicios</span>
            </div>
            <div class="stat-mini">
                <strong>€<?php echo number_format($datos['estadisticas_generales']['ingresos_mensuales'] ?? 0, 0); ?></strong>
                <span>Mes</span>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="alert success">✅ <?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab <?php echo $datos['tab_activa'] === 'vencimientos' ? 'active' : ''; ?>" data-tab="vencimientos">
                📅 Vencimientos (<?php echo $datos['estadisticas_generales']['total_servicios'] ?? 0; ?>)
            </button>
            <button class="tab <?php echo $datos['tab_activa'] === 'clientes' ? 'active' : ''; ?>" data-tab="clientes">
                👥 Clientes (<?php echo $datos['estadisticas_generales']['total_clientes'] ?? 0; ?>)
            </button>
            <button class="tab <?php echo $datos['tab_activa'] === 'albaranes' ? 'active' : ''; ?>" data-tab="albaranes">
                📄 Albaranes (<?php echo $datos['estadisticas_generales']['total_albaranes'] ?? 0; ?>)
            </button>
            <button class="tab <?php echo $datos['tab_activa'] === 'config' ? 'active' : ''; ?>" data-tab="config">
                ⚙️ Configuración
            </button>
        </div>

        <!-- TAB VENCIMIENTOS -->
        <div id="vencimientos" class="tab-content <?php echo $datos['tab_activa'] === 'vencimientos' ? 'active' : ''; ?>">
            <div class="controls">
                <div class="urgency-filters">
                    <div class="filter-pill active" data-filter="todos">📋 Todos (<?php echo $datos['estadisticas_generales']['total_servicios'] ?? 0; ?>)</div>
                    <div class="filter-pill urgente" data-filter="urgente">🚨 Urgentes (<?php echo $datos['estadisticas_generales']['servicios_urgentes'] ?? 0; ?>)</div>
                    <div class="filter-pill proximo" data-filter="proximo">⏰ Esta semana (<?php echo ($datos['estadisticas_generales']['proximos_vencimientos'] ?? 0) - ($datos['estadisticas_generales']['servicios_urgentes'] ?? 0); ?>)</div>
                    <div class="filter-pill mes" data-filter="mes">📅 Este mes (<?php echo ($datos['estadisticas_generales']['total_servicios'] ?? 0) - ($datos['estadisticas_generales']['proximos_vencimientos'] ?? 0); ?>)</div>
                </div>
                
                <div class="search-row">
                    <input type="text" class="search-input" placeholder="Buscar cliente, empresa..." id="searchInput">
                    <select class="search-input" id="serviceFilter">
                        <option value="">Todos los servicios</option>
                        <?php foreach($datos['tipos_servicios'] as $ts): ?>
                        <option value="<?php echo $ts['id']; ?>"><?php echo htmlspecialchars($ts['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" onclick="aplicarFiltros()">🔍 Filtrar</button>
                    <button class="btn btn-success" onclick="openModal('modalCliente')">➕ Nuevo Cliente</button>
                </div>
            </div>

            <div class="bulk-bar" id="bulkBar">
                <span id="selectedCount">0 servicios seleccionados</span>
                <div class="bulk-actions">
                    <button class="btn btn-success" onclick="renovarSeleccionados()">🔄 Renovar</button>
                    <button class="btn btn-warning" onclick="enviarAvisosSeleccionados()">📧 Avisar</button>
                    <button class="btn btn-primary" onclick="generarAlbaranSeleccionados()">📄 Albarán</button>
                </div>
            </div>

            <div class="loading" id="loading">
                <div>🔄 Cargando servicios...</div>
            </div>

            <div class="services-container" id="servicesContainer">
                <!-- Los servicios se cargarán aquí dinámicamente -->
            </div>
        </div>

        <!-- TAB CLIENTES -->
        <div id="clientes" class="tab-content <?php echo $datos['tab_activa'] === 'clientes' ? 'active' : ''; ?>">
            <div class="tab-header">
                <h2>👥 Gestión de Clientes</h2>
                <button class="btn btn-primary" onclick="openModal('modalCliente')">➕ Añadir Cliente</button>
            </div>

            <div class="filters">
                <div class="search-row">
                    <input type="text" value="<?php echo htmlspecialchars($datos['search']); ?>" 
                           placeholder="Buscar cliente..." id="clientSearchInput" onkeypress="if(event.key==='Enter') searchClientes()">
                    <button class="btn btn-primary" onclick="searchClientes()">🔍 Buscar</button>
                    <button class="btn btn-secondary" onclick="clearSearch()">❌ Limpiar</button>
                </div>
            </div>

            <div class="clients-grid">
                <?php foreach($datos['clientes'] as $cliente): 
                    $dias_venc = $cliente['proximo_vencimiento'] ? (strtotime($cliente['proximo_vencimiento']) - time()) / (60*60*24) : null;
                    $clase_venc = '';
                    if ($dias_venc !== null) {
                        if ($dias_venc <= 2) $clase_venc = 'urgente';
                        elseif ($dias_venc <= 7) $clase_venc = 'proximo';
                        elseif ($dias_venc <= 30) $clase_venc = 'ok';
                        else $clase_venc = 'lejano';
                    }
                ?>
                <div class="client-card <?php echo $clase_venc; ?>">
                    <div class="client-info">
                        <h4><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellidos']); ?></h4>
                        <?php if($cliente['empresa']): ?>
                            <p class="empresa">🏢 <?php echo htmlspecialchars($cliente['empresa']); ?></p>
                        <?php endif; ?>
                        <div class="contact-info">
                            <span>📧 <?php echo htmlspecialchars($cliente['email']); ?></span>
                            <?php if($cliente['telefono']): ?>
                                <span>📱 <?php echo htmlspecialchars($cliente['telefono']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="client-stats">
                        <div class="stat-item">
                            <strong><?php echo $cliente['servicios_activos']; ?></strong>
                            <span>Servicios activos</span>
                        </div>
                        <?php if($cliente['proximo_vencimiento']): ?>
                            <div class="stat-item">
                                <strong><?php echo date('d/m/Y', strtotime($cliente['proximo_vencimiento'])); ?></strong>
                                <span>Próximo venc.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="client-actions">
                        <button class="btn btn-xs btn-primary" onclick="verCliente(<?php echo $cliente['id']; ?>)">👁️ Ver</button>
                        <button class="btn btn-xs btn-success" onclick="editarCliente(<?php echo $cliente['id']; ?>)">✏️ Editar</button>
                        <button class="btn btn-xs btn-warning" onclick="asignarServicio(<?php echo $cliente['id']; ?>)">➕ Servicio</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <?php if ($datos['total_pages'] > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $datos['total_pages']; $i++): ?>
                    <?php if($i == $datos['current_page']): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?tab=clientes&page=<?php echo $i; ?>&search=<?php echo urlencode($datos['search']); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- TAB ALBARANES -->
        <div id="albaranes" class="tab-content <?php echo $datos['tab_activa'] === 'albaranes' ? 'active' : ''; ?>">
            <div class="tab-header">
                <h2>📄 Gestión de Albaranes</h2>
                <div class="actions-group">
                    <button class="btn btn-danger" onclick="eliminarAlbaranesMasivo()" id="btnEliminarMasivo" style="display: none;">🗑️ Eliminar Seleccionados</button>
                </div>
            </div>

            <!-- Filtros para Albaranes -->
            <div class="filters">
                <h4>🔍 Filtros de Albaranes</h4>
                <div class="filter-row">
                    <div class="form-group">
                        <label>Cliente:</label>
                        <input type="text" id="filtroAlbaranCliente" placeholder="Buscar cliente..." onkeyup="aplicarFiltrosAlbaranes()">
                    </div>
                    <div class="form-group">
                        <label>Estado:</label>
                        <select id="filtroAlbaranEstado" onchange="aplicarFiltrosAlbaranes()">
                            <option value="">Todos los estados</option>
                            <option value="borrador">📝 Borrador</option>
                            <option value="generado">✅ Generado</option>
                            <option value="enviado">📧 Enviado</option>
                            <option value="pagado">💰 Pagado</option>
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="form-group">
                        <label>Desde:</label>
                        <input type="date" id="filtroAlbaranFechaDesde" onchange="aplicarFiltrosAlbaranes()">
                    </div>
                    <div class="form-group">
                        <label>Hasta:</label>
                        <input type="date" id="filtroAlbaranFechaHasta" onchange="aplicarFiltrosAlbaranes()">
                    </div>
                    <div class="form-group">
                        <label>Monto mín:</label>
                        <input type="number" id="filtroAlbaranMontoMin" placeholder="0.00" onchange="aplicarFiltrosAlbaranes()">
                    </div>
                    <div class="form-group">
                        <label>Monto máx:</label>
                        <input type="number" id="filtroAlbaranMontoMax" placeholder="9999.99" onchange="aplicarFiltrosAlbaranes()">
                    </div>
                </div>
                <div class="form-inline">
                    <button class="btn btn-primary" onclick="aplicarFiltrosAlbaranes()">🔍 Aplicar</button>
                    <button class="btn btn-secondary" onclick="limpiarFiltrosAlbaranes()">❌ Limpiar</button>
                </div>
            </div>
            
            <div class="stats-grid" style="margin-bottom: 30px;" id="statsAlbaranes">
                <!-- Se llenará dinámicamente -->
            </div>

            <!-- Selección múltiple -->
            <div class="selection-controls">
                <label>
                    <input type="checkbox" id="selectAllAlbaranes" onchange="toggleSelectAllAlbaranes()"> Seleccionar todos
                </label>
                <span style="margin-left: 20px;" id="selectedAlbaranesCount">0 albaranes seleccionados</span>
            </div>
            
            <div class="table-container">
                <table id="tablaAlbaranes">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllAlbaranesHeader" onchange="toggleSelectAllAlbaranes()"></th>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Servicios</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="albaranesTableBody">
                        <!-- Se llenará dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB CONFIGURACIÓN -->
        <div id="config" class="tab-content <?php echo $datos['tab_activa'] === 'config' ? 'active' : ''; ?>">
            <div class="tab-header">
                <h2>⚙️ Configuración del Sistema</h2>
            </div>
            
            <form method="POST" style="max-width: 900px;">
                <input type="hidden" name="action" value="update_config">
                <input type="hidden" name="current_tab" value="config">
                
                <h3 style="margin-bottom: 20px; color: #667eea;">📧 Configuración de Notificaciones</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Días de aviso antes del vencimiento:</label>
                        <input type="number" name="dias_aviso" value="<?php echo getConfig('dias_aviso'); ?>" required min="1" max="30">
                        <small style="color: #718096;">Número de días antes del vencimiento para recibir avisos</small>
                    </div>
                    <div class="form-group">
                        <label>Email principal para avisos:</label>
                        <input type="email" name="email_admin" value="<?php echo getConfig('email_admin'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Emails adicionales (separados por comas):</label>
                    <input type="text" name="emails_copia" value="<?php echo getConfig('emails_copia'); ?>" 
                           placeholder="email1@ejemplo.com, email2@ejemplo.com">
                    <small style="color: #718096;">Emails adicionales que recibirán copia de los avisos</small>
                </div>
                
                <h3 style="margin: 40px 0 20px 0; color: #667eea;">📮 Configuración SMTP</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Servidor SMTP:</label>
                        <input type="text" name="smtp_host" value="<?php echo getConfig('smtp_host'); ?>" 
                               placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Puerto SMTP:</label>
                        <input type="number" name="smtp_port" value="<?php echo getConfig('smtp_port'); ?>" 
                               placeholder="587">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Usuario SMTP:</label>
                        <input type="text" name="smtp_user" value="<?php echo getConfig('smtp_user'); ?>" 
                               placeholder="tu_email@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Contraseña SMTP:</label>
                        <input type="password" name="smtp_pass" value="<?php echo getConfig('smtp_pass'); ?>" 
                               placeholder="tu_contraseña_app">
                    </div>
                </div>
                
                <!-- Test de configuración SMTP -->
                <div class="test-config">
                    <h4 style="color: #38b2ac;">🧪 Test de Configuración SMTP</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email de prueba:</label>
                            <input type="email" id="testEmail" value="<?php echo getConfig('email_admin'); ?>" 
                                   placeholder="email@ejemplo.com">
                        </div>
                        <div class="form-group" style="display: flex; align-items: end;">
                            <button type="button" class="btn btn-info" onclick="testSMTP()" style="width: 100%;">
                                📤 Enviar Email de Prueba
                            </button>
                        </div>
                    </div>
                </div>
                
                <h3 style="margin: 40px 0 20px 0; color: #667eea;">🏢 Datos de la Empresa</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre de la empresa:</label>
                        <input type="text" name="empresa_nombre" value="<?php echo getConfig('empresa_nombre'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>CIF/NIF:</label>
                        <input type="text" name="empresa_cif" value="<?php echo getConfig('empresa_cif'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Dirección:</label>
                    <input type="text" name="empresa_direccion" value="<?php echo getConfig('empresa_direccion'); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Ciudad:</label>
                        <input type="text" name="empresa_ciudad" value="<?php echo getConfig('empresa_ciudad'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Código Postal:</label>
                        <input type="text" name="empresa_codigo_postal" value="<?php echo getConfig('empresa_codigo_postal'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Teléfono:</label>
                        <input type="text" name="empresa_telefono" value="<?php echo getConfig('empresa_telefono'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email de la empresa:</label>
                        <input type="email" name="empresa_email" value="<?php echo getConfig('empresa_email'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Sitio web:</label>
                    <input type="text" name="empresa_web" value="<?php echo getConfig('empresa_web'); ?>" 
                           placeholder="www.tuempresa.com">
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">💾 Guardar Configuración</button>
            </form>
            
            <!-- RESET DE BASE DE DATOS -->
            <div style="margin-top: 50px; padding-top: 30px; border-top: 2px solid #f56565;">
                <h3 style="color: #f56565;">⚠️ Zona Peligrosa - Reset de Base de Datos</h3>
                <div style="background: #fff5f5; border: 1px solid #f56565; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p style="color: #721c24; margin-bottom: 15px;"><strong>⚠️ ADVERTENCIA:</strong> Selecciona qué datos quieres eliminar:</p>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="resetClientes">
                                <span>👥 Clientes</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="resetServicios">
                                <span>⚙️ Tipos de Servicios</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="resetServiciosCliente">
                                <span>📋 Servicios de Clientes</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="resetAlbaranes">
                                <span>📄 Albaranes</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="resetNotificaciones">
                                <span>📧 Notificaciones</span>
                            </label>
                        </div>
                        
                        <label style="display: flex; align-items: center; gap: 10px; margin-top: 20px; background: #fff5f5; padding: 10px; border-radius: 5px;">
                            <input type="checkbox" id="confirmReset">
                            <span>Confirmo que quiero eliminar los datos seleccionados (SIN datos de ejemplo)</span>
                        </label>
                    </div>

                    <button type="button" class="btn btn-danger" onclick="resetDatabase()" id="btnReset" disabled>
                        🗑️ Eliminar Datos Seleccionados
                    </button>
                </div>
            </div>
            
            <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e2e8f0;">
                <h3>📊 Estadísticas del Sistema</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $datos['estadisticas_generales']['total_clientes'] ?? 0; ?></div>
                        <div class="stat-label">Clientes Activos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $datos['estadisticas_generales']['total_servicios'] ?? 0; ?></div>
                        <div class="stat-label">Servicios Activos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $datos['estadisticas_generales']['proximos_vencimientos'] ?? 0; ?></div>
                        <div class="stat-label">Próximos Vencimientos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">€<?php echo number_format($datos['estadisticas_generales']['ingresos_mensuales'] ?? 0, 2); ?></div>
                        <div class="stat-label">Ingresos Mensuales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $datos['estadisticas_generales']['servicios_urgentes'] ?? 0; ?></div>
                        <div class="stat-label">Servicios Urgentes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">€<?php echo number_format($datos['estadisticas_generales']['facturacion_pendiente'] ?? 0, 2); ?></div>
                        <div class="stat-label">Facturación Pendiente</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/modals.php'; ?>
    
    <script>
        const DATOS_SISTEMA = <?php echo json_encode($datos); ?>;
    </script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/modals.js"></script>
    <script src="assets/js/clientes.js"></script>
    <script src="assets/js/servicios-new.js"></script>
    <script src="assets/js/albaranes.js"></script>
    <script src="assets/js/config.js"></script>
    <script src="assets/js/main-new.js"></script>
</body>
</html>