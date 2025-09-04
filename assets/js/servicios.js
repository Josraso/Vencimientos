// assets/js/servicios.js - Funciones de servicios CORREGIDO

// Cargar servicios
function cargarServicios() {
    const filtros = obtenerFiltrosServicios();
    
    ajaxRequest('get_todos_servicios', filtros, function(result) {
        serviciosData = result.data;
        mostrarServicios(serviciosData);
        actualizarResumenEconomico(serviciosData);
        actualizarContadorSeleccionados();
    });
}

// Obtener filtros
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

// Mostrar servicios en tabla
function mostrarServicios(servicios) {
    const tbody = document.getElementById('serviciosTableBody');
    if (!tbody) return;
    
    if (servicios.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 40px; color: #a0aec0;">
                    <div style="font-size: 3em;">&#128721;</div>
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
        
        const iconoEstado = servicio.dias_restantes <= 2 ? '&#128680;' :
                           servicio.dias_restantes <= 7 ? '&#9888;' :
                           servicio.dias_restantes <= 30 ? '&#9989;' : '&#128994;';
        
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
                      '<span class="badge badge-info">&#128196; Facturado</span>' : 
                      '<span class="badge badge-secondary">&#128721; Sin facturar</span>'}
                </td>
                <td>
                    <div>&#128231; ${servicio.email}</div>
                    <div>&#128241; ${servicio.telefono || 'Sin teléfono'}</div>
                </td>
                <td>
                    <div class="actions-group">
                        <button class="btn btn-xs btn-primary" onclick="verCliente(${servicio.cliente_id})" title="Ver cliente">&#128065;</button>
                        <button class="btn btn-xs btn-warning" onclick="editarServicioCliente(${servicio.id})" title="Editar">&#9999;</button>
                        <button class="btn btn-xs btn-success" onclick="renovarServicio(${servicio.id})" title="Renovar">&#128260;</button>
                        ${servicio.tiene_albaran == 0 ? 
                          `<button class="btn btn-xs btn-info" onclick="generarAlbaran([${servicio.id}])" title="Generar albarán">&#128196;</button>` : ''}
                        <button class="btn btn-xs btn-secondary" onclick="enviarNotificacionIndividual(${servicio.id})" title="Enviar aviso">&#128231;</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Actualizar resumen económico
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
                <div class="stat-label">&#128680; Urgentes</div>
                <div class="stat-detail">${urgentes.length} servicios</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ed8936;">
                <div class="stat-number">€${proximos.reduce((sum, s) => sum + parseFloat(s.precio_final), 0).toFixed(2)}</div>
                <div class="stat-label">&#9888; Esta semana</div>
                <div class="stat-detail">${proximos.length} servicios</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #48bb78;">
                <div class="stat-number">€${esteMes.reduce((sum, s) => sum + parseFloat(s.precio_final), 0).toFixed(2)}</div>
                <div class="stat-label">&#128197; Este mes</div>
                <div class="stat-detail">${esteMes.length} servicios</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #4299e1;">
                <div class="stat-number">€${sinFacturar.reduce((sum, s) => sum + parseFloat(s.precio_final), 0).toFixed(2)}</div>
                <div class="stat-label">&#128196; Sin facturar</div>
                <div class="stat-detail">${sinFacturar.length} servicios</div>
            </div>
        `;
    }
}

// Aplicar filtros
function aplicarFiltros() {
    cargarServicios();
}

// Limpiar filtros
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

// Selección múltiple
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

// Renovar servicio
function renovarServicio(servicioId) {
    mostrarConfirmacion('¿Renovar este servicio por el mismo período?', function() {
        ajaxRequest('renovar_servicio', {servicio_id: servicioId}, function(result) {
            mostrarExito(result.message);
            // Recargar el modal del cliente si está abierto
            const clienteId = document.getElementById('serviciosContent')?.dataset.clienteId;
            if (clienteId) {
                verCliente(clienteId);
            } else {
                cargarServicios();
            }
        });
    });
}

// Renovar masivo
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

// Generar albarán
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
        
        if (confirm('&#128196; ' + mensaje)) {
            window.open('generar_pdf.php?albaran_id=' + result.data.albaran_id, '_blank');
        }
        
        serviciosSeleccionados = [];
        
        // Recargar el modal del cliente si está abierto
        const clienteId = document.getElementById('serviciosContent')?.dataset.clienteId;
        if (clienteId) {
            verCliente(clienteId);
        } else if (document.querySelector('.tab.active')?.textContent.includes('Todos los Servicios')) {
            cargarServicios();
        } else {
            location.reload();
        }
    }, function(error) {
        mostrarError(error);
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

// Editar servicio cliente
function editarServicioCliente(servicioId) {
    ajaxRequest('get_servicio_cliente', {servicio_id: servicioId}, function(result) {
        const servicio = result.data;
        
        document.getElementById('editarServicioId').value = servicioId;
        document.getElementById('editarServicioInfo').innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h4 style="color: #667eea;">&#128101; ${servicio.cliente_nombre} ${servicio.cliente_apellidos}</h4>
                    <p><strong>&#9881; Servicio:</strong> ${servicio.servicio_nombre}</p>
                    <p><strong>&#128176; Precio base:</strong> €${parseFloat(servicio.precio_base).toFixed(2)}</p>
                </div>
                <div style="text-align: right;">
                    <p><strong>&#128197; Próximo vencimiento:</strong></p>
                    <p style="font-size: 1.1em; color: #ed8936;">${new Date(servicio.fecha_proximo_vencimiento).toLocaleDateString('es-ES')}</p>
                </div>
            </div>
        `;
        
        document.getElementById('editarTipoVencimiento').value = servicio.tipo_vencimiento_id;
        document.getElementById('editarPrecioPersonalizado').value = servicio.precio_personalizado || '';
        document.getElementById('editarObservaciones').value = servicio.observaciones || '';
        document.getElementById('editarFechaVencimiento').value = servicio.fecha_proximo_vencimiento || '';
        
        closeModal('modalServicios');
        openModal('modalEditarServicio');
    });
}

