<!-- TAB CLIENTES -->
<div id="clientes" class="tab-content active">
    <div class="tab-pane">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>?? Gestión de Clientes</h2>
            <button class="btn btn-primary" onclick="openModal('modalCliente')">? Añadir Cliente</button>
        </div>
        
        <div class="filters">
            <h4>?? Filtros y Búsqueda</h4>
            <div class="search-bar">
                <div class="form-group" style="margin: 0; flex: 1;">
                    <label>Buscar cliente:</label>
                    <input type="text" id="searchInput" value="<?php echo htmlspecialchars($datos['search']); ?>" 
                           placeholder="Nombre, empresa, email..." onkeypress="if(event.key==='Enter') searchClientes()">
                </div>
                <div class="form-inline">
                    <button class="btn btn-primary" onclick="searchClientes()">?? Buscar</button>
                    <button class="btn btn-secondary" onclick="clearSearch()">??? Limpiar</button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Servicios</th>
                        <th>Próximo Venc.</th>
                        <th>Albaranes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($datos['clientes'] as $cliente): 
                        $dias_venc = $cliente['proximo_vencimiento'] ? (strtotime($cliente['proximo_vencimiento']) - time()) / (60*60*24) : null;
                        $clase_venc = '';
                        if ($dias_venc !== null) {
                            if ($dias_venc <= 2) $clase_venc = 'vencimiento-urgente';
                            elseif ($dias_venc <= 7) $clase_venc = 'vencimiento-proximo';
                            elseif ($dias_venc <= 30) $clase_venc = 'vencimiento-ok';
                            else $clase_venc = 'vencimiento-lejano';
                        }
                    ?>
                    <tr class="<?php echo $clase_venc; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellidos']); ?></strong><br>
                            <small>?? <?php echo htmlspecialchars($cliente['ciudad'] ?: 'Sin ciudad'); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($cliente['empresa'] ?: '-'); ?></td>
                        <td>
                            <div>?? <?php echo htmlspecialchars($cliente['email']); ?></div>
                            <div>?? <?php echo htmlspecialchars($cliente['telefono'] ?: 'Sin teléfono'); ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-success">
                                <?php echo $cliente['servicios_activos']; ?> activos
                            </span><br>
                            <small><?php echo $cliente['total_servicios']; ?> total</small>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($cliente['proximo_vencimiento']): ?>
                                <?php echo date('d/m/Y', strtotime($cliente['proximo_vencimiento'])); ?><br>
                                <small>(<?php echo (int)$dias_venc; ?> días)</small>
                            <?php else: ?>
                                <span class="badge badge-secondary">Sin servicios</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($cliente['albaranes_generados'] > 0): ?>
                                <span class="badge badge-info"><?php echo $cliente['albaranes_generados']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-group">
                                <button class="btn btn-small btn-primary" onclick="verCliente(<?php echo $cliente['id']; ?>)" title="Ver detalles">???</button>
                                <button class="btn btn-small btn-success" onclick="editarCliente(<?php echo $cliente['id']; ?>)" title="Editar">??</button>
                                <button class="btn btn-small btn-warning" onclick="asignarServicio(<?php echo $cliente['id']; ?>)" title="Añadir servicio">?</button>
                                <button class="btn btn-small btn-danger" onclick="eliminarClienteSeguro(<?php echo $cliente['id']; ?>)" title="Eliminar">???</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($datos['clientes'])): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #a0aec0;">
                            <div style="font-size: 3em;">??</div>
                            <div style="margin-top: 10px;">No hay clientes que mostrar</div>
                            <button class="btn btn-primary" onclick="openModal('modalCliente')" style="margin-top: 15px;">Añadir Primer Cliente</button>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($datos['total_pages'] > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $datos['total_pages']; $i++): ?>
                <?php if($i == $datos['current_page']): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($datos['search']); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- TAB SERVICIOS -->
