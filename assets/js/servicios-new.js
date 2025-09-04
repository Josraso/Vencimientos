function cargarServicios() {
    const loading = document.getElementById('loading');
    const container = document.getElementById('servicesContainer');
    
    if (loading) loading.style.display = 'block';
    if (container) container.innerHTML = '';
    
    const filtros = obtenerFiltrosServicios();
    
    ajaxRequest('get_todos_servicios', filtros, function(result) {
        serviciosData = result.data || [];
        mostrarServiciosCards(serviciosData);
        actualizarContadorSeleccionados();
        if (loading) loading.style.display = 'none';
    }, function(error) {
        if (loading) loading.style.display = 'none';
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <div style="font-size: 4rem;">⚠️</div>
                    <div style="margin-top: 1rem;">Error cargando servicios</div>
                    <div style="margin-top: 0.5rem; color: #9ca3af;">${error}</div>
                </div>
            `;
        }
    });
}

function obtenerFiltrosServicios() {
    const filtroActual = document.querySelector('.filter-pill.active')?.dataset.filter || 'todos';
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const serviceFilter = document.getElementById('serviceFilter')?.value || '';
    
    let estado = '';
    if (filtroActual === 'urgente') estado = 'urgente';
    else if (filtroActual === 'proximo') estado = 'proximo';
    else if (filtroActual === 'mes') estado = 'este_mes';
    
    return {
        filtro_estado: estado,
        filtro_servicio: serviceFilter,
        filtro_cliente: searchTerm,
        filtro_fecha_desde: '',
        filtro_fecha_hasta: '',
        filtro_facturado: ''
    };
}

function mostrarServiciosCards(servicios) {
    const container = document.getElementById('servicesContainer');
    if (!container) return;
    
    if (!servicios || servicios.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div style="font-size: 4rem;">🔍</div>
                <div style="margin-top: 1rem;">No se encontraron servicios</div>
                <div style="margin-top: 0.5rem; color: #9ca3af;">Prueba a cambiar los filtros o añadir servicios a los clientes</div>
            </div>
        `;
        return;
    }
    
    container.innerHTML = servicios.map(servicio => {
        const clase = servicio.dias_restantes <= 2 ? 'urgente' : 
                     servicio.dias_restantes <= 7 ? 'proximo' : 
                     servicio.dias_restantes <= 30 ? 'ok' : 'lejano';
        
        const iconoEstado = servicio.dias_restantes <= 2 ? '🚨' :
                           servicio.dias_restantes <= 7 ? '⏰' :
                           servicio.dias_restantes <= 30 ? '✅' : '📅';
        
        return `
            <div class="service-card ${clase}">
                <input type="checkbox" class="service-checkbox" value="${servicio.id}">
                
                <div class="service-info">
                    <h4>${servicio.nombre} ${servicio.apellidos} - ${servicio.empresa || 'Sin empresa'}</h4>
                    <div class="service-details">
                        <span>📧 ${servicio.email}</span>
                        <span>📱 ${servicio.telefono || 'Sin teléfono'}</span>
                        <span><strong>${servicio.servicio_nombre}</strong></span>
                    </div>
                </div>
                
                <div class="service-meta">
                    <div class="service-date">${new Date(servicio.fecha_proximo_vencimiento).toLocaleDateString('es-ES')}</div>
                    <div class="service-days ${clase}">${iconoEstado} ${servicio.dias_restantes} días</div>
                </div>
                
                <div class="service-price">€${parseFloat(servicio.precio_final).toFixed(2)}</div>
                
                <div class="service-actions">
                    <button class="action-btn renovar" onclick="renovarServicioRapido(${servicio.id})" title="Renovar">🔄</button>
                    <button class="action-btn avisar" onclick="enviarAvisoRapido(${servicio.id})" title="Enviar aviso">📧</button>
                    <button class="action-btn albaran" onclick="generarAlbaranRapido(${servicio.id})" title="Generar albarán">📄</button>
                    <button class="action-btn editar" onclick="editarServicioCliente(${servicio.id})" title="Editar">✏️</button>
                </div>
            </div>
        `;
    }).join('');
    
    // Reactivar checkboxes
    container.querySelectorAll('.service-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectionFromCheckbox);
    });
}

function aplicarFiltros() {
    cargarServicios();
}

function updateSelectionFromCheckbox() {
    serviciosSeleccionados = [];
    document.querySelectorAll('.service-checkbox:checked').forEach(cb => {
        serviciosSeleccionados.push(parseInt(cb.value));
    });
    actualizarContadorSeleccionados();
}

function actualizarContadorSeleccionados() {
    const contador = document.getElementById('selectedCount');
    const bulkBar = document.getElementById('bulkBar');
    
    if (contador) {
        contador.textContent = `${serviciosSeleccionados.length} servicios seleccionados`;
    }
    
    if (bulkBar) {
        if (serviciosSeleccionados.length > 0) {
            bulkBar.classList.add('show');
        } else {
            bulkBar.classList.remove('show');
        }
    }
}

