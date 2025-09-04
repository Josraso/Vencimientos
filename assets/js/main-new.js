let serviciosData = [];
let albaranesData = [];
let serviciosSeleccionados = [];
let albaranesSeleccionados = [];

function showTab(tabName) {
    // Ocultar todas las tabs
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    
    // Mostrar la tab seleccionada
    const targetTab = document.getElementById(tabName);
    const targetButton = document.querySelector(`[data-tab="${tabName}"]`);
    
    if (targetTab && targetButton) {
        targetTab.classList.add('active');
        targetButton.classList.add('active');
        
        // Actualizar URL
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({tab: tabName}, '', url);
        
        // Cargar datos específicos
        if (tabName === 'vencimientos') {
            setTimeout(() => cargarServicios(), 100);
        } else if (tabName === 'albaranes') {
            setTimeout(() => cargarAlbaranes(), 100);
        }
    }
}

// Manejar botón atrás del navegador
window.addEventListener('popstate', (e) => {
    const tab = e.state?.tab || new URLSearchParams(window.location.search).get('tab') || 'vencimientos';
    showTabSilent(tab);
});

function showTabSilent(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    
    const targetTab = document.getElementById(tabName);
    const targetButton = document.querySelector(`[data-tab="${tabName}"]`);
    
    if (targetTab && targetButton) {
        targetTab.classList.add('active');
        targetButton.classList.add('active');
        
        // Cargar datos si es necesario
        if (tabName === 'vencimientos') {
            setTimeout(() => cargarServicios(), 100);
        } else if (tabName === 'albaranes') {
            setTimeout(() => cargarAlbaranes(), 100);
        }
    }
}

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
    btn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s linear infinite;"></span> Asignando...';
    btn.disabled = true;
    
    ajaxRequest('add_servicio_cliente', data, function(result) {
        mostrarExito('Servicio asignado correctamente');
        
        const clienteId = data.cliente_id;
        if (clienteId) {
            verCliente(clienteId);
        }
    }, function(error) {
        mostrarError(error);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
    
    return false;
}

// Función para búsqueda de clientes
function searchClientes() {
    const search = document.getElementById('clientSearchInput').value;
    window.location.href = '?tab=clientes&search=' + encodeURIComponent(search);
}

function clearSearch() {
    window.location.href = '?tab=clientes';
}

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar con la tab correcta
    const tabActual = new URLSearchParams(window.location.search).get('tab') || 'vencimientos';
    showTabSilent(tabActual);
    
    // Configurar event listeners para las tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            if (tabName) {
                showTab(tabName);
            }
        });
    });
    
    // Event listeners para stats en header
    document.querySelectorAll('.header .stat-mini').forEach(item => {
        item.addEventListener('click', function() {
            const filter = this.dataset.filter;
            if (filter) {
                showTab('vencimientos');
                setTimeout(() => {
                    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
                    const targetPill = document.querySelector(`[data-filter="${filter}"]`);
                    if (targetPill) {
                        targetPill.classList.add('active');
                        aplicarFiltros();
                    }
                }, 200);
            }
        });
    });
    
    // Cargar datos iniciales
    if (tabActual === 'vencimientos') {
        setTimeout(() => cargarServicios(), 100);
    } else if (tabActual === 'albaranes') {
        setTimeout(() => cargarAlbaranes(), 100);
    }
    
    // Configurar formularios
    const formTipoServicio = document.getElementById('formTipoServicio');
    if (formTipoServicio) {
        formTipoServicio.onsubmit = function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s linear infinite;"></span> Guardando...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            ajaxRequest(data.action, data, function(result) {
                mostrarExito('Tipo de servicio guardado correctamente');
                location.reload();
            }, function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        };
    }

    const formEditarServicio = document.getElementById('formEditarServicio');
    if (formEditarServicio) {
        formEditarServicio.onsubmit = function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s linear infinite;"></span> Actualizando...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            ajaxRequest(data.action, data, function(result) {
                mostrarExito(result.message);
                
                const clienteId = document.getElementById('serviciosContent')?.dataset.clienteId;
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

    // Formularios con loading
    const forms = document.querySelectorAll('form[method="POST"]:not([id])');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s linear infinite;"></span> Procesando...';
                btn.disabled = true;
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 5000);
            }
        });
    });

    console.log('📊 Sistema de Gestión de Servicios Pro cargado correctamente');
});

// Añadir CSS para spinner inline
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Auto-refrescar cada 5 minutos
setInterval(function() {
    if (!document.querySelector('.modal.active')) {
        const tabActiva = document.querySelector('.tab.active');
        if (tabActiva?.dataset.tab === 'vencimientos') {
            cargarServicios();
        } else if (tabActiva?.dataset.tab === 'albaranes') {
            cargarAlbaranes();
        }
    }
}, 300000);

// Detectar cambios no guardados
window.addEventListener('beforeunload', function(e) {
    const formsWithChanges = document.querySelectorAll('form.has-changes');
    if (formsWithChanges.length > 0) {
        e.preventDefault();
        e.returnValue = '¿Estás seguro de salir? Tienes cambios sin guardar.';
    }
});

document.addEventListener('input', function(e) {
    if (e.target.form && !e.target.form.classList.contains('no-warn')) {
        e.target.form.classList.add('has-changes');
    }
});

document.addEventListener('submit', function(e) {
    if (e.target.tagName === 'FORM') {
        e.target.classList.remove('has-changes');
    }
});

// Exponer funciones globalmente
window.showTab = showTab;
window.procesarAsignarServicio = procesarAsignarServicio;
window.searchClientes = searchClientes;
window.clearSearch = clearSearch;