// assets/js/albaranes.js - Funciones de albaranes

// Cargar albaranes
function cargarAlbaranes() {
    const filtros = obtenerFiltrosAlbaranes();
    
    ajaxRequest('get_albaranes', filtros, function(result) {
        albaranesData = result.data;
        mostrarAlbaranes(albaranesData);
        actualizarEstadisticasAlbaranes(albaranesData);
        actualizarContadorAlbaranesSeleccionados();
    });
}

// Obtener filtros
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

// Mostrar albaranes
function mostrarAlbaranes(albaranes) {
    const tbody = document.getElementById('albaranesTableBody');
    if (!tbody) return;
    
    if (albaranes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #a0aec0;">
                    <div style="font-size: 3em;">&#128721;</div>
                    <div style="margin-top: 10px;">No hay albaranes que mostrar</div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = albaranes.map(albaran => {
        const estados = {
            'borrador': '<span class="badge badge-secondary">&#128221; Borrador</span>',
            'generado': '<span class="badge badge-warning">&#9989; Generado</span>',
            'enviado': '<span class="badge badge-info">&#128228; Enviado</span>',
            'pagado': '<span class="badge badge-success">&#128176; Pagado</span>'
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
                        <button class="btn btn-xs btn-primary" onclick="verAlbaranPDF(${albaran.id})" title="Ver PDF">&#128065;</button>
                        <button class="btn btn-xs btn-success" onclick="descargarAlbaranPDF(${albaran.id})" title="Descargar">&#128190;</button>
                        <button class="btn btn-xs btn-info" onclick="cambiarEstadoAlbaran(${albaran.id})" title="Cambiar estado">&#128260;</button>
                        <button class="btn btn-xs btn-danger" onclick="eliminarAlbaran(${albaran.id})" title="Eliminar">&#128465;</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Actualizar estadísticas
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
            <div class="stat-label">&#128196; Total Albaranes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">€${totalFacturado.toFixed(2)}</div>
            <div class="stat-label">&#128176; Facturado Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">${porEstado.pagado}</div>
            <div class="stat-label">&#9989; Pagados</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">${porEstado.generado + porEstado.enviado}</div>
            <div class="stat-label">&#9203; Pendientes</div>
        </div>
    `;
}

// Filtros
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

// Selección múltiple
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

// Acciones de albaranes
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