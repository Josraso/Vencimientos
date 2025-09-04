<?php if ($mensaje): ?>
    <div class="alert success">✅ <?php echo $mensaje; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error">❌ <?php echo $error; ?></div>
<?php endif; ?>

<?php if ($datos['estadisticas_generales']['servicios_urgentes'] > 0): ?>
    <div class="alert warning">
        ⚠️ <strong>Atención:</strong> Tienes <?php echo $datos['estadisticas_generales']['servicios_urgentes']; ?> servicios que vencen en menos de 2 días.
        <button class="btn btn-small btn-warning" onclick="showTab('vencimientos')" style="margin-left: 10px;">Ver Urgentes</button>
    </div>
<?php endif; ?>