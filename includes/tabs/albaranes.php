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
            <button class="btn btn-primary" onclick="aplicarFiltrosAlbaranes()">?? Aplicar</button>
            <button class="btn btn-secondary" onclick="limpiarFiltrosAlbaranes()">??? Limpiar</button>
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