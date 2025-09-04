// assets/js/modals.js - Gestión de modales

// Abrir modal
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Cerrar modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = 'auto';
    
    if (modalId === 'modalCliente') {
        resetFormCliente();
    } else if (modalId === 'modalTipoServicio') {
        resetFormTipoServicio();
    }
}

// Reset formulario cliente
function resetFormCliente() {
    document.getElementById('formCliente').reset();
    document.querySelector('#formCliente input[name="action"]').value = 'add_cliente';
    document.getElementById('modalClienteTitle').innerHTML = '&#128101; Añadir Cliente';
    document.getElementById('btnSubmitCliente').innerHTML = '&#128190; Guardar Cliente';
    document.getElementById('clienteId').value = '';
}

// Reset formulario tipo servicio
function resetFormTipoServicio() {
    document.getElementById('formTipoServicio').reset();
    document.querySelector('#formTipoServicio input[name="action"]').value = 'add_servicio_tipo';
    document.getElementById('modalTipoServicioTitle').innerHTML = '&#9881; Añadir Tipo de Servicio';
    document.getElementById('btnSubmitTipoServicio').innerHTML = '&#128190; Guardar Servicio';
    document.getElementById('tipoServicioId').value = '';
}

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