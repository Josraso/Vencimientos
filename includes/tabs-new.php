<div class="tabs">
    <button class="tab <?php echo $datos['tab_activa'] === 'vencimientos' ? 'active' : ''; ?>" data-tab="vencimientos">
        ?? Vencimientos (<?php echo $datos['estadisticas_generales']['total_servicios']; ?>)
    </button>
    <button class="tab <?php echo $datos['tab_activa'] === 'clientes' ? 'active' : ''; ?>" data-tab="clientes">
        ?? Clientes (<?php echo $datos['estadisticas_generales']['total_clientes']; ?>)
    </button>
    <button class="tab <?php echo $datos['tab_activa'] === 'albaranes' ? 'active' : ''; ?>" data-tab="albaranes">
        ?? Albaranes (<?php echo $datos['estadisticas_generales']['total_albaranes']; ?>)
    </button>
    <button class="tab <?php echo $datos['tab_activa'] === 'config' ? 'active' : ''; ?>" data-tab="config">
        ?? Configuración
    </button>
</div>