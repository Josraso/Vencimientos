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
                <?php foreach($datos['clientes']['data'] as $cliente): 
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
                
                <?php if (empty($datos['clientes']['data'])): ?>
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

    <?php if ($datos['clientes']['total_pages'] > 1): ?>
    <div class="pagination">
        <?php for($i = 1; $i <= $datos['clientes']['total_pages']; $i++): ?>
            <?php if($i == $datos['page']): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($datos['search']); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>