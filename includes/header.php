<div class="header">
    <div class="container">
        <div class="header-content">
            <div class="header-left">
                <h1>?? Gestión de Servicios Pro</h1>
            </div>
            <div class="header-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['total_clientes']; ?></div>
                    <div class="stat-label">Clientes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['total_servicios']; ?></div>
                    <div class="stat-label">Servicios</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" style="color: <?php echo $datos['estadisticas_generales']['servicios_urgentes'] > 0 ? '#fed7d7' : '#c6f6d5'; ?>">
                        <?php echo $datos['estadisticas_generales']['proximos_vencimientos']; ?>
                    </div>
                    <div class="stat-label">Vencimientos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">€<?php echo number_format($datos['estadisticas_generales']['ingresos_mensuales'], 0); ?></div>
                    <div class="stat-label">Ing./Mes</div>
                </div>
            </div>
        </div>
    </div>
</div>