function renovarServicioRapido(servicioId) {
    ajaxRequest('renovar_servicio', {servicio_id: servicioId}, function(result) {
        mostrarNotificacion('✅ ' + result.message);
        cargarServicios();
    });
}

function enviarAvisoRapido(servicioId) {
    ajaxRequest('enviar_notificacion_individual', {servicio_id: servicioId}, function(result) {
        mostrarNotificacion('📧 Aviso enviado correctamente');
    });
}

function generarAlbaranRapido(servicioId) {
    ajaxRequest('generar_albaran', {servicios_ids: [servicioId]}, function(result) {
        mostrarNotificacion(`📄 Albarán ${result.data.numero} generado - €${parseFloat(result.data.total).toFixed(2)}`);
        cargarServicios();
    });
}

function renovarSeleccionados() {
    if (serviciosSeleccionados.length === 0) {
        mostrarError('Selecciona al menos un servicio para renovar');
        return;
    }
    
    ajaxRequest('renovar_masivo', {servicios_ids: serviciosSeleccionados}, function(result) {
        mostrarNotificacion('✅ ' + result.message);
        serviciosSeleccionados = [];
        cargarServicios();
    });
}

function enviarAvisosSeleccionados() {
    if (serviciosSeleccionados.length === 0) {
        mostrarError('Selecciona al menos un servicio');
        return;
    }
    
    mostrarNotificacion(`📧 Enviando avisos a ${serviciosSeleccionados.length} clientes...`);
    
    let enviados = 0;
    serviciosSeleccionados.forEach(servicioId => {
        ajaxRequest('enviar_notificacion_individual', {servicio_id: servicioId}, function(result) {
            enviados++;
            if (enviados === serviciosSeleccionados.length) {
                mostrarNotificacion(`✅ Enviados ${enviados} avisos correctamente`);
            }
        });
    });
}

function generarAlbaranSeleccionados() {
    if (serviciosSeleccionados.length === 0) {
        mostrarError('Selecciona al menos un servicio');
        return;
    }
    
    ajaxRequest('generar_albaran', {servicios_ids: serviciosSeleccionados}, function(result) {
        mostrarNotificacion(`📄 Albarán ${result.data.numero} generado - €${parseFloat(result.data.total).toFixed(2)}`);
        serviciosSeleccionados = [];
        cargarServicios();
    });
}

function editarServicioCliente(servicioId) {
    ajaxRequest('get_servicio_cliente', {servicio_id: servicioId}, function(result) {
        const servicio = result.data;
        
        document.getElementById('editarServicioId').value = servicioId;
        document.getElementById('editarServicioInfo').innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h4 style="color: #667eea;">👤 ${servicio.cliente_nombre} ${servicio.cliente_apellidos}</h4>
                    <p><strong>⚙️ Servicio:</strong> ${servicio.servicio_nombre}</p>
                    <p><strong>💰 Precio base:</strong> €${parseFloat(servicio.precio_base).toFixed(2)}</p>
                </div>
                <div style="text-align: right;">
                    <p><strong>📅 Próximo vencimiento:</strong></p>
                    <p style="font-size: 1.1em; color: #ed8936;">${new Date(servicio.fecha_proximo_vencimiento).toLocaleDateString('es-ES')}</p>
                </div>
            </div>
        `;
        
        document.getElementById('editarTipoVencimiento').value = servicio.tipo_vencimiento_id;
        document.getElementById('editarPrecioPersonalizado').value = servicio.precio_personalizado || '';
        document.getElementById('editarObservaciones').value = servicio.observaciones || '';
        document.getElementById('editarFechaVencimiento').value = servicio.fecha_proximo_vencimiento || '';
        
        openModal('modalEditarServicio');
    });
}

function mostrarNotificacion(mensaje) {
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #059669;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 2000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 350px;
        word-wrap: break-word;
        font-size: 0.9rem;
    `;
    notif.textContent = mensaje;
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.transform = 'translateX(0)';
    }, 10);
    
    setTimeout(() => {
        notif.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notif)) {
                document.body.removeChild(notif);
            }
        }, 300);
    }, 4000);
}

// Event listeners cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Filtros pills
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            cargarServicios();
        });
    });
    
    // Búsqueda con debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(cargarServicios, 500));
    }
    
    // Filtro de servicios
    const serviceFilter = document.getElementById('serviceFilter');
    if (serviceFilter) {
        serviceFilter.addEventListener('change', cargarServicios);
    }
});

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Funciones globales
window.cargarServicios = cargarServicios;
window.aplicarFiltros = aplicarFiltros;
window.renovarSeleccionados = renovarSeleccionados;
window.enviarAvisosSeleccionados = enviarAvisosSeleccionados;
window.generarAlbaranSeleccionados = generarAlbaranSeleccionados;
window.editarServicioCliente = editarServicioCliente;
window.renovarServicioRapido = renovarServicioRapido;
window.enviarAvisoRapido = enviarAvisoRapido;
window.generarAlbaranRapido = generarAlbaranRapido;