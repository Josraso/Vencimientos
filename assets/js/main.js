// assets/js/main.js - JavaScript principal

// Variables globales
let serviciosData = [];
let albaranesData = [];
let serviciosSeleccionados = [];
let albaranesSeleccionados = [];

// Gestión de pestañas
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
    
    // Cargar datos específicos de cada pestaña
    if (tabName === 'vencimientos') {
        cargarServicios();
    } else if (tabName === 'albaranes') {
        cargarAlbaranes();
    }
}

// Gestión de modales
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = 'auto';
    
    if (modalId === 'modalCliente') {
        resetFormCliente();
    } else if (modalId === 'modalTipoServicio') {
        resetFormTipoServicio();
    }
}

function resetFormCliente() {
    document.getElementById('formCliente').reset();
    document.querySelector('#formCliente input[name="action"]').value = 'add_cliente';
    document.getElementById('modalClienteTitle').innerHTML = '?? Añadir Cliente';
    document.getElementById('btnSubmitCliente').innerHTML = '?? Guardar Cliente';
    document.getElementById('clienteId').value = '';
}

function resetFormTipoServicio() {
    document.getElementById('formTipoServicio').reset();
    document.querySelector('#formTipoServicio input[name="action"]').value = 'add_servicio_tipo';
    document.getElementById('modalTipoServicioTitle').innerHTML = '?? Añadir Tipo de Servicio';
    document.getElementById('btnSubmitTipoServicio').innerHTML = '?? Guardar Servicio';
    document.getElementById('tipoServicioId').value = '';
}

// Búsqueda de clientes
function searchClientes() {
    const search = document.getElementById('searchInput').value;
    window.location.href = '?search=' + encodeURIComponent(search);
}

function clearSearch() {
    window.location.href = '?';
}
// Funciones AJAX
function ajaxRequest(action, data, callback, errorCallback = null) {
    const formData = new FormData();
    formData.append('action', action);
    
    for (const [key, value] of Object.entries(data)) {
        if (Array.isArray(value)) {
            value.forEach(item => formData.append(key + '[]', item));
        } else {
            formData.append(key, value);
        }
    }

    fetch('ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            callback(result);
        } else {
            const error = result.error || result.message || 'Error desconocido';
            if (errorCallback) {
                errorCallback(error);
            } else {
                mostrarError('Error: ' + error);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMsg = 'Error de conexión - Verifica que ajax.php existe y funciona correctamente';
        if (errorCallback) {
            errorCallback(errorMsg);
        } else {
            mostrarError(errorMsg);
        }
    });
}

// Funciones de utilidad
function mostrarError(mensaje) {
    alert('? ' + mensaje);
}

function mostrarExito(mensaje) {
    alert('? ' + mensaje);
}

