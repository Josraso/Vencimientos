<div class="tabs">
    <button class="tab active" onclick="showTab('clientes')">
        ?? Clientes (<?php echo $datos['estadisticas_generales']['total_clientes']; ?>)
    </button>
    <button class="tab" onclick="showTab('servicios')">
        ?? Servicios (<?php echo count($datos['tipos_servicios']); ?>)
    </button>
    <button class="tab" onclick="showTab('vencimientos')">
        ?? Todos los Servicios (<?php echo $datos['estadisticas_generales']['total_servicios']; ?>)
    </button>
    <button class="tab" onclick="showTab('albaranes')">
        ?? Albaranes (<?php echo $datos['estadisticas_generales']['total_albaranes']; ?>)
    </button>
    <button class="tab" onclick="showTab('config')">
        ?? Configuración
    </button>
</div>