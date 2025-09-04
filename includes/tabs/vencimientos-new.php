<div id="vencimientos" class="tab-content <?php echo $datos['tab_activa'] === 'vencimientos' ? 'active' : ''; ?>">
    <div class="controls">
        <div class="urgency-filters">
            <div class="filter-pill active" data-filter="todos">?? Todos (<?php echo $datos['estadisticas_generales']['total_servicios']; ?>)</div>
            <div class="filter-pill urgente" data-filter="urgente">?? Urgentes (<?php echo $datos['estadisticas_generales']['servicios_urgentes']; ?>)</div>
            <div class="filter-pill proximo" data-filter="proximo">? Esta semana (5)</div>
            <div class="filter-pill mes" data-filter="mes">?? Este mes (12)</div>
        </div>
        
        <div class="search-row">
            <input type="text" class="search-input" placeholder="Buscar cliente, empresa..." id="searchInput">
            <select class="search-input" id="serviceFilter">
                <option value="">Todos los servicios</option>
                <?php foreach($datos['tipos_servicios'] as $ts): ?>
                <option value="<?php echo $ts['id']; ?>"><?php echo htmlspecialchars($ts['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" onclick="aplicarFiltros()">?? Filtrar</button>
            <button class="btn btn-success" onclick="openModal('modalCliente')">? Nuevo Cliente</button>
        </div>
    </div>

    <div class="bulk-bar" id="bulkBar">
        <span id="selectedCount">0 servicios seleccionados</span>
        <div class="bulk-actions">
            <button class="btn btn-success" onclick="renovarSeleccionados()">?? Renovar</button>
            <button class="btn btn-warning" onclick="enviarAvisosSeleccionados()">?? Avisar</button>
            <button class="btn btn-primary" onclick="generarAlbaranSeleccionados()">?? Albarán</button>
        </div>
    </div>

    <div class="loading" id="loading">
        <div>?? Cargando servicios...</div>
    </div>

    <div class="services-container" id="servicesContainer">
        <!-- Los servicios se cargarán aquí dinámicamente -->
    </div>
</div>