function mostrarConfirmacion(mensaje, callback) {
    if (confirm('? ' + mensaje)) {
        callback();
    }
}
// Funciones de clientes
function verCliente(id) {
    openModal('modalServicios');
    document.getElementById('serviciosContent').innerHTML = `
        <div style="text-align: center; color: #a0aec0; padding: 40px;">
            <div class="loading-spinner"></div>
            <div style="margin-top: 10px;">Cargando información del cliente...</div>
        </div>
    `;
    
    ajaxRequest('get_cliente', {cliente_id: id}, function(result) {
        const cliente = result.data.cliente;
        const servicios = result.data.servicios;
        
        let html = `
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid #667eea;">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <h3 style="color: #667eea; margin-bottom: 10px;">?? ${cliente.nombre} ${cliente.apellidos}</h3>
                        ${cliente.empresa ? `<p style="margin: 5px 0;"><strong>?? Empresa:</strong> ${cliente.empresa}</p>` : ''}
                        <p style="margin: 5px 0;"><strong>?? Email:</strong> ${cliente.email}</p>
                        <p style="margin: 5px 0;"><strong>?? Teléfono:</strong> ${cliente.telefono || 'No especificado'}</p>
                        ${cliente.direccion ? `<p style="margin: 5px 0;"><strong>?? Dirección:</strong> ${cliente.direccion}, ${cliente.ciudad || ''} ${cliente.codigo_postal || ''}</p>` : ''}
                    </div>
                    <div class="actions-group">
                        <button class="btn btn-success" onclick="asignarServicio(${cliente.id})">? Asignar Servicio</button>
                        <button class="btn btn-primary" onclick="editarCliente(${cliente.id}); closeModal('modalServicios');">?? Editar Cliente</button>
                    </div>
                </div>
            </div>
            
            <h4 style="margin-bottom: 20px;">?? Servicios Contratados (${servicios.length})</h4>
        `;
        
        if (servicios.length === 0) {
            html += `
                <div style="text-align: center; color: #a0aec0; padding: 40px; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 3em;">??</div>
                    <div style="margin-top: 10px;">Este cliente no tiene servicios asignados</div>
                    <button class="btn btn-primary" onclick="asignarServicio(${cliente.id})" style="margin-top: 15px;">? Asignar Primer Servicio</button>
                </div>
            `;
        } else {
            html += '<div class="table-container"><table><thead><tr><th>Servicio</th><th>Precio</th><th>Vencimiento</th><th>Días Rest.</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
            
            servicios.forEach(servicio => {
                const clase = servicio.dias_restantes <= 2 ? 'vencimiento-urgente' : 
                             servicio.dias_restantes <= 7 ? 'vencimiento-proximo' : 
                             servicio.dias_restantes <= 30 ? 'vencimiento-ok' : 'vencimiento-lejano';
                
                const icono = servicio.dias_restantes <= 2 ? '??' :
                             servicio.dias_restantes <= 7 ? '??' :
                             servicio.dias_restantes <= 30 ? '?' : '?';
                
                html += `
                    <tr class="${clase}">
                        <td>
                            <strong>${servicio.servicio_nombre}</strong><br>
                            <small>${servicio.descripcion}</small>
                        </td>
                        <td style="font-weight: bold;">€${parseFloat(servicio.precio_final).toFixed(2)}</td>
                        <td>${new Date(servicio.fecha_proximo_vencimiento).toLocaleDateString('es-ES')}</td>
                        <td style="text-align: center;">
                            <span class="badge ${servicio.dias_restantes <= 2 ? 'badge-danger' : servicio.dias_restantes <= 7 ? 'badge-warning' : 'badge-success'}">
                                ${icono} ${servicio.dias_restantes}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            ${servicio.activo == 1 ? '<span class="badge badge-success">? Activo</span>' : '<span class="badge badge-danger">? Inactivo</span>'}
                        </td>
                        <td>
                            <div class="actions-group">
                                <button class="btn btn-xs btn-warning" onclick="editarServicioCliente(${servicio.id})" title="Editar">??</button>
                                <button class="btn btn-xs btn-success" onclick="renovarServicio(${servicio.id})" title="Renovar">??</button>
                                ${servicio.tiene_albaran == 0 ? 
                                  `<button class="btn btn-xs btn-info" onclick="generarAlbaran([${servicio.id}])" title="Generar albarán">??</button>` : ''}
                                <button class="btn btn-xs ${servicio.activo == 1 ? 'btn-danger' : 'btn-success'}" 
                                        onclick="toggleServicioCliente(${servicio.id}, ${servicio.activo == 1 ? 0 : 1})" 
                                        title="${servicio.activo == 1 ? 'Desactivar' : 'Activar'}">
                                    ${servicio.activo == 1 ? '?' : '?'}
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            
            const totalActivos = servicios.filter(s => s.activo == 1).reduce((sum, s) => sum + parseFloat(s.precio_final), 0);
            const proximosVencer = servicios.filter(s => s.activo == 1 && s.dias_restantes <= 7).length;
            
            if (totalActivos > 0) {
                html += `
                    <div style="background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%); padding: 20px; border-radius: 10px; margin-top: 25px; text-align: center;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: #48bb78;">€${totalActivos.toFixed(2)}</div>
                                <div>?? Total mensual activo</div>
                            </div>
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: ${proximosVencer > 0 ? '#ed8936' : '#48bb78'};">
                                    ${proximosVencer}
                                </div>
                                <div>${proximosVencer > 0 ? '??' : '?'} Próximos a vencer</div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
        
        document.getElementById('serviciosContent').innerHTML = html;
    });
}

function editarCliente(id) {
    ajaxRequest('get_cliente', {cliente_id: id}, function(result) {
        const cliente = result.data.cliente;
        
        document.querySelector('#formCliente input[name="action"]').value = 'edit_cliente';
        document.getElementById('clienteId').value = cliente.id;
        document.getElementById('modalClienteTitle').innerHTML = '?? Editar Cliente';
        document.getElementById('btnSubmitCliente').innerHTML = '?? Actualizar Cliente';
        
        document.getElementById('clienteNombre').value = cliente.nombre;
        document.getElementById('clienteApellidos').value = cliente.apellidos;
        document.getElementById('clienteEmail').value = cliente.email;
        document.getElementById('clienteTelefono').value = cliente.telefono || '';
        document.getElementById('clienteEmpresa').value = cliente.empresa || '';
        document.getElementById('clienteDireccion').value = cliente.direccion || '';
        document.getElementById('clienteCiudad').value = cliente.ciudad || '';
        document.getElementById('clienteCodigo').value = cliente.codigo_postal || '';
        
        openModal('modalCliente');
    });
}

function asignarServicio(clienteId) {
    ajaxRequest('get_cliente', {cliente_id: clienteId}, function(result) {
        const cliente = result.data.cliente;
        
        document.getElementById('asignarClienteId').value = clienteId;
        document.getElementById('asignarClienteInfo').innerHTML = `
            <h4 style="color: #667eea;">?? ${cliente.nombre} ${cliente.apellidos}</h4>
            <p><strong>??</strong> ${cliente.email} ${cliente.empresa ? ` | <strong>??</strong> ${cliente.empresa}` : ''}</p>
        `;
        
        closeModal('modalServicios');
        openModal('modalAsignarServicio');
    });
}

function eliminarClienteSeguro(clienteId) {
    ajaxRequest('validar_borrado_cliente', {cliente_id: clienteId}, function(result) {
        const data = result.data;
        let contenido = '';
        
        if (data.puede_borrar) {
            contenido = `
                <div class="alert success">
                    <h4>? Cliente sin servicios activos</h4>
                    <p>Este cliente no tiene servicios activos y puede ser eliminado de forma segura.</p>
                </div>
            `;
        } else {
            contenido = `
                <div class="alert error">
                    <h4>? Cliente con servicios activos</h4>
                    <p>Este cliente tiene <strong>${data.servicios_activos} servicios activos</strong> que deben ser gestionados primero:</p>
                    <ul style="margin: 15px 0;">
            `;
            
            data.servicios_detalle.forEach(servicio => {
                const fechaVenc = new Date(servicio.fecha_proximo_vencimiento).toLocaleDateString('es-ES');
                contenido += `<li><strong>${servicio.nombre}</strong> - €${parseFloat(servicio.precio_final).toFixed(2)} - Vence: ${fechaVenc}</li>`;
            });
            
            contenido += `
                    </ul>
                    <p><strong>Opciones:</strong></p>
                    <ul>
                        <li>Desactiva o elimina los servicios primero</li>
                        <li>Transfiere los servicios a otro cliente</li>
                    </ul>
                </div>
            `;
        }
        
        document.getElementById('confirmarEliminarContent').innerHTML = contenido;
        
        const btnConfirmar = document.getElementById('btnConfirmarEliminar');
        if (data.puede_borrar) {
            btnConfirmar.style.display = 'inline-block';
            btnConfirmar.onclick = function() {
                eliminarClienteConfirmado(clienteId);
            };
        } else {
            btnConfirmar.style.display = 'none';
        }
        
        openModal('modalConfirmarEliminar');
    });
}

function eliminarClienteConfirmado(clienteId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete_cliente">
        <input type="hidden" name="cliente_id" value="${clienteId}">
    `;
    document.body.appendChild(form);
    form.submit();
}
// Funciones de servicios
function cargarServicios() {
    const filtros = obtenerFiltrosServicios();
    
    ajaxRequest('get_todos_servicios', filtros, function(result) {
        serviciosData = result.data;
        mostrarServicios(serviciosData);
        actualizarResumenEconomico(serviciosData);
        actualizarContadorSeleccionados();
    });
}

function obtenerFiltrosServicios() {
    return {
        filtro_estado: document.getElementById('filtroEstado')?.value || '',
        filtro_servicio: document.getElementById('filtroServicio')?.value || '',
        filtro_cliente: document.getElementById('filtroCliente')?.value || '',
        filtro_fecha_desde: document.getElementById('filtroFechaDesde')?.value || '',
        filtro_fecha_hasta: document.getElementById('filtroFechaHasta')?.value || '',
        filtro_facturado: document.getElementById('filtroFacturado')?.value || ''
    };
}

function mostrarServicios(servicios) {
    const tbody = document.getElementById('serviciosTableBody');
    if (!tbody) return;
    
    if (servicios.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 40px; color: #a0aec0;">
                    <div style="font-size: 3em;">??</div>
                    <div style="margin-top: 10px;">No hay servicios que mostrar con los filtros aplicados</div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = servicios.map(servicio => {
        const clase = servicio.dias_restantes <= 2 ? 'vencimiento-urgente' : 
                     servicio.dias_restantes <= 7 ? 'vencimiento-proximo' : 
                     servicio.dias_restantes <= 30 ? 'vencimiento-ok' : 'vencimiento-lejano';
        
        const badgeClase = servicio.dias_restantes <= 2 ? 'badge-danger' : 
                          servicio.dias_restantes <= 7 ? 'badge-warning' : 
                          servicio.dias_restantes <= 30 ? 'badge-success' : 'badge-secondary';
        
        const iconoEstado = servicio.dias_restantes <= 2 ? '??' :
                           servicio.dias_restantes <= 7 ? '??' :
                           servicio.dias_restantes <= 30 ? '?' : '?';
        
        return `
            <tr class="${clase}">
                <td><input type="checkbox" class="servicio-checkbox" value="${servicio.id}" onchange="toggleServicioSeleccion(${servicio.id})"></td>
                <td>
                    <strong>${servicio.nombre} ${servicio.apellidos}</strong><br>
                    <small>${servicio.empresa || 'Sin empresa'}</small>
                </td>
                <td>
                    ${servicio.servicio_nombre}<br>
                    <small>${servicio.vencimiento_tipo}</small>
                </td>
                <td style="font-weight: bold; color: #48bb78;">€${parseFloat(servicio.precio_final).toFixed(2)}</td>
                <td>${new Date(servicio.fecha_proximo_vencimiento).toLocaleDateString('es-ES')}</td>
                <td style="text-align: center;">
                    <span class="badge ${badgeClase}">${iconoEstado} ${servicio.dias_restantes} días</span>
                </td>
                <td style="text-align: center;">
                    ${servicio.tiene_albaran > 0 ? 
                      '<span class="badge badge-info">? Facturado</span>' : 
                      '<span class="badge badge-secondary">? Sin facturar</span>'}
                </td>
                <td>
                    <div>?? ${servicio.email}</div>
                    <div>?? ${servicio.telefono || 'Sin teléfono'}</div>
                </td>
                <td>
                    <div class="actions-group">
                        <button class="btn btn-xs btn-primary" onclick="verCliente(${servicio.cliente_id})" title="Ver cliente">???</button>
                        <button class="btn btn-xs btn-warning" onclick="editarServicioCliente(${servicio.id})" title="Editar">??</button>
                        <button class="btn btn-xs btn-success" onclick="renovarServicio(${servicio.id})" title="Renovar">??</button>
                        ${servicio.tiene_albaran == 0 ? 
                          `<button class="btn btn-xs btn-info" onclick="generarAlbaran([${servicio.id}])" title="Generar albarán">??</button>` : ''}
                        <button class="btn btn-xs btn-secondary" onclick="enviarNotificacionIndividual(${servicio.id})" title="Enviar aviso">??</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function actualizarResumenEconomico(servicios) {
    const urgentes = servicios.filter(s => s.dias_restantes <= 2);
    const proximos = servicios.filter(s => s.dias_restantes > 2 && s.dias_restantes <= 7);
    const esteMes = servicios.filter(s => s.dias_restantes > 7 && s.dias_restantes <= 30);
    const sinFacturar = servicios.filter(s => s.tiene_albaran == 0);
    
    const resumen = document.getElementById('resumenEconomico');
    if (resumen) {
        resumen.innerHTML = `
            <div class="stat-card" style="border-left: 4px solid #f56565;">
                <div class="stat-number">€${urgentes.reduce((sum, s) => sum + parseFloat(s.precio_final), 0).toFixed(2)}</div>
                <div class="stat-label">?? Urgentes</div>
                <div class="stat-detail">${urgentes.length} servicios</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ed8936;">
                <div class="stat-number">€${proximos.reduce((sum, s) => sum + parseFloat(s.precio_final), 0).toFixed(2)}</div>
                <div class="stat-label">?? Esta semana</div>
                <div class="stat-detail">${proximos.length} servicios</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #48bb78;">
                <div class="stat-number">€${esteMes.reduce((sum, s) => sum + parseFloat(s.precio_final), 0).toFixed(2)}</div>
                <div class="stat-label">? Este mes</div>
                <div class="stat-detail">${esteMes.length} servicios</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #4299e1;">
                <div class="stat-number">€${sinFacturar.reduce((sum, s) => sum + parseFloat(s.precio_final), 0).toFixed(2)}</div>
                <div class="stat-label">?? Sin facturar</div>
                <div class="stat-detail">${sinFacturar.length} servicios</div>
            </div>
        `;
    }
}

function aplicarFiltros() {
    cargarServicios();
}

function limpiarFiltros() {
    document.getElementById('filtroEstado').value = '';
    document.getElementById('filtroServicio').value = '';
    document.getElementById('filtroCliente').value = '';
    document.getElementById('filtroFechaDesde').value = '';
    document.getElementById('filtroFechaHasta').value = '';
    document.getElementById('filtroFacturado').value = '';
    serviciosSeleccionados = [];
    cargarServicios();
}

// Funciones de selección múltiple para servicios
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.servicio-checkbox');
    
    serviciosSeleccionados = [];
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if (selectAll.checked) {
            serviciosSeleccionados.push(parseInt(checkbox.value));
        }
    });
    
    actualizarContadorSeleccionados();
}

function toggleServicioSeleccion(servicioId) {
    const checkbox = document.querySelector(`.servicio-checkbox[value="${servicioId}"]`);
    
    if (checkbox.checked) {
        if (!serviciosSeleccionados.includes(servicioId)) {
            serviciosSeleccionados.push(servicioId);
        }
    } else {
        serviciosSeleccionados = serviciosSeleccionados.filter(id => id !== servicioId);
    }
    
    actualizarContadorSeleccionados();
}

function actualizarContadorSeleccionados() {
    const contador = document.getElementById('selectedCount');
    const bulkActions = document.getElementById('bulkActions');
    
    if (contador) {
        contador.textContent = `${serviciosSeleccionados.length} servicios seleccionados`;
    }
    
    if (bulkActions) {
        if (serviciosSeleccionados.length > 0) {
            bulkActions.classList.add('show');
        } else {
            bulkActions.classList.remove('show');
        }
    }
}

// Funciones específicas de servicios
function renovarServicio(servicioId) {
    mostrarConfirmacion('¿Renovar este servicio por el mismo período?', function() {
        ajaxRequest('renovar_servicio', {servicio_id: servicioId}, function(result) {
            mostrarExito(result.message);
            cargarServicios();
        });
    });
}

function renovarMasivo() {
    if (serviciosSeleccionados.length === 0) {
        mostrarError('Selecciona al menos un servicio para renovar');
        return;
    }
    
    mostrarConfirmacion(`¿Renovar ${serviciosSeleccionados.length} servicios seleccionados?`, function() {
        ajaxRequest('renovar_masivo', {servicios_ids: serviciosSeleccionados}, function(result) {
            mostrarExito(result.message);
            serviciosSeleccionados = [];
            cargarServicios();
        });
    });
}

function renovarSeleccionados() {
    renovarMasivo();
}

function generarAlbaran(serviciosIds) {
    if (!Array.isArray(serviciosIds) || serviciosIds.length === 0) {
        mostrarError('No se han seleccionado servicios');
        return;
    }
    
    const btn = event?.target;
    const originalText = btn?.innerHTML;
    if (btn) {
        btn.innerHTML = '<span class="loading-spinner"></span> Generando...';
        btn.disabled = true;
    }
    
    ajaxRequest('generar_albaran', {servicios_ids: serviciosIds}, function(result) {
        const mensaje = `Albarán generado correctamente: ${result.data.numero}\nTotal: €${parseFloat(result.data.total).toFixed(2)}\n\n¿Deseas ver el PDF?`;
        
        if (confirm('? ' + mensaje)) {
            window.open('generar_pdf.php?albaran_id=' + result.data.albaran_id, '_blank');
        }
        
        serviciosSeleccionados = [];
        if (document.querySelector('.tab.active')?.textContent.includes('Todos los Servicios')) {
            cargarServicios();
        } else {
            location.reload();
        }
    }, function(error) {
        mostrarError(error);
    }).finally(() => {
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

function generarAlbaranSeleccionados() {
    if (serviciosSeleccionados.length === 0) {
        mostrarError('Selecciona al menos un servicio para generar albarán');
        return;
    }
    
    generarAlbaran(serviciosSeleccionados);
}

function editarServicioCliente(servicioId) {
    ajaxRequest('get_servicio_cliente', {servicio_id: servicioId}, function(result) {
        const servicio = result.data;
        
        document.getElementById('editarServicioId').value = servicioId;
        document.getElementById('editarServicioInfo').innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h4 style="color: #667eea;">?? ${servicio.cliente_nombre} ${servicio.cliente_apellidos}</h4>
                    <p><strong>?? Servicio:</strong> ${servicio.servicio_nombre}</p>
                    <p><strong>?? Precio base:</strong> €${parseFloat(servicio.precio_base).toFixed(2)}</p>
                </div>
                <div style="text-align: right;">
                    <p><strong>?? Próximo vencimiento:</strong></p>
                    <p style="font-size: 1.1em; color: #ed8936;">${new Date(servicio.fecha_proximo_vencimiento).toLocaleDateString('es-ES')}</p>
                </div>
            </div>
        `;
        
        document.getElementById('editarTipoVencimiento').value = servicio.tipo_vencimiento_id;
        document.getElementById('editarPrecioPersonalizado').value = servicio.precio_personalizado || '';
        document.getElementById('editarObservaciones').value = servicio.observaciones || '';
        
        closeModal('modalServicios');
        openModal('modalEditarServicio');
    });
}

function toggleServicioCliente(servicioClienteId, nuevoEstado) {
    const accion = nuevoEstado ? 'activar' : 'desactivar';
    
    mostrarConfirmacion(`¿Estás seguro de que quieres ${accion} este servicio?`, function() {
        ajaxRequest('toggle_servicio_cliente', {
            servicio_cliente_id: servicioClienteId,
            activo: nuevoEstado
        }, function(result) {
            mostrarExito('Servicio ' + (nuevoEstado ? 'activado' : 'desactivado') + ' correctamente');
            const tabActiva = document.querySelector('.tab.active');
            if (tabActiva) {
                const tabId = tabActiva.textContent.includes('Todos los Servicios') ? 'vencimientos' : null;
                if (tabId === 'vencimientos') {
                    cargarServicios();
                } else {
                    location.reload();
                }
            }
        });
    });
}
// Funciones de tipos de servicios
function verClientesServicio(servicioId) {
    openModal('modalClientesServicio');
    document.getElementById('clientesServicioContent').innerHTML = `
        <div style="text-align: center; color: #a0aec0; padding: 40px;">
            <div class="loading-spinner"></div>
            <div style="margin-top: 10px;">Cargando clientes del servicio...</div>
        </div>
    `;
    
    ajaxRequest('get_clientes_servicio', {servicio_id: servicioId}, function(result) {
        const servicio = result.data.servicio;
        const clientes = result.data.clientes;
        
        document.getElementById('modalClientesServicioTitle').innerHTML = `?? Clientes de: ${servicio.nombre}`;
        
        let html = `
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid #48bb78;">
                <h4 style="color: #48bb78; margin-bottom: 10px;">?? ${servicio.nombre}</h4>
                <p style="margin: 5px 0;">${servicio.descripcion}</p>
                <p style="margin: 10px 0 0 0;"><strong>?? Precio base:</strong> €${parseFloat(servicio.precio).toFixed(2)}</p>
            </div>
            
            <h4 style="margin-bottom: 20px;">?? Clientes con este servicio (${clientes.length})</h4>
        `;
        
        if (clientes.length === 0) {
            html += `
                <div style="text-align: center; color: #a0aec0; padding: 40px; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 3em;">??</div>
                    <div style="margin-top: 10px;">No hay clientes con este servicio asignado</div>
                </div>
            `;
        } else {
            html += '<div class="table-container"><table><thead><tr><th>Cliente</th><th>Empresa</th><th>Precio</th><th>Próximo Venc.</th><th>Días Rest.</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
            
            clientes.forEach(cliente => {
                const clase = cliente.dias_restantes <= 2 ? 'vencimiento-urgente' : 
                             cliente.dias_restantes <= 7 ? 'vencimiento-proximo' : 
                             cliente.dias_restantes <= 30 ? 'vencimiento-ok' : 'vencimiento-lejano';
                
                const icono = cliente.dias_restantes <= 2 ? '??' :
                             cliente.dias_restantes <= 7 ? '??' :
                             cliente.dias_restantes <= 30 ? '?' : '?';
                
                html += `
                    <tr class="${clase}">
                        <td>
                            <strong>${cliente.nombre} ${cliente.apellidos}</strong><br>
                            <small>?? ${cliente.email} | ?? ${cliente.telefono || 'Sin teléfono'}</small>
                        </td>
                        <td>${cliente.empresa || '-'}</td>
                        <td style="font-weight: bold;">€${parseFloat(cliente.precio_final).toFixed(2)}</td>
                        <td>${new Date(cliente.fecha_proximo_vencimiento).toLocaleDateString('es-ES')}</td>
                        <td style="text-align: center;">
                            <span class="badge ${cliente.dias_restantes <= 2 ? 'badge-danger' : cliente.dias_restantes <= 7 ? 'badge-warning' : 'badge-success'}">
                                ${icono} ${cliente.dias_restantes} días
                            </span>
                        </td>
                        <td style="text-align: center;">
                            ${cliente.tiene_albaran > 0 ? '<span class="badge badge-info">? Facturado</span>' : '<span class="badge badge-secondary">? Sin facturar</span>'}
                        </td>
                        <td>
                            <div class="actions-group">
                                <button class="btn btn-xs btn-primary" onclick="verCliente(${cliente.id}); closeModal('modalClientesServicio');" title="Ver cliente">???</button>
                                <button class="btn btn-xs btn-warning" onclick="editarServicioCliente(${cliente.servicio_cliente_id}); closeModal('modalClientesServicio');" title="Editar servicio">??</button>
                                ${cliente.tiene_albaran == 0 ? `<button class="btn btn-xs btn-success" onclick="generarAlbaran([${cliente.servicio_cliente_id}])" title="Generar albarán">??</button>` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            
            const totalIngresos = clientes.reduce((sum, c) => sum + parseFloat(c.precio_final), 0);
            const sinFacturar = clientes.filter(c => c.tiene_albaran == 0).length;
            const proximosVencer = clientes.filter(c => c.dias_restantes <= 7).length;
            
            html += `<div style="margin-top: 25px;">
                    <h4 style="margin-bottom: 15px;">?? Resumen del Servicio</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="background: #e6fffa; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #48bb78;">
                            <div style="font-size: 1.5em; font-weight: bold; color: #48bb78;">€${totalIngresos.toFixed(2)}</div>
                            <div>?? Ingresos Totales</div>
                        </div>
                        <div style="background: #fff5f5; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid ${sinFacturar > 0 ? '#f56565' : '#48bb78'};">
                            <div style="font-size: 1.5em; font-weight: bold; color: ${sinFacturar > 0 ? '#f56565' : '#48bb78'};">${sinFacturar}</div>
                            <div>${sinFacturar > 0 ? '?' : '?'} Pendientes de Facturar</div>
                        </div>
                        <div style="background: #fffbf0; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid ${proximosVencer > 0 ? '#ed8936' : '#48bb78'};">
                            <div style="font-size: 1.5em; font-weight: bold; color: ${proximosVencer > 0 ? '#ed8936' : '#48bb78'};">${proximosVencer}</div>
                            <div>${proximosVencer > 0 ? '??' : '?'} Próximos a Vencer</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        document.getElementById('clientesServicioContent').innerHTML = html;
    });
}

function editarTipoServicio(id) {
    ajaxRequest('get_tipo_servicio', {servicio_id: id}, function(result) {
        const servicio = result.data;
        
        document.querySelector('#formTipoServicio input[name="action"]').value = 'edit_servicio_tipo';
        document.getElementById('tipoServicioId').value = servicio.id;
        document.getElementById('modalTipoServicioTitle').innerHTML = '?? Editar Tipo de Servicio';
        document.getElementById('btnSubmitTipoServicio').innerHTML = '?? Actualizar Servicio';
        
        document.getElementById('tipoServicioNombre').value = servicio.nombre;
        document.getElementById('tipoServicioDescripcion').value = servicio.descripcion;
        document.getElementById('tipoServicioPrecio').value = servicio.precio;
        
        openModal('modalTipoServicio');
    });
}

// Funciones de albaranes
function cargarAlbaranes() {
    const filtros = obtenerFiltrosAlbaranes();
    
    ajaxRequest('get_albaranes', filtros, function(result) {
        albaranesData = result.data;
        mostrarAlbaranes(albaranesData);
        actualizarEstadisticasAlbaranes(albaranesData);
        actualizarContadorAlbaranesSeleccionados();
    });
}

function obtenerFiltrosAlbaranes() {
    return {
        filtro_cliente: document.getElementById('filtroAlbaranCliente')?.value || '',
        filtro_estado: document.getElementById('filtroAlbaranEstado')?.value || '',
        filtro_fecha_desde: document.getElementById('filtroAlbaranFechaDesde')?.value || '',
        filtro_fecha_hasta: document.getElementById('filtroAlbaranFechaHasta')?.value || '',
        filtro_monto_min: document.getElementById('filtroAlbaranMontoMin')?.value || '',
        filtro_monto_max: document.getElementById('filtroAlbaranMontoMax')?.value || ''
    };
}

function mostrarAlbaranes(albaranes) {
    const tbody = document.getElementById('albaranesTableBody');
    if (!tbody) return;
    
    if (albaranes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #a0aec0;">
                    <div style="font-size: 3em;">??</div>
                    <div style="margin-top: 10px;">No hay albaranes que mostrar</div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = albaranes.map(albaran => {
        const estados = {
            'borrador': '<span class="badge badge-secondary">?? Borrador</span>',
            'generado': '<span class="badge badge-warning">? Generado</span>',
            'enviado': '<span class="badge badge-info">?? Enviado</span>',
            'pagado': '<span class="badge badge-success">?? Pagado</span>'
        };
        
        return `
            <tr>
                <td><input type="checkbox" class="albaran-checkbox" value="${albaran.id}" onchange="toggleAlbaranSeleccion(${albaran.id})"></td>
                <td><strong>${albaran.numero_albaran}</strong></td>
                <td>
                    <strong>${albaran.nombre} ${albaran.apellidos}</strong><br>
                    <small>${albaran.empresa || 'Sin empresa'}</small>
                </td>
                <td>
                    <span class="badge badge-info">${albaran.total_lineas} líneas</span><br>
                    <small title="${albaran.servicios_nombres}">${(albaran.servicios_nombres || 'Sin servicios').substring(0, 30)}...</small>
                </td>
                <td>${new Date(albaran.fecha_albaran).toLocaleDateString('es-ES')}</td>
                <td style="font-weight: bold; color: #48bb78;">€${parseFloat(albaran.total).toFixed(2)}</td>
                <td>${estados[albaran.estado] || `<span class="badge badge-secondary">${albaran.estado}</span>`}</td>
                <td>
                    <div class="actions-group">
                        <button class="btn btn-xs btn-primary" onclick="verAlbaranPDF(${albaran.id})" title="Ver PDF">???</button>
                        <button class="btn btn-xs btn-success" onclick="descargarAlbaranPDF(${albaran.id})" title="Descargar">??</button>
                        <button class="btn btn-xs btn-info" onclick="cambiarEstadoAlbaran(${albaran.id})" title="Cambiar estado">??</button>
                        <button class="btn btn-xs btn-danger" onclick="eliminarAlbaran(${albaran.id})" title="Eliminar">???</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function actualizarEstadisticasAlbaranes(albaranes) {
    const stats = document.getElementById('statsAlbaranes');
    if (!stats) return;
    
    const total = albaranes.length;
    const totalFacturado = albaranes.reduce((sum, a) => sum + parseFloat(a.total), 0);
    const porEstado = {
        borrador: albaranes.filter(a => a.estado === 'borrador').length,
        generado: albaranes.filter(a => a.estado === 'generado').length,
        enviado: albaranes.filter(a => a.estado === 'enviado').length,
        pagado: albaranes.filter(a => a.estado === 'pagado').length
    };
    
    stats.innerHTML = `
        <div class="stat-card">
            <div class="stat-number">${total}</div>
            <div class="stat-label">?? Total Albaranes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">€${totalFacturado.toFixed(2)}</div>
            <div class="stat-label">?? Facturado Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">${porEstado.pagado}</div>
            <div class="stat-label">? Pagados</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">${porEstado.generado + porEstado.enviado}</div>
            <div class="stat-label">? Pendientes</div>
        </div>
    `;
}

function aplicarFiltrosAlbaranes() {
    cargarAlbaranes();
}

function limpiarFiltrosAlbaranes() {
    document.getElementById('filtroAlbaranCliente').value = '';
    document.getElementById('filtroAlbaranEstado').value = '';
    document.getElementById('filtroAlbaranFechaDesde').value = '';
    document.getElementById('filtroAlbaranFechaHasta').value = '';
    document.getElementById('filtroAlbaranMontoMin').value = '';
    document.getElementById('filtroAlbaranMontoMax').value = '';
    albaranesSeleccionados = [];
    cargarAlbaranes();
}

function toggleSelectAllAlbaranes() {
    const selectAll = document.getElementById('selectAllAlbaranes') || document.getElementById('selectAllAlbaranesHeader');
    const checkboxes = document.querySelectorAll('.albaran-checkbox');
    
    albaranesSeleccionados = [];
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if (selectAll.checked) {
            albaranesSeleccionados.push(parseInt(checkbox.value));
        }
    });
    
    actualizarContadorAlbaranesSeleccionados();
}

function toggleAlbaranSeleccion(albaranId) {
    const checkbox = document.querySelector(`.albaran-checkbox[value="${albaranId}"]`);
    
    if (checkbox.checked) {
        if (!albaranesSeleccionados.includes(albaranId)) {
            albaranesSeleccionados.push(albaranId);
        }
    } else {
        albaranesSeleccionados = albaranesSeleccionados.filter(id => id !== albaranId);
    }
    
    actualizarContadorAlbaranesSeleccionados();
}

function actualizarContadorAlbaranesSeleccionados() {
    const contador = document.getElementById('selectedAlbaranesCount');
    const btnEliminar = document.getElementById('btnEliminarMasivo');
    
    if (contador) {
        contador.textContent = `${albaranesSeleccionados.length} albaranes seleccionados`;
    }
    
    if (btnEliminar) {
        if (albaranesSeleccionados.length > 0) {
            btnEliminar.style.display = 'inline-block';
        } else {
            btnEliminar.style.display = 'none';
        }
    }
}

function eliminarAlbaran(albaranId) {
    mostrarConfirmacion('¿Estás seguro de eliminar este albarán? Esta acción no se puede deshacer.', function() {
        ajaxRequest('eliminar_albaran', {albaran_id: albaranId}, function(result) {
            mostrarExito(result.message);
            cargarAlbaranes();
        });
    });
}

function eliminarAlbaranesMasivo() {
    if (albaranesSeleccionados.length === 0) {
        mostrarError('Selecciona al menos un albarán para eliminar');
        return;
    }
    
    mostrarConfirmacion(`¿Eliminar ${albaranesSeleccionados.length} albaranes seleccionados? Esta acción no se puede deshacer.`, function() {
        ajaxRequest('eliminar_albaranes_masivo', {albaranes_ids: albaranesSeleccionados}, function(result) {
            mostrarExito(result.message);
            albaranesSeleccionados = [];
            cargarAlbaranes();
        });
    });
}

function cambiarEstadoAlbaran(albaranId) {
    const nuevoEstado = prompt('Nuevo estado (borrador/generado/enviado/pagado):');
    const estadosValidos = ['borrador', 'generado', 'enviado', 'pagado'];
    
    if (nuevoEstado && estadosValidos.includes(nuevoEstado.toLowerCase())) {
        ajaxRequest('cambiar_estado_albaran', {
            albaran_id: albaranId,
            nuevo_estado: nuevoEstado.toLowerCase()
        }, function(result) {
            mostrarExito(result.message);
            cargarAlbaranes();
        });
    } else if (nuevoEstado) {
        mostrarError('Estado no válido. Usa: borrador, generado, enviado o pagado');
    }
}

function verAlbaranPDF(albaranId) {
    window.open('generar_pdf.php?albaran_id=' + albaranId, '_blank');
}

function descargarAlbaranPDF(albaranId) {
    window.open('generar_pdf.php?albaran_id=' + albaranId + '&download=1', '_blank');
}
// Funciones de notificaciones
function enviarNotificacionesMasivas() {
    mostrarConfirmacion('¿Enviar notificaciones por email a todos los clientes con vencimientos próximos?', function() {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="loading-spinner"></span> Enviando...';
        btn.disabled = true;
        
        fetch('cron_notifier.php')
            .then(response => response.text())
            .then(result => {
                mostrarExito('Notificaciones enviadas. Revisa los logs para más detalles.');
            })
            .catch(error => {
                mostrarError('Error ejecutando notificaciones: ' + error);
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    });
}

function enviarNotificacionesSeleccionadas() {
    if (serviciosSeleccionados.length === 0) {
        mostrarError('Selecciona al menos un servicio para enviar notificaciones');
        return;
    }
    
    mostrarConfirmacion(`¿Enviar notificaciones a ${serviciosSeleccionados.length} clientes?`, function() {
        let enviados = 0;
        let errores = 0;
        
        serviciosSeleccionados.forEach((servicioId, index) => {
            ajaxRequest('enviar_notificacion_individual', {servicio_id: servicioId}, function(result) {
                enviados++;
                if (enviados + errores === serviciosSeleccionados.length) {
                    mostrarExito(`Proceso completado: ${enviados} enviados, ${errores} errores`);
                    serviciosSeleccionados = [];
                    cargarServicios();
                }
            }, function(error) {
                errores++;
                if (enviados + errores === serviciosSeleccionados.length) {
                    mostrarExito(`Proceso completado: ${enviados} enviados, ${errores} errores`);
                    serviciosSeleccionados = [];
                    cargarServicios();
                }
            });
        });
    });
}

function enviarNotificacionIndividual(servicioId) {
    mostrarConfirmacion('¿Enviar notificación por email para este servicio específico?\n\nSe enviará al cliente y al administrador.', function() {
        const btn = event?.target;
        const originalText = btn?.innerHTML;
        if (btn) {
            btn.innerHTML = '<span class="loading-spinner"></span>';
            btn.disabled = true;
        }
        
        ajaxRequest('enviar_notificacion_individual', {servicio_id: servicioId}, function(result) {
            mostrarExito(result.message);
        }, function(error) {
            mostrarError(error);
        }).finally(() => {
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    });
}

// Test SMTP
function testSMTP() {
    const testEmail = document.getElementById('testEmail').value;
    if (!testEmail) {
        mostrarError('Por favor introduce un email de prueba');
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner"></span> Enviando...';
    btn.disabled = true;
    
    ajaxRequest('test_smtp', {test_email: testEmail}, function(result) {
        mostrarExito(`${result.message}\n\nRevisa la bandeja de entrada de: ${testEmail}`);
    }, function(error) {
        mostrarError(error);
    }).finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Inicialización del documento
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos iniciales
    cargarServicios();
    
    // Manejador de búsqueda
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchClientes();
            }
        });
    }

    // Formulario de tipo de servicio
    const formTipoServicio = document.getElementById('formTipoServicio');
    if (formTipoServicio) {
        formTipoServicio.onsubmit = function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span> Guardando...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            ajaxRequest(data.action, data, function(result) {
                mostrarExito('Tipo de servicio guardado correctamente');
                closeModal('modalTipoServicio');
                location.reload();
            }).finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        };
    }

    // Formulario de editar servicio cliente
    const formEditarServicio = document.getElementById('formEditarServicio');
    if (formEditarServicio) {
        formEditarServicio.onsubmit = function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span> Actualizando...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            ajaxRequest(data.action, data, function(result) {
                mostrarExito(result.message);
                closeModal('modalEditarServicio');
                cargarServicios();
            }).finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        };
    }

    // Actualizar precio personalizado cuando cambie el servicio
    document.addEventListener('change', function(e) {
        if (e.target.name === 'tipo_servicio_id') {
            const option = e.target.selectedOptions[0];
            if (option) {
                const precio = option.dataset.precio;
                const precioInput = document.querySelector('[name="precio_personalizado"]');
                if (precioInput) {
                    precioInput.placeholder = `Precio base: €${precio}`;
                }
            }
        }
    });

    // Manejador de clics en estadísticas del header
    document.querySelectorAll('.header .stat-item').forEach(item => {
        item.addEventListener('click', function() {
            const texto = this.textContent.toLowerCase();
            if (texto.includes('vencimientos')) {
                showTab('vencimientos');
            } else if (texto.includes('servicios')) {
                showTab('vencimientos');
            } else if (texto.includes('clientes')) {
                showTab('clientes');
            }
        });
    });

    // Manejadores específicos para formularios con submit normal (no AJAX)
    const forms = document.querySelectorAll('form[method="POST"]:not([id])');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="loading-spinner"></span> Procesando...';
                btn.disabled = true;
                
                // Re-habilitar después de 5 segundos como fallback
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 5000);
            }
        });
    });

    console.log('?? Sistema de Gestión de Servicios Pro cargado correctamente');
    
    // Si tenemos datos del sistema, mostrar estadísticas en consola
    if (typeof DATOS_SISTEMA !== 'undefined') {
        console.log('?? Estadísticas del sistema:', {
            clientes: DATOS_SISTEMA.estadisticas_generales?.total_clientes || 0,
            servicios: DATOS_SISTEMA.estadisticas_generales?.total_servicios || 0,
            urgentes: DATOS_SISTEMA.estadisticas_generales?.servicios_urgentes || 0,
            facturacionPendiente: `€${(DATOS_SISTEMA.estadisticas_generales?.facturacion_pendiente || 0).toFixed(2)}`
        });
    }
});