<div id="servicios" class="tab-content">
    <div class="tab-pane">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>?? Gestión de Servicios</h2>
            <button class="btn btn-primary" onclick="openModal('modalTipoServicio')">? Añadir Tipo de Servicio</button>
        </div>
        
        <div class="stats-grid">
            <?php foreach($datos['estadisticas_servicios'] as $servicio): ?>
            <div class="stat-card" onclick="verClientesServicio(<?php echo $servicio['id']; ?>)" title="Click para ver clientes">
                <div class="stat-number"><?php echo $servicio['clientes_distintos']; ?></div>
                <div class="stat-label"><?php echo htmlspecialchars($servicio['nombre']); ?></div>
                <div style="margin-top: 10px; font-size: 0.9em; color: #667eea;">
                    €<?php echo number_format($servicio['precio'], 2); ?> | <?php echo $servicio['activas']; ?> activas
                </div>
                <div style="margin-top: 5px; font-size: 0.85em; color: #48bb78;">
                    ?? €<?php echo number_format($servicio['ingresos_mensuales'], 2); ?>/mes
                </div>
                <div class="stat-detail">
                    <?php echo $servicio['total_contrataciones']; ?> contrataciones | 
                    <?php if ($servicio['proximos_vencer'] > 0): ?>
                        <span style="color: #ed8936;">?? <?php echo $servicio['proximos_vencer']; ?> próximos</span>
                    <?php else: ?>
                        <span style="color: #48bb78;">? Al día</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- TAB TODOS LOS SERVICIOS / VENCIMIENTOS -->
