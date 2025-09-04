// assets/js/utils.js - Utilidades comunes

// Funciones de utilidad
function mostrarError(mensaje) {
    alert('❌ ' + mensaje);
}

function mostrarExito(mensaje) {
    alert('✅ ' + mensaje);
}

function mostrarConfirmacion(mensaje, callback) {
    if (confirm('⚠️ ' + mensaje)) {
        callback();
    }
}

// Función AJAX principal
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
                mostrarError(error);
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
    })
    .finally(() => {
        // Restaurar botones si existen
        const btn = event?.target;
        if (btn && btn.classList && btn.classList.contains('btn')) {
            btn.disabled = false;
            if (btn.dataset && btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
            }
        }
    });
}

// Formatear fecha
function formatearFecha(fecha, formato = 'es-ES') {
    return new Date(fecha).toLocaleDateString(formato);
}

// Formatear moneda
function formatearMoneda(cantidad, moneda = 'EUR', locale = 'es-ES') {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: moneda
    }).format(cantidad);
}

// Mostrar loading
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

// Validar formulario
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

// Debug info
function debugInfo() {
    console.log('📊 Información de debug:', {
        serviciosSeleccionados,
        albaranesSeleccionados,
        serviciosData: serviciosData.length,
        albaranesData: albaranesData.length,
        modalsActive: document.querySelectorAll('.modal.active').length,
        currentTab: document.querySelector('.tab.active')?.textContent
    });
}

// Exponer función globalmente
window.debugInfo = debugInfo;