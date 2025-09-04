// assets/js/clientes.js - Funciones de clientes

// Búsqueda de clientes
function searchClientes() {
    const search = document.getElementById('searchInput').value;
    window.location.href = '?search=' + encodeURIComponent(search);
}

function clearSearch() {
    window.location.href = '?';
}

// Ver cliente detalle
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
                        <h3 style="color: #667eea; margin-bottom: 10px;">&#128101; ${cliente.nombre} ${cliente.apellidos}</h3>
                        ${cliente.dni ? `<p style="margin: 5px 0;"><strong>&#128179; DNI:</strong> ${cliente.dni}</p>` : ''}
                        ${cliente.empresa ? `<p style="margin: 5px 0;"><strong>&#127970; Empresa:</strong> ${cliente.empresa}</p>` : ''}
                        <p style="margin: 5px 0;"><strong>&#128231; Email:</strong> ${cliente.email}</p>
                        <p style="margin: 5px 0;"><strong>&#128241; Teléfono:</strong> ${cliente.telefono || 'No especificado'}</p>
                        ${cliente.direccion ? `<p style="margin: 5px 0;"><strong>&#128205; Dirección:</strong> ${cliente.direccion}, ${cliente.ciudad || ''} ${cliente.codigo_postal || ''}</p>` : ''}
                    </div>
                    <div class="actions-group">
                        <button class="btn btn-success" onclick="asignarServicio(${cliente.id})">&#10133; Asignar Servicio</button>
                        <button class="btn btn-primary" onclick="editarCliente(${cliente.id}); closeModal('modalServicios');">&#9999; Editar Cliente</button>
                    </div>
                </div>
            </div>
            
            <h4 style="margin-bottom: 20px;">&#128196; Servicios Contratados (${servicios.length})</h4>
        `;
        
        if (servicios.length === 0) {
            html += `
                <div style="text-align: center; color: #a0aec0; padding: 40px; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 3em;">&#128721;</div>
                    <div style="margin-top: 10px;">Este cliente no tiene servicios asignados</div>
                    <button class="btn btn-primary" onclick="asignarServicio(${cliente.id})" style="margin-top: 15px;">&#10133; Asignar Primer Servicio</button>
                </div>
            `;
        } else {
            html += '<div class="table-container"><table><thead><tr><th>Servicio</th><th>Precio</th><th>Vencimiento</th><th>Días Rest.</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
            
            servicios.forEach(servicio => {
                const clase = servicio.dias_restantes <= 2 ? 'vencimiento-urgente' : 
                             servicio.dias_restantes <= 7 ? 'vencimiento-proximo' : 
                             servicio.dias_restantes <= 30 ? 'vencimiento-ok' : 'vencimiento-lejano';
                
                const icono = servicio.dias_restantes <= 2 ? '&#128680;' :
                             servicio.dias_restantes <= 7 ? '&#9888;' :
                             servicio.dias_restantes <= 30 ? '&#9989;' : '&#128994;';
                
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
                            ${servicio.activo == 1 ? '<span class="badge badge-success">&#9989; Activo</span>' : '<span class="badge badge-danger">&#10060; Inactivo</span>'}
                        </td>
                        <td>
                            <div class="actions-group">
                                <button class="btn btn-xs btn-warning" onclick="editarServicioCliente(${servicio.id})" title="Editar">&#9999;</button>
                                <button class="btn btn-xs btn-success" onclick="renovarServicio(${servicio.id})" title="Renovar">&#128260;</button>
                                ${servicio.tiene_albaran == 0 ? 
                                  `<button class="btn btn-xs btn-info" onclick="generarAlbaran([${servicio.id}])" title="Generar albarán">&#128196;</button>` : ''}
                                <button class="btn btn-xs ${servicio.activo == 1 ? 'btn-danger' : 'btn-success'}" 
                                        onclick="toggleServicioCliente(${servicio.id}, ${servicio.activo == 1 ? 0 : 1})" 
                                        title="${servicio.activo == 1 ? 'Desactivar' : 'Activar'}">
                                    ${servicio.activo == 1 ? '&#10060;' : '&#9989;'}
                                </button>
                                ${servicio.tiene_albaran == 0 ? `<button class="btn btn-xs btn-danger" onclick="eliminarServicioCliente(${servicio.id})" title="Eliminar definitivamente">&#128465;</button>` : ''}
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
                                <div>&#128176; Total mensual activo</div>
                            </div>
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: ${proximosVencer > 0 ? '#ed8936' : '#48bb78'};">
                                    ${proximosVencer}
                                </div>
                                <div>${proximosVencer > 0 ? '&#9888;' : '&#9989;'} Próximos a vencer</div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
        
        document.getElementById('serviciosContent').innerHTML = html;
    });
}

// Editar cliente
function editarCliente(id) {
    ajaxRequest('get_cliente', {cliente_id: id}, function(result) {
        const cliente = result.data.cliente;
        
        document.querySelector('#formCliente input[name="action"]').value = 'edit_cliente';
        document.getElementById('clienteId').value = cliente.id;
        document.getElementById('modalClienteTitle').innerHTML = '&#9999; Editar Cliente';
        document.getElementById('btnSubmitCliente').innerHTML = '&#128190; Actualizar Cliente';
        
        document.getElementById('clienteNombre').value = cliente.nombre;
        document.getElementById('clienteApellidos').value = cliente.apellidos;
        document.getElementById('clienteDni').value = cliente.dni || '';
        document.getElementById('clienteEmail').value = cliente.email;
        document.getElementById('clienteTelefono').value = cliente.telefono || '';
        document.getElementById('clienteEmpresa').value = cliente.empresa || '';
        document.getElementById('clienteDireccion').value = cliente.direccion || '';
        document.getElementById('clienteCiudad').value = cliente.ciudad || '';
        document.getElementById('clienteCodigo').value = cliente.codigo_postal || '';
        
        openModal('modalCliente');
    });
}

// Asignar servicio
function asignarServicio(clienteId) {
    ajaxRequest('get_cliente', {cliente_id: clienteId}, function(result) {
        const cliente = result.data.cliente;
        
        document.getElementById('asignarClienteId').value = clienteId;
        document.getElementById('asignarClienteInfo').innerHTML = `
            <h4 style="color: #667eea;">&#128101; ${cliente.nombre} ${cliente.apellidos}</h4>
            <p><strong>&#128231;</strong> ${cliente.email} ${cliente.empresa ? ` | <strong>&#127970;</strong> ${cliente.empresa}` : ''}</p>
        `;
        
        closeModal('modalServicios');
        openModal('modalAsignarServicio');
    });
}

// Eliminar cliente
function eliminarClienteSeguro(clienteId) {
    ajaxRequest('validar_borrado_cliente', {cliente_id: clienteId}, function(result) {
        const data = result.data;
        let contenido = '';
        
        if (data.puede_borrar) {
            contenido = `
                <div class="alert success">
                    <h4>&#9989; Cliente sin servicios activos</h4>
                    <p>Este cliente no tiene servicios activos y puede ser eliminado de forma segura.</p>
                </div>
            `;
        } else {
            contenido = `
                <div class="alert error">
                    <h4>&#10060; Cliente con servicios activos</h4>
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