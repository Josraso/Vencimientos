<!-- MODAL CLIENTE -->
<div id="modalCliente" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalClienteTitle">&#128101; Añadir Cliente</h3>
            <span class="close" onclick="closeModal('modalCliente')">&times;</span>
        </div>
        <form id="formCliente" method="POST">
            <input type="hidden" name="action" value="add_cliente">
            <input type="hidden" name="id" id="clienteId">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre *:</label>
                    <input type="text" name="nombre" id="clienteNombre" required>
                </div>
                <div class="form-group">
                    <label>Apellidos *:</label>
                    <input type="text" name="apellidos" id="clienteApellidos" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>DNI/NIF:</label>
                    <input type="text" name="dni" id="clienteDni" placeholder="12345678A">
                </div>
                <div class="form-group">
                    <label>Email *:</label>
                    <input type="email" name="email" id="clienteEmail" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono" id="clienteTelefono">
                </div>
                <div class="form-group">
                    <label>Empresa:</label>
                    <input type="text" name="empresa" id="clienteEmpresa">
                </div>
            </div>
            
            <div class="form-group">
                <label>Dirección:</label>
                <textarea name="direccion" id="clienteDireccion" rows="2"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Ciudad:</label>
                    <input type="text" name="ciudad" id="clienteCiudad">
                </div>
                <div class="form-group">
                    <label>Código Postal:</label>
                    <input type="text" name="codigo_postal" id="clienteCodigo">
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalCliente')">&#10060; Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitCliente">&#128190; Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TIPO SERVICIO -->
<div id="modalTipoServicio" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTipoServicioTitle">&#9881; Añadir Tipo de Servicio</h3>
            <span class="close" onclick="closeModal('modalTipoServicio')">&times;</span>
        </div>
        <form id="formTipoServicio">
            <input type="hidden" name="action" value="add_servicio_tipo">
            <input type="hidden" name="id" id="tipoServicioId">
            
            <div class="form-group">
                <label>Nombre del servicio *:</label>
                <input type="text" name="nombre" id="tipoServicioNombre" required>
            </div>
            
            <div class="form-group">
                <label>Descripción:</label>
                <textarea name="descripcion" id="tipoServicioDescripcion" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Precio base *:</label>
                <input type="number" step="0.01" name="precio" id="tipoServicioPrecio" required>
            </div>
            
            <div style="text-align: right; margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTipoServicio')">&#10060; Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitTipoServicio">&#128190; Guardar Servicio</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SERVICIOS CLIENTE -->
<div id="modalServicios" class="modal">
    <div class="modal-content" style="max-width: 1400px;">
        <div class="modal-header">
            <h3 id="modalServiciosTitle">&#128065; Detalles del Cliente</h3>
            <span class="close" onclick="closeModal('modalServicios')">&times;</span>
        </div>
        <div id="serviciosContent">
            <div style="text-align: center; color: #a0aec0; padding: 40px;">
                <div class="loading-spinner"></div>
                <div style="margin-top: 10px;">Cargando información...</div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ASIGNAR SERVICIO -->
<div id="modalAsignarServicio" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>&#10133; Asignar Servicio a Cliente</h3>
            <span class="close" onclick="closeModal('modalAsignarServicio')">&times;</span>
        </div>
        <form id="formAsignarServicio" method="POST">
            <input type="hidden" name="action" value="add_servicio_cliente">
            <input type="hidden" name="cliente_id" id="asignarClienteId">
            
            <div id="asignarClienteInfo" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <!-- Info del cliente -->
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Servicio *:</label>
                    <select name="tipo_servicio_id" required>
                        <option value="">Seleccionar servicio...</option>
                        <?php foreach($datos['tipos_servicios'] as $servicio): ?>
                        <option value="<?php echo $servicio['id']; ?>" data-precio="<?php echo $servicio['precio']; ?>">
                            <?php echo $servicio['nombre'] . ' - €' . $servicio['precio']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de Vencimiento *:</label>
                    <select name="tipo_vencimiento_id" required>
                        <option value="">Seleccionar vencimiento...</option>
                        <?php foreach($datos['tipos_vencimiento'] as $vencimiento): ?>
                        <option value="<?php echo $vencimiento['id']; ?>">
                            <?php echo $vencimiento['nombre']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de Inicio *:</label>
                    <input type="date" name="fecha_inicio" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Precio Personalizado:</label>
                    <input type="number" step="0.01" name="precio_personalizado" 
                           placeholder="Dejar vacío para usar precio base">
                </div>
            </div>
            
            <div class="form-group">
                <label>Observaciones:</label>
                <textarea name="observaciones" rows="3" placeholder="Observaciones sobre este servicio..."></textarea>
            </div>
            
            <div style="text-align: right; margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalAsignarServicio')">&#10060; Cancelar</button>
                <button type="submit" class="btn btn-primary">&#10133; Asignar Servicio</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR SERVICIO CLIENTE -->
<div id="modalEditarServicio" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>&#9999; Editar Servicio</h3>
            <span class="close" onclick="closeModal('modalEditarServicio')">&times;</span>
        </div>
        <form id="formEditarServicio">
            <input type="hidden" name="action" value="edit_servicio_cliente">
            <input type="hidden" name="servicio_id" id="editarServicioId">
            
            <div id="editarServicioInfo" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <!-- Info del servicio y cliente -->
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de Vencimiento *:</label>
                    <select name="tipo_vencimiento_id" id="editarTipoVencimiento" required>
                        <option value="">Seleccionar vencimiento...</option>
                        <?php foreach($datos['tipos_vencimiento'] as $vencimiento): ?>
                        <option value="<?php echo $vencimiento['id']; ?>">
                            <?php echo $vencimiento['nombre']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Precio Personalizado:</label>
                    <input type="number" step="0.01" name="precio_personalizado" id="editarPrecioPersonalizado"
                           placeholder="Dejar vacío para usar precio base">
                </div>
            </div>
            
            <div class="form-group">
                <label>Fecha de próximo vencimiento:</label>
                <input type="date" name="fecha_proximo_vencimiento" id="editarFechaVencimiento">
                <small style="color: #718096;">Dejar vacío para calcular automáticamente según el tipo de vencimiento</small>
            </div>
            
            <div class="form-group">
                <label>Observaciones:</label>
                <textarea name="observaciones" id="editarObservaciones" rows="3"></textarea>
            </div>
            
            <div style="text-align: right; margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarServicio')">&#10060; Cancelar</button>
                <button type="submit" class="btn btn-primary">&#128190; Actualizar Servicio</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CLIENTES DE SERVICIO -->
<div id="modalClientesServicio" class="modal">
    <div class="modal-content" style="max-width: 1400px;">
        <div class="modal-header">
            <h3 id="modalClientesServicioTitle">&#128101; Clientes del Servicio</h3>
            <span class="close" onclick="closeModal('modalClientesServicio')">&times;</span>
        </div>
        <div id="clientesServicioContent">
            <div style="text-align: center; color: #a0aec0; padding: 40px;">
                <div class="loading-spinner"></div>
                <div style="margin-top: 10px;">Cargando clientes...</div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONFIRMACIÓN ELIMINAR -->
<div id="modalConfirmarEliminar" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>&#9888; Confirmar Eliminación</h3>
            <span class="close" onclick="closeModal('modalConfirmarEliminar')">&times;</span>
        </div>
        <div id="confirmarEliminarContent">
            <!-- Se llenará dinámicamente -->
        </div>
        <div style="text-align: right; margin-top: 30px;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalConfirmarEliminar')">&#10060; Cancelar</button>
            <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">&#128465; Sí, Eliminar</button>
        </div>
    </div>
</div>