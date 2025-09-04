<div class="tabs">
    <button class="tab active" onclick="showTab('clientes')">
        &#128101; Clientes (<?php echo $datos['estadisticas_generales']['total_clientes']; ?>)
    </button>
    <button class="tab" onclick="showTab('servicios')">
        &#9881; Servicios (<?php echo count($datos['tipos_servicios']); ?>)
    </button>
    <button class="tab" onclick="showTab('vencimientos')">
        &#128197; Todos los Servicios (<?php echo $datos['estadisticas_generales']['total_servicios']; ?>)
    </button>
    <button class="tab" onclick="showTab('albaranes')">
        &#128196; Albaranes (<?php echo $datos['estadisticas_generales']['total_albaranes']; ?>)
    </button>
    <button class="tab" onclick="showTab('config')">
        &#9881; Configuración
    </button>
</div>