<?php
// generar_pdf.php - Generador de PDFs mejorado
require_once 'config.php';

if (!isset($_GET['albaran_id'])) {
    die('ID de albarán no especificado');
}

$albaran_id = $_GET['albaran_id'];

// Obtener datos del albarán
$stmt = $pdo->prepare("
    SELECT a.*, c.nombre, c.apellidos, c.dni, c.empresa, c.email, c.telefono, 
           c.direccion, c.ciudad, c.codigo_postal
    FROM albaranes a 
    JOIN clientes c ON a.cliente_id = c.id 
    WHERE a.id = ?
");
$stmt->execute([$albaran_id]);
$albaran = $stmt->fetch();

if (!$albaran) {
    die('Albarán no encontrado');
}

// Obtener líneas del albarán
$stmt = $pdo->prepare("
    SELECT al.*, ts.nombre as servicio_nombre, ts.descripcion as servicio_descripcion
    FROM albaran_lineas al
    LEFT JOIN servicios_cliente sc ON al.servicio_cliente_id = sc.id
    LEFT JOIN tipos_servicios ts ON sc.tipo_servicio_id = ts.id
    WHERE al.albaran_id = ?
    ORDER BY al.id
");
$stmt->execute([$albaran_id]);
$lineas = $stmt->fetchAll();

// Datos de la empresa
$empresa = [
    'nombre' => getConfig('empresa_nombre') ?: 'Tu Empresa S.L.',
    'direccion' => getConfig('empresa_direccion') ?: 'Calle Ejemplo, 123',
    'ciudad' => getConfig('empresa_ciudad') ?: 'Tu Ciudad',
    'codigo_postal' => getConfig('empresa_codigo_postal') ?: '12345',
    'telefono' => getConfig('empresa_telefono') ?: '666 123 456',
    'email' => getConfig('empresa_email') ?: 'info@tuempresa.com',
    'cif' => getConfig('empresa_cif') ?: 'B12345678',
    'web' => getConfig('empresa_web') ?: 'www.tuempresa.com'
];

// Generar HTML del PDF
$html = generarHTMLAlbaran($albaran, $lineas, $empresa);

// Headers para mostrar como HTML (se puede imprimir como PDF)
header('Content-Type: text/html; charset=UTF-8');
echo $html;

function generarHTMLAlbaran($albaran, $lineas, $empresa) {
    $fecha_albaran = date('d/m/Y', strtotime($albaran['fecha_albaran']));
    
    $html = "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Albarán {$albaran['numero_albaran']}</title>
        <style>
            @page { size: A4 portrait; margin: 15mm; }
            
            body { 
                font-family: 'Segoe UI', Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
                font-size: 14px; 
                color: #333;
                background: white;
            }
            
            .header-empresa {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                border-radius: 10px 10px 0 0;
                margin: -20px -20px 20px -20px;
            }
            
            .header-empresa h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
            }
            
            .header-empresa p {
                margin: 3px 0;
                font-size: 12px;
                opacity: 0.95;
            }
            
            .albaran-numero {
                background: #f8f9fa;
                border-left: 4px solid #667eea;
                padding: 15px;
                margin: 20px 0;
                border-radius: 5px;
            }
            
            .albaran-numero h2 {
                margin: 0;
                color: #667eea;
                font-size: 20px;
            }
            
            .cliente-box {
                background: #e6fffa;
                border: 1px solid #48bb78;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            
            .cliente-box h3 {
                margin-top: 0;
                color: #48bb78;
            }
            
            .tabla-servicios {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .tabla-servicios th {
                background: #667eea;
                color: white;
                padding: 12px;
                text-align: left;
                font-weight: 600;
            }
            
            .tabla-servicios td {
                padding: 12px;
                border-bottom: 1px solid #e2e8f0;
            }
            
            .tabla-servicios tr:nth-child(even) {
                background: #f8f9fa;
            }
            
            .tabla-servicios tr:hover {
                background: #e6fffa;
            }
            
            .total-box {
                background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
                color: white;
                padding: 20px;
                border-radius: 8px;
                text-align: right;
                margin: 20px 0;
            }
            
            .total-box h3 {
                margin: 0;
                font-size: 24px;
            }
            
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 2px solid #e2e8f0;
                text-align: center;
                color: #718096;
                font-size: 11px;
            }
            
            @media print {
                body { margin: 0; padding: 10px; }
                .header-empresa { margin: -10px -10px 20px -10px; }
                .tabla-servicios { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class='header-empresa'>
            <h1>{$empresa['nombre']}</h1>
            <p>CIF: {$empresa['cif']} | {$empresa['direccion']}, {$empresa['codigo_postal']} {$empresa['ciudad']}</p>
            <p>&#128241; {$empresa['telefono']} | &#128231; {$empresa['email']}</p>
            " . ($empresa['web'] ? "<p>&#127760; {$empresa['web']}</p>" : "") . "
        </div>
        
        <div class='albaran-numero'>
            <h2>ALBARÁN {$albaran['numero_albaran']}</h2>
            <p><strong>Fecha:</strong> {$fecha_albaran} | <strong>Estado:</strong> " . ucfirst($albaran['estado']) . "</p>
        </div>
        
        <div class='cliente-box'>
            <h3>&#128101; DATOS DEL CLIENTE</h3>
            <p><strong>{$albaran['nombre']} {$albaran['apellidos']}</strong></p>
            " . ($albaran['dni'] ? "<p><strong>DNI:</strong> {$albaran['dni']}</p>" : "") . "
            " . ($albaran['empresa'] ? "<p><strong>Empresa:</strong> {$albaran['empresa']}</p>" : "") . "
            <p><strong>Email:</strong> {$albaran['email']} | <strong>Teléfono:</strong> {$albaran['telefono']}</p>
            " . ($albaran['direccion'] ? "<p><strong>Dirección:</strong> {$albaran['direccion']}, {$albaran['codigo_postal']} {$albaran['ciudad']}</p>" : "") . "
        </div>
        
        <table class='tabla-servicios'>
            <thead>
                <tr>
                    <th style='width: 50%;'>Descripción</th>
                    <th style='width: 15%; text-align: center;'>Cantidad</th>
                    <th style='width: 17.5%; text-align: right;'>Precio Unit.</th>
                    <th style='width: 17.5%; text-align: right;'>Total</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($lineas as $linea) {
        $html .= "
            <tr>
                <td>{$linea['descripcion']}</td>
                <td style='text-align: center;'>{$linea['cantidad']}</td>
                <td style='text-align: right;'>€" . number_format($linea['precio_unitario'], 2) . "</td>
                <td style='text-align: right; font-weight: bold;'>€" . number_format($linea['total_linea'], 2) . "</td>
            </tr>";
    }
    
    $html .= "
            </tbody>
        </table>
        
        <div class='total-box'>
            <h3>TOTAL: €" . number_format($albaran['total'], 2) . "</h3>
        </div>
        
        " . ($albaran['observaciones'] ? "
        <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;'>
            <h4 style='margin-top: 0; color: #667eea;'>Observaciones:</h4>
            <p>{$albaran['observaciones']}</p>
        </div>" : "") . "
        
        <div class='footer'>
            <p>Documento generado automáticamente el " . date('d/m/Y H:i') . "</p>
            <p>{$empresa['nombre']} - {$empresa['cif']}</p>
        </div>
        
        <script>
            window.onload = function() {
                if (window.location.search.includes('download=1')) {
                    window.print();
                }
            }
        </script>
    </body>
    </html>";
    
    return $html;
}
?>