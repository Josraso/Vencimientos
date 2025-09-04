// assets/js/global.js - Exponer todas las funciones necesarias globalmente
document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que todos los scripts estén cargados
    setTimeout(function() {
        // Verificar que las funciones existen antes de exponerlas
        const functionsToExpose = [
            // Servicios
            'eliminarTipoServicio',
            'editarTipoServicio',
            'verClientesServicio',
            'eliminarServicioCliente',
            'renovarServicio',
            'editarServicioCliente',
            'toggleServicioCliente',
            'generarAlbaran',
            'enviarNotificacionIndividual',
            'renovarMasivo',
            'enviarNotificacionesMasivas',
            'aplicarFiltros',
            'limpiarFiltros',
            'toggleSelectAll',
            'toggleServicioSeleccion',
            'renovarSeleccionados',
            'generarAlbaranSeleccionados',
            'enviarNotificacionesSeleccionadas',
            // Clientes
            'searchClientes',
            'clearSearch',
            'verCliente',
            'editarCliente',
            'asignarServicio',
            'eliminarClienteSeguro',
            // Albaranes
            'cargarAlbaranes',
            'aplicarFiltrosAlbaranes',
            'limpiarFiltrosAlbaranes',
            'toggleSelectAllAlbaranes',
            'toggleAlbaranSeleccion',
            'eliminarAlbaran',
            'eliminarAlbaranesMasivo',
            'cambiarEstadoAlbaran',
            'verAlbaranPDF',
            'descargarAlbaranPDF',
            // Modales
            'openModal',
            'closeModal',
            // Config
            'testSMTP',
            'resetDatabase',
            // Main
            'showTab',
            // Utils
            'mostrarError',
            'mostrarExito',
            'mostrarConfirmacion',
            'ajaxRequest'
        ];
        
        functionsToExpose.forEach(funcName => {
            if (typeof window[funcName] === 'undefined') {
                console.warn(`Función ${funcName} no está definida`);
            }
        });
        
        console.log('? Funciones globales cargadas correctamente');
    }, 100);
});