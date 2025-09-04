<div class="header">
    <div class="container">
        <h1>📊 Gestión de Servicios Pro</h1>
        <div class="stats">
            <div class="stat-item">
                <strong><?php echo $datos['estadisticas_generales']['total_clientes']; ?></strong>
                <span>Clientes</span>
            </div>
            <div class="stat-item">
                <strong><?php echo $datos['estadisticas_generales']['total_servicios']; ?></strong>
                <span>Servicios</span>
            </div>
            <div class="stat-item <?php echo $datos['estadisticas_generales']['servicios_urgentes'] > 0 ? 'urgente' : ''; ?>">
                <strong><?php echo $datos['estadisticas_generales']['proximos_vencimientos']; ?></strong>
                <span>Vencimientos</span>
            </div>
            <div class="stat-item">
                <strong>€<?php echo number_format($datos['estadisticas_generales']['ingresos_mensuales'], 0); ?></strong>
                <span>Mes</span>
            </div>
        </div>
    </div>
</div>