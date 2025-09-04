// assets/js/config.js - Funciones de configuración

// Test SMTP
function testSMTP() {
    const testEmail = document.getElementById('testEmail').value;
    if (!testEmail) {
        mostrarError('Por favor introduce un email de prueba');
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.dataset.originalText = originalText;
    btn.innerHTML = '<span class="loading-spinner"></span> Enviando...';
    btn.disabled = true;
    
    ajaxRequest('test_smtp', {test_email: testEmail}, function(result) {
        mostrarExito(`${result.message}\n\nRevisa la bandeja de entrada de: ${testEmail}`);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, function(error) {
        mostrarError(error);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Checkbox de confirmación de reset
document.addEventListener('change', function(e) {
    if (e.target.id === 'confirmReset') {
        const btnReset = document.getElementById('btnReset');
        if (btnReset) btnReset.disabled = !e.target.checked;
    }
});

// Reset de base de datos
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