<div id="vencimientos" class="tab-content">
    <div class="tab-pane">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2>?? Todos los Servicios - Por Vencimiento</h2>
                <p>Listado completo de servicios activos ordenados por fecha de vencimiento</p>
            </div>
            <div class="actions-group">
                <button class="btn btn-warning" onclick="renovarMasivo()">?? Renovar Seleccionados</button>
                <button class="btn btn-info" onclick="enviarNotificacionesMasivas()">?? Notificaciones Masivas</button>
            </div>
        </div>

        <!-- Filtros Avanzados -->
        <div class="filters">
            <h4>?? Filtros Avanzados</h4>
            <div class="filter-row">
                <div class="form-group">
                    <label>Estado:</label>
                    <select id="filtroEstado" onchange="aplicarFiltros()">
                        <option value="">Todos los estados</option>
                        <option value="urgente">?? Urgente (=2 días)</option>
                        <option value="proximo">?? Próximo (3-7 días)</option>
                        <option value="este_mes">?? Este mes (8-30 días)</option>
                        <option value="lejano">? Lejano (>30 días)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de Servicio:</label>
                    <select id="filtroServicio" onchange="aplicarFiltros()">
                        <option value="">Todos los servicios</option>
                        <?php foreach($datos['tipos_servicios'] as $ts): ?>
                        <option value="<?php echo $ts['id']; ?>"><?php echo htmlspecialchars($ts['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cliente:</label>
                    <input type="text" id="filtroCliente" placeholder="Buscar cliente..." onkeyup="aplicarFiltros()">
                </div>
            </div>
            <div class="form-inline">
                <button class="btn btn-primary" onclick="aplicarFiltros()">?? Aplicar Filtros</button>
                <button class="btn btn-secondary" onclick="limpiarFiltros()">??? Limpiar Filtros</button>
            </div>
        </div>

        <!-- Controles de Selección -->
        <div class="selection-controls">
            <label>
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"> Seleccionar todo
            </label>
            <span style="margin-left: 20px;" id="selectedCount">0 servicios seleccionados</span>
        </div>

        <!-- Acciones Masivas -->
        <div class="bulk-actions" id="bulkActions">
            <strong>Acciones masivas:</strong>
            <div class="actions-group" style="margin-top: 10px;">
                <button class="btn btn-success" onclick="renovarSeleccionados()">?? Renovar</button>
                <button class="btn btn-info" onclick="generarAlbaranSeleccionados()">?? Generar Albaranes</button>
                <button class="btn btn-warning" onclick="enviarNotificacionesSeleccionadas()">?? Enviar Avisos</button>
            </div>
        </div>
        
        <div class="table-container">
            <table id="tablaServicios">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllHeader" onchange="toggleSelectAll()"></th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Precio</th>
                        <th>Vencimiento</th>
                        <th>Días Rest.</th>
                        <th>Estado</th>
                        <th>Contacto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="serviciosTableBody">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 30px;">
            <h4>?? Resumen Económico</h4>
            <div class="stats-grid" id="resumenEconomico">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- TAB ALBARANES -->
<div id="albaranes" class="tab-content">
    <div class="tab-pane">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>?? Gestión de Albaranes</h2>
            <div class="actions-group">
                <button class="btn btn-danger" onclick="eliminarAlbaranesMasivo()" id="btnEliminarMasivo" style="display: none;">??? Eliminar Seleccionados</button>
            </div>
        </div>

        <!-- Filtros para Albaranes -->
        <div class="filters">
            <h4>?? Filtros de Albaranes</h4>
            <div class="filter-row">
                <div class="form-group">
                    <label>Cliente:</label>
                    <input type="text" id="filtroAlbaranCliente" placeholder="Buscar cliente..." onkeyup="aplicarFiltrosAlbaranes()">
                </div>
                <div class="form-group">
                    <label>Estado:</label>
                    <select id="filtroAlbaranEstado" onchange="aplicarFiltrosAlbaranes()">
                        <option value="">Todos los estados</option>
                        <option value="borrador">?? Borrador</option>
                        <option value="generado">? Generado</option>
                        <option value="enviado">?? Enviado</option>
                        <option value="pagado">?? Pagado</option>
                    </select>
                </div>
            </div>
            <div class="form-inline">
                <button class="btn btn-primary" onclick="aplicarFiltrosAlbaranes()">?? Aplicar</button>
                <button class="btn btn-secondary" onclick="limpiarFiltrosAlbaranes()">??? Limpiar</button>
            </div>
        </div>
        
        <div class="stats-grid" style="margin-bottom: 30px;" id="statsAlbaranes">
            <!-- Se llenará dinámicamente -->
        </div>
        
        <div class="table-container">
            <table id="tablaAlbaranes">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllAlbaranes" onchange="toggleSelectAllAlbaranes()"></th>
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
</div>

<!-- TAB CONFIGURACIÓN -->
<div id="config" class="tab-content">
    <div class="tab-pane">
        <h2>?? Configuración del Sistema</h2>
        
        <form method="POST" style="max-width: 900px;">
            <input type="hidden" name="action" value="update_config">
            
            <h3 style="margin-bottom: 20px; color: #667eea;">?? Configuración de Notificaciones</h3>
            
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
            
            <h3 style="margin: 40px 0 20px 0; color: #667eea;">?? Configuración SMTP</h3>
            
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
                <h4 style="color: #38b2ac;">?? Test de Configuración SMTP</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email de prueba:</label>
                        <input type="email" id="testEmail" value="<?php echo getConfig('email_admin'); ?>" 
                               placeholder="email@ejemplo.com">
                    </div>
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="button" class="btn btn-info" onclick="testSMTP()" style="width: 100%;">
                            ?? Enviar Email de Prueba
                        </button>
                    </div>
                </div>
            </div>
            
            <h3 style="margin: 40px 0 20px 0; color: #667eea;">?? Datos de la Empresa</h3>
            
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
            
            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">?? Guardar Configuración</button>
        </form>
        
        <!-- RESET DE BASE DE DATOS -->
        <div style="margin-top: 50px; padding-top: 30px; border-top: 2px solid #f56565;">
            <h3 style="color: #f56565;">??? Zona Peligrosa - Reset de Base de Datos</h3>
            <div style="background: #fff5f5; border: 1px solid #f56565; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p style="color: #721c24; margin-bottom: 15px;"><strong>?? ADVERTENCIA:</strong> Esta acción eliminará TODOS los datos del sistema y los reemplazará con datos de ejemplo.</p>
                
                <<div style="margin-bottom: 20px;">
    <p><strong>Selecciona qué datos quieres eliminar:</strong></p>
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
        
        <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e2e8f0;">
            <h3>?? Estadísticas del Sistema</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['total_clientes']; ?></div>
                    <div class="stat-label">Clientes Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['total_servicios']; ?></div>
                    <div class="stat-label">Servicios Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['proximos_vencimientos']; ?></div>
                    <div class="stat-label">Próximos Vencimientos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">€<?php echo number_format($datos['estadisticas_generales']['ingresos_mensuales'], 2); ?></div>
                    <div class="stat-label">Ingresos Mensuales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['servicios_urgentes']; ?></div>
                    <div class="stat-label">Servicios Urgentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">€<?php echo number_format($datos['estadisticas_generales']['facturacion_pendiente'], 2); ?></div>
                    <div class="stat-label">Facturación Pendiente</div>
                </div>
            </div>
        </div>
    </div>
</div>