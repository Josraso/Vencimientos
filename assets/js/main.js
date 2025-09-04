// assets/js/main.js - Archivo principal coordinador

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

// Procesar asignación de servicio sin recargar página
function procesarAsignarServicio(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner"></span> Asignando...';
    btn.disabled = true;
    
    // Enviar por AJAX en lugar de POST tradicional
    ajaxRequest('add_servicio_cliente', data, function(result) {
        mostrarExito('Servicio asignado correctamente');
        closeModal('modalAsignarServicio');
        
        // Recargar el modal del cliente si está abierto
        const clienteId = data.cliente_id;
        if (clienteId) {
            verCliente(clienteId);
        }
    }, function(error) {
        mostrarError(error);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
    
    return false; // Prevenir el envío tradicional del formulario
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
            }, function() {
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
                
                // Recargar el modal del cliente si está abierto
                const clienteId = document.getElementById('serviciosContent').dataset.clienteId;
                if (clienteId) {
                    verCliente(clienteId);
                } else {
                    cargarServicios();
                }
            }, function() {
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
                document.querySelector('.tab:nth-child(3)').click();
            } else if (texto.includes('servicios')) {
                document.querySelector('.tab:nth-child(3)').click();
            } else if (texto.includes('clientes')) {
                document.querySelector('.tab:first-child').click();
            }
        });
    });

    // Manejadores para formularios con submit normal
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

    console.log('&#128202; Sistema de Gestión de Servicios Pro cargado correctamente');
    
    // Si tenemos datos del sistema, mostrar estadísticas en consola
    if (typeof DATOS_SISTEMA !== 'undefined') {
        console.log('&#128202; Estadísticas del sistema:', {
            clientes: DATOS_SISTEMA.estadisticas_generales?.total_clientes || 0,
            servicios: DATOS_SISTEMA.estadisticas_generales?.total_servicios || 0,
            urgentes: DATOS_SISTEMA.estadisticas_generales?.servicios_urgentes || 0,
            facturacionPendiente: `€${(DATOS_SISTEMA.estadisticas_generales?.facturacion_pendiente || 0).toFixed(2)}`
        });
    }
});

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

// Función para detectar cambios no guardados
window.addEventListener('beforeunload', function(e) {
    // Solo avisar si hay un formulario con clase 'has-changes'
    const formsWithChanges = document.querySelectorAll('form.has-changes');
    if (formsWithChanges.length > 0) {
        e.preventDefault();
        e.returnValue = '¿Estás seguro de salir? Tienes cambios sin guardar.';
    }
});

// Marcar formularios con cambios
document.addEventListener('input', function(e) {
    if (e.target.form && !e.target.form.classList.contains('no-warn')) {
        e.target.form.classList.add('has-changes');
    }
});

// Limpiar marca al enviar
document.addEventListener('submit', function(e) {
    if (e.target.tagName === 'FORM') {
        e.target.classList.remove('has-changes');
    }
});

// Hacer la función showTab disponible globalmente
window.showTab = showTab;
window.procesarAsignarServicio = procesarAsignarServicio;