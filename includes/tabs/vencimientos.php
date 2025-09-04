<div id="vencimientos" class="tab-content">
<div class="tab-pane">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2>📅 Todos los Servicios - Por Vencimiento</h2>
            <p>Listado completo de servicios activos ordenados por fecha de vencimiento</p>
        </div>
        <div class="actions-group">
            <button class="btn btn-warning" onclick="renovarMasivo()">?? Renovar Seleccionados</button>
            <button class="btn btn-info" onclick="enviarNotificacionesMasivas()">?? Notificaciones Masivas</button>
        </div>
    </div>

    <!-- Filtros Avanzados -->
    <div class="filters">
        <h4>🔍 Filtros Avanzados</h4>
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
        <div class="filter-row">
            <div class="form-group">
                <label>Desde:</label>
                <input type="date" id="filtroFechaDesde" onchange="aplicarFiltros()">
            </div>
            <div class="form-group">
                <label>Hasta:</label>
                <input type="date" id="filtroFechaHasta" onchange="aplicarFiltros()">
            </div>
            <div class="form-group">
                <label>Facturado:</label>
                <select id="filtroFacturado" onchange="aplicarFiltros()">
                    <option value="">Todos</option>
                    <option value="si">? Facturado</option>
                    <option value="no">? Sin facturar</option>
                </select>
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

    <?php if (!empty($datos['proximos_vencimientos'])): ?>
    <div style="margin-bottom: 20px;">
        <div class="alert warning">
            ?? <strong><?php echo count($datos['proximos_vencimientos']); ?> servicios</strong> requieren atención en los próximos <strong><?php echo getConfig('dias_aviso') ?: 5; ?> días</strong>
        </div>
    </div>
    <?php endif; ?>
    
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