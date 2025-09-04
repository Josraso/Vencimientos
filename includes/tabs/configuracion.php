<div id="config" class="tab-content">
    <div class="tab-pane">
        <h2>⚙️ Configuración del Sistema</h2>
        
        <form method="POST" style="max-width: 900px;">
            <input type="hidden" name="action" value="update_config">
            
            <h3 style="margin-bottom: 20px; color: #667eea;">📧 Configuración de Notificaciones</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Días de aviso antes del vencimiento:</label>
                    <input type="number" name="dias_aviso" value="<?php echo getConfig('dias_aviso'); ?>" required min="1" max="30">
                    <small style="color: #718096;">Número de días antes del vencimiento para recibir avisos</small>
                </div>
                <div class="form-group">
                    <label>Email principal para avisos:</label>
                    <input type="email" name="email_admin" value="<?php echo getConfig('email_admin'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Emails adicionales (separados por comas):</label>
                <input type="text" name="emails_copia" value="<?php echo getConfig('emails_copia'); ?>" 
                       placeholder="email1@ejemplo.com, email2@ejemplo.com">
                <small style="color: #718096;">Emails adicionales que recibirán copia de los avisos</small>
            </div>
            
            <h3 style="margin: 40px 0 20px 0; color: #667eea;">📮 Configuración SMTP</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Servidor SMTP:</label>
                    <input type="text" name="smtp_host" value="<?php echo getConfig('smtp_host'); ?>" 
                           placeholder="smtp.gmail.com">
                </div>
                <div class="form-group">
                    <label>Puerto SMTP:</label>
                    <input type="number" name="smtp_port" value="<?php echo getConfig('smtp_port'); ?>" 
                           placeholder="587">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Usuario SMTP:</label>
                    <input type="text" name="smtp_user" value="<?php echo getConfig('smtp_user'); ?>" 
                           placeholder="tu_email@gmail.com">
                </div>
                <div class="form-group">
                    <label>Contraseña SMTP:</label>
                    <input type="password" name="smtp_pass" value="<?php echo getConfig('smtp_pass'); ?>" 
                           placeholder="tu_contraseña_app">
                </div>
            </div>
            
            <!-- Test de configuración SMTP -->
            <div class="test-config">
                <h4 style="color: #38b2ac;">🧪 Test de Configuración SMTP</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email de prueba:</label>
                        <input type="email" id="testEmail" value="<?php echo getConfig('email_admin'); ?>" 
                               placeholder="email@ejemplo.com">
                    </div>
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="button" class="btn btn-info" onclick="testSMTP()" style="width: 100%;">
                            📤 Enviar Email de Prueba
                        </button>
                    </div>
                </div>
            </div>
            
            <h3 style="margin: 40px 0 20px 0; color: #667eea;">🏢 Datos de la Empresa</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre de la empresa:</label>
                    <input type="text" name="empresa_nombre" value="<?php echo getConfig('empresa_nombre'); ?>" required>
                </div>
                <div class="form-group">
                    <label>CIF/NIF:</label>
                    <input type="text" name="empresa_cif" value="<?php echo getConfig('empresa_cif'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Dirección:</label>
                <input type="text" name="empresa_direccion" value="<?php echo getConfig('empresa_direccion'); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Ciudad:</label>
                    <input type="text" name="empresa_ciudad" value="<?php echo getConfig('empresa_ciudad'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Código Postal:</label>
                    <input type="text" name="empresa_codigo_postal" value="<?php echo getConfig('empresa_codigo_postal'); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" name="empresa_telefono" value="<?php echo getConfig('empresa_telefono'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email de la empresa:</label>
                    <input type="email" name="empresa_email" value="<?php echo getConfig('empresa_email'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Sitio web:</label>
                <input type="text" name="empresa_web" value="<?php echo getConfig('empresa_web'); ?>" 
                       placeholder="www.tuempresa.com">
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">💾 Guardar Configuración</button>
        </form>
        
        <!-- RESET DE BASE DE DATOS -->
        <div style="margin-top: 50px; padding-top: 30px; border-top: 2px solid #f56565;">
            <h3 style="color: #f56565;">⚠️ Zona Peligrosa - Reset de Base de Datos</h3>
            <div style="background: #fff5f5; border: 1px solid #f56565; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p style="color: #721c24; margin-bottom: 15px;"><strong>⚠️ ADVERTENCIA:</strong> Selecciona qué datos quieres eliminar:</p>
                
                <div style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="resetClientes">
                            <span>👥 Clientes</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="resetServicios">
                            <span>⚙️ Tipos de Servicios</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="resetServiciosCliente">
                            <span>📋 Servicios de Clientes</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="resetAlbaranes">
                            <span>📄 Albaranes</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="resetNotificaciones">
                            <span>📧 Notificaciones</span>
                        </label>
                    </div>
                    
                    <label style="display: flex; align-items: center; gap: 10px; margin-top: 20px; background: #fff5f5; padding: 10px; border-radius: 5px;">
                        <input type="checkbox" id="confirmReset">
                        <span>Confirmo que quiero eliminar los datos seleccionados (SIN datos de ejemplo)</span>
                    </label>
                </div>

                <button type="button" class="btn btn-danger" onclick="resetDatabase()" id="btnReset" disabled>
                    🗑️ Eliminar Datos Seleccionados
                </button>
            </div>
        </div>
        
        <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e2e8f0;">
            <h3>📊 Estadísticas del Sistema</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['total_clientes'] ?? 0; ?></div>
                    <div class="stat-label">Clientes Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['total_servicios'] ?? 0; ?></div>
                    <div class="stat-label">Servicios Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['proximos_vencimientos'] ?? 0; ?></div>
                    <div class="stat-label">Próximos Vencimientos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">€<?php echo number_format($datos['estadisticas_generales']['ingresos_mensuales'] ?? 0, 2); ?></div>
                    <div class="stat-label">Ingresos Mensuales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $datos['estadisticas_generales']['servicios_urgentes'] ?? 0; ?></div>
                    <div class="stat-label">Servicios Urgentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">€<?php echo number_format($datos['estadisticas_generales']['facturacion_pendiente'] ?? 0, 2); ?></div>
                    <div class="stat-label">Facturación Pendiente</div>
                </div>
            </div>
        </div>
    </div>
</div>