// Ver clientes de un servicio
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
        
        document.getElementById('modalClientesServicioTitle').innerHTML = `&#128101; Clientes de: ${servicio.nombre}`;
        
        let html = `
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid #48bb78;">
                <h4 style="color: #48bb78; margin-bottom: 10px;">&#9881; ${servicio.nombre}</h4>
                <p style="margin: 5px 0;">${servicio.descripcion}</p>
                <p style="margin: 10px 0 0 0;"><strong>&#128176; Precio base:</strong> €${parseFloat(servicio.precio).toFixed(2)}</p>
            </div>
            
            <h4 style="margin-bottom: 20px;">&#128101; Clientes con este servicio (${clientes.length})</h4>
        `;
        
        if (clientes.length === 0) {
            html += `
                <div style="text-align: center; color: #a0aec0; padding: 40px; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 3em;">&#128721;</div>
                    <div style="margin-top: 10px;">No hay clientes con este servicio asignado</div>
                </div>
            `;
        } else {
            html += '<div class="table-container"><table><thead><tr><th>Cliente</th><th>Empresa</th><th>Precio</th><th>Próximo Venc.</th><th>Días Rest.</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
            
            clientes.forEach(cliente => {
                const clase = cliente.dias_restantes <= 2 ? 'vencimiento-urgente' : 
                             cliente.dias_restantes <= 7 ? 'vencimiento-proximo' : 
                             cliente.dias_restantes <= 30 ? 'vencimiento-ok' : 'vencimiento-lejano';
                
                const icono = cliente.dias_restantes <= 2 ? '&#128680;' :
                             cliente.dias_restantes <= 7 ? '&#9888;' :
                             cliente.dias_restantes <= 30 ? '&#9989;' : '&#128994;';
                
                html += `
                    <tr class="${clase}">
                        <td>
                            <strong>${cliente.nombre} ${cliente.apellidos}</strong><br>
                            <small>&#128231; ${cliente.email} | &#128241; ${cliente.telefono || 'Sin teléfono'}</small>
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
                            ${cliente.tiene_albaran > 0 ? '<span class="badge badge-info">&#128196; Facturado</span>' : '<span class="badge badge-secondary">&#128721; Sin facturar</span>'}
                        </td>
                        <td>
                            <div class="actions-group">
                                <button class="btn btn-xs btn-primary" onclick="verCliente(${cliente.id}); closeModal('modalClientesServicio');" title="Ver cliente">&#128065;</button>
                                <button class="btn btn-xs btn-warning" onclick="editarServicioCliente(${cliente.servicio_cliente_id}); closeModal('modalClientesServicio');" title="Editar servicio">&#9999;</button>
                                ${cliente.tiene_albaran == 0 ? `<button class="btn btn-xs btn-success" onclick="generarAlbaran([${cliente.servicio_cliente_id}])" title="Generar albarán">&#128196;</button>` : ''}
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
                    <h4 style="margin-bottom: 15px;">&#128202; Resumen del Servicio</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="background: #e6fffa; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #48bb78;">
                            <div style="font-size: 1.5em; font-weight: bold; color: #48bb78;">€${totalIngresos.toFixed(2)}</div>
                            <div>&#128176; Ingresos Totales</div>
                        </div>
                        <div style="background: #fff5f5; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid ${sinFacturar > 0 ? '#f56565' : '#48bb78'};">
                            <div style="font-size: 1.5em; font-weight: bold; color: ${sinFacturar > 0 ? '#f56565' : '#48bb78'};">${sinFacturar}</div>
                            <div>${sinFacturar > 0 ? '&#10060;' : '&#9989;'} Pendientes de Facturar</div>
                        </div>
                        <div style="background: #fffbf0; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid ${proximosVencer > 0 ? '#ed8936' : '#48bb78'};">
                            <div style="font-size: 1.5em; font-weight: bold; color: ${proximosVencer > 0 ? '#ed8936' : '#48bb78'};">${proximosVencer}</div>
                            <div>${proximosVencer > 0 ? '&#9888;' : '&#9989;'} Próximos a Vencer</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        document.getElementById('clientesServicioContent').innerHTML = html;
    });
}