// Cerrar modales con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    }
});

// Cerrar modal clickeando fuera
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

// Función para manejar errores de promesas
function handlePromiseError(promise) {
    return promise.catch(error => {
        console.error('Promise error:', error);
        mostrarError('Error inesperado: ' + error.message);
    });
}

// Auto-refrescar estadísticas cada 5 minutos
setInterval(function() {
    // Solo actualizar si no hay modales abiertos
    if (!document.querySelector('.modal.active')) {
        const tabActiva = document.querySelector('.tab.active');
        if (tabActiva?.textContent.includes('Todos los Servicios')) {
            cargarServicios();
        } else if (tabActiva?.textContent.includes('Albaranes')) {
            cargarAlbaranes();
        }
    }
}, 300000); // 5 minutos

// Funciones de utilidad para formatear datos
function formatearFecha(fecha, formato = 'es-ES') {
    return new Date(fecha).toLocaleDateString(formato);
}

function formatearMoneda(cantidad, moneda = 'EUR', locale = 'es-ES') {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: moneda
    }).format(cantidad);
}

// Función para validar formularios antes de enviar
function validarFormulario(form) {
    const required = form.querySelectorAll('[required]');
    let valid = true;
    
    required.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#f56565';
            valid = false;
        } else {
            field.style.borderColor = '#e2e8f0';
        }
    });
    
    return valid;
}

