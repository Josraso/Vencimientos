<div id="servicios" class="tab-content">
    <div class="tab-pane">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>&#9881; Gestión de Servicios</h2>
            <button class="btn btn-primary" onclick="openModal('modalTipoServicio')">&#10133; Añadir Tipo de Servicio</button>
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
                    &#128176; €<?php echo number_format($servicio['ingresos_mensuales'], 2); ?>/mes
                </div>
                <div class="stat-detail">
                    <?php echo $servicio['total_contrataciones']; ?> contrataciones | 
                    <?php if ($servicio['proximos_vencer'] > 0): ?>
                        <span style="color: #ed8936;">&#9888; <?php echo $servicio['proximos_vencer']; ?> próximos</span>
                    <?php else: ?>
                        <span style="color: #48bb78;">&#9989; Al día</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <h3>&#128202; Resumen Detallado de Servicios</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>&#128196; Servicio</th>
                            <th>&#128176; Precio Base</th>
                            <th>&#128101; Clientes</th>
                            <th>&#9989; Activos</th>
                            <th>&#9888; Próximos a Vencer</th>
                            <th>&#128176; Ingresos/Mes</th>
                            <th>&#9881; Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($datos['estadisticas_servicios'] as $servicio): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($servicio['nombre']); ?></strong><br>
                                <small><?php echo htmlspecialchars($servicio['descripcion']); ?></small>
                            </td>
                            <td style="font-weight: bold;">€<?php echo number_format($servicio['precio'], 2); ?></td>
                            <td style="text-align: center;"><?php echo $servicio['clientes_distintos']; ?></td>
                            <td style="text-align: center;">
                                <span class="badge badge-success"><?php echo $servicio['activas']; ?></span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($servicio['proximos_vencer'] > 0): ?>
                                    <span class="badge badge-warning"><?php echo $servicio['proximos_vencer']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #48bb78; font-weight: bold;">€<?php echo number_format($servicio['ingresos_mensuales'], 2); ?></td>
                            <td>
                                <div class="actions-group">
                                    <button class="btn btn-small btn-primary" onclick="verClientesServicio(<?php echo $servicio['id']; ?>)" title="Ver clientes">&#128065;</button>
                                    <button class="btn btn-small btn-success" onclick="editarTipoServicio(<?php echo $servicio['id']; ?>)" title="Editar">&#9999;</button>
                                    <button class="btn btn-small btn-danger" onclick="eliminarTipoServicio(<?php echo $servicio['id']; ?>)" title="Eliminar">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>