// Editar tipo de servicio
function editarTipoServicio(id) {
    ajaxRequest('get_tipo_servicio', {servicio_id: id}, function(result) {
        const servicio = result.data;
        
        document.querySelector('#formTipoServicio input[name="action"]').value = 'edit_servicio_tipo';
        document.getElementById('tipoServicioId').value = servicio.id;
        document.getElementById('modalTipoServicioTitle').innerHTML = '&#9999; Editar Tipo de Servicio';
        document.getElementById('btnSubmitTipoServicio').innerHTML = '&#128190; Actualizar Servicio';
        
        document.getElementById('tipoServicioNombre').value = servicio.nombre;
        document.getElementById('tipoServicioDescripcion').value = servicio.descripcion;
        document.getElementById('tipoServicioPrecio').value = servicio.precio;
        
        openModal('modalTipoServicio');
    });
}

// Enviar notificaciones
function enviarNotificacionIndividual(servicioId) {
    mostrarConfirmacion('¿Enviar notificación por email para este servicio?\n\nSe enviará al cliente y al administrador.', function() {
        const btn = event?.target;
        const originalText = btn?.innerHTML;
        if (btn) {
            btn.innerHTML = '<span class="loading-spinner"></span>';
            btn.disabled = true;
        }
        
        ajaxRequest('enviar_notificacion_individual', {servicio_id: servicioId}, function(result) {
            mostrarExito(result.message);
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }, function(error) {
            mostrarError(error);
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    });
}

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

// Función para eliminar servicio definitivamente
function eliminarServicioCliente(servicioId) {
    mostrarConfirmacion('¿Eliminar definitivamente este servicio? Esta acción no se puede deshacer.', function() {
        ajaxRequest('eliminar_servicio_cliente', {servicio_id: servicioId}, function(result) {
            mostrarExito(result.message);
            
            // Recargar el modal del cliente si está abierto
            const clienteId = document.getElementById('serviciosContent')?.dataset.clienteId;
            if (clienteId) {
                verCliente(clienteId);
            } else {
                location.reload();
            }
        });
    });
}

// Función para eliminar tipo de servicio
function eliminarTipoServicio(id) {
    mostrarConfirmacion('¿Eliminar este tipo de servicio? Solo se puede eliminar si no tiene clientes asignados.', function() {
        ajaxRequest('eliminar_tipo_servicio', {servicio_id: id}, function(result) {
            mostrarExito(result.message);
            location.reload();
        });
    });
}

// Hacer las funciones disponibles globalmente para los onclick inline
window.eliminarTipoServicio = eliminarTipoServicio;
window.editarTipoServicio = editarTipoServicio;
window.verClientesServicio = verClientesServicio;
window.eliminarServicioCliente = eliminarServicioCliente;
window.renovarServicio = renovarServicio;
window.editarServicioCliente = editarServicioCliente;
window.generarAlbaran = generarAlbaran;
window.enviarNotificacionIndividual = enviarNotificacionIndividual;
window.renovarMasivo = renovarMasivo;
window.enviarNotificacionesMasivas = enviarNotificacionesMasivas;
window.aplicarFiltros = aplicarFiltros;
window.limpiarFiltros = limpiarFiltros;
window.toggleSelectAll = toggleSelectAll;
window.toggleServicioSeleccion = toggleServicioSeleccion;
window.renovarSeleccionados = renovarSeleccionados;
window.generarAlbaranSeleccionados = generarAlbaranSeleccionados;
window.enviarNotificacionesSeleccionadas = enviarNotificacionesSeleccionadas;