// Función para mostrar loading en elementos específicos
function showLoading(element, text = 'Cargando...') {
    if (element) {
        element.innerHTML = `
            <div style="text-align: center; padding: 20px; color: #a0aec0;">
                <div class="loading-spinner"></div>
                <div style="margin-top: 10px;">${text}</div>
            </div>
        `;
    }
}

// Función para detectar cambios no guardados
window.addEventListener('beforeunload', function(e) {
    const forms = document.querySelectorAll('form');
    let hasChanges = false;
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.defaultValue !== input.value) {
                hasChanges = true;
            }
        });
    });
    
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '¿Estás seguro de salir? Tienes cambios sin guardar.';
    }
});

// Función para debugging
function debugInfo() {
    console.log('?? Información de debug:', {
        serviciosSeleccionados,
        albaranesSeleccionados,
        serviciosData: serviciosData.length,
        albaranesData: albaranesData.length,
        modalsActive: document.querySelectorAll('.modal.active').length,
        currentTab: document.querySelector('.tab.active')?.textContent
    });
}
// Función para el checkbox de confirmación de reset
document.addEventListener('change', function(e) {
    if (e.target.id === 'confirmReset') {
        const btnReset = document.getElementById('btnReset');
        if (btnReset) btnReset.disabled = !e.target.checked;
    }
});

// Función para resetear la base de datos
function resetDatabase() {
    const checkboxes = {
        clientes: document.getElementById('resetClientes').checked,
        servicios: document.getElementById('resetServicios').checked,
        servicios_cliente: document.getElementById('resetServiciosCliente').checked,
        albaranes: document.getElementById('resetAlbaranes').checked,
        notificaciones: document.getElementById('resetNotificaciones').checked
    };
    
    const seleccionados = Object.keys(checkboxes).filter(key => checkboxes[key]);
    
    if (seleccionados.length === 0) {
        mostrarError('Debes seleccionar al menos un tipo de datos para eliminar');
        return;
    }
    
    const descripcion = seleccionados.map(key => {
        switch(key) {
            case 'clientes': return '• Todos los clientes';
            case 'servicios': return '• Todos los tipos de servicios';
            case 'servicios_cliente': return '• Todos los servicios asignados';
            case 'albaranes': return '• Todos los albaranes';
            case 'notificaciones': return '• Todas las notificaciones';
        }
    }).join('\n');
    
    const confirmed = confirm(`⚠️ ELIMINAR DATOS ⚠️\n\nVas a eliminar PERMANENTEMENTE:\n${descripcion}\n\n${seleccionados.includes('clientes') ? 'NOTA: Eliminar clientes también eliminará sus servicios y albaranes.\n\n' : ''}Las tablas quedarán VACÍAS (sin datos de ejemplo).\n\n¿Continuar?`);
    
    if (!confirmed) return;
    
    const btn = document.getElementById('btnReset');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner"></span> Eliminando...';
    btn.disabled = true;
    
    // Convertir checkboxes a formato correcto para FormData
const data = {
    clientes: checkboxes.clientes,
    servicios: checkboxes.servicios, 
    servicios_cliente: checkboxes.servicios_cliente,
    albaranes: checkboxes.albaranes,
    notificaciones: checkboxes.notificaciones
};

ajaxRequest('reset_database', data, function(result) {
        mostrarExito(result.message + '\nLa página se recargará...');
        setTimeout(() => {
            location.reload();
        }, 2000);
    }, function(error) {
        mostrarError('Error: ' + error);
        btn.innerHTML = originalText;
        btn.disabled = false;
        document.getElementById('confirmReset').checked = false;
    });
}
// Exponer función de debug globalmente
window.debugInfo = debugInfo;