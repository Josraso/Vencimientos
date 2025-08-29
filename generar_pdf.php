<?php
// generar_pdf.php - Generador de PDFs para albaranes
require_once 'config.php';

if (!isset($_GET['albaran_id'])) {
    die('ID de albarán no especificado');
}

$albaran_id = $_GET['albaran_id'];

// Obtener datos del albarán
$stmt = $pdo->prepare("
    SELECT a.*, c.nombre, c.apellidos, c.empresa, c.email, c.telefono, c.direccion, c.ciudad, c.codigo_postal
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

// Si se solicita descarga
$download = isset($_GET['download']) && $_GET['download'] == '1';
$filename = "Albaran_{$albaran['numero_albaran']}.pdf";

// Headers para PDF
if ($download) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Type: text/html; charset=UTF-8');
}

// Por simplicidad, mostrar HTML (en un entorno real usarías una librería como TCPDF o DomPDF)
echo $html;

function generarHTMLAlbaran($albaran, $lineas, $empresa) {
    $fecha_albaran = date('d/m/Y', strtotime($albaran['fecha_albaran']));
    
    $html = "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Albarán {$albaran['numero_albaran']}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; font-size: 12px; }
            .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
            .empresa { width: 45%; }
            .albaran-info { width: 45%; text-align: right; }
            .cliente { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
            .lineas { margin: 20px 0; }
            .lineas table { width: 100%; border-collapse: collapse; }
            .lineas th, .lineas td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .lineas th { background: #f8f9fa; font-weight: bold; }
            .total { text-align: right; margin-top: 20px; font-size: 14px; }
            .footer { margin-top: 40px; text-align: center; color: #666; font-size: 10px; }
            @media print {
                body { margin: 0; }
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='empresa'>
                <h2>{$empresa['nombre']}</h2>
                <p>{$empresa['direccion']}<br>
                {$empresa['codigo_postal']} {$empresa['ciudad']}<br>
                <strong>CIF:</strong> {$empresa['cif']}<br>
                <strong>Tel:</strong> {$empresa['telefono']}<br>
                <strong>Email:</strong> {$empresa['email']}</p>
            </div>
            <div class='albaran-info'>
                <h2>ALBARÁN</h2>
                <p><strong>Número:</strong> {$albaran['numero_albaran']}<br>
                <strong>Fecha:</strong> {$fecha_albaran}<br>
                <strong>Estado:</strong> " . ucfirst($albaran['estado']) . "</p>
            </div>
        </div>
        
        <div class='cliente'>
            <h3>DATOS DEL CLIENTE</h3>
            <p><strong>{$albaran['nombre']} {$albaran['apellidos']}</strong></p>
            " . ($albaran['empresa'] ? "<p><strong>Empresa:</strong> {$albaran['empresa']}</p>" : "") . "
            <p><strong>Email:</strong> {$albaran['email']}<br>
            <strong>Teléfono:</strong> {$albaran['telefono']}</p>
            " . ($albaran['direccion'] ? "<p><strong>Dirección:</strong> {$albaran['direccion']}<br>{$albaran['codigo_postal']} {$albaran['ciudad']}</p>" : "") . "
        </div>
        
        <div class='lineas'>
            <table>
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th style='width: 80px;'>Cantidad</th>
                        <th style='width: 100px;'>Precio Unit.</th>
                        <th style='width: 100px;'>Total</th>
                    </tr>
                </thead>
                <tbody>";
    
    foreach ($lineas as $linea) {
        $html .= "
                    <tr>
                        <td>{$linea['descripcion']}</td>
                        <td style='text-align: center;'>{$linea['cantidad']}</td>
                        <td style='text-align: right;'>€" . number_format($linea['precio_unitario'], 2) . "</td>
                        <td style='text-align: right;'>€" . number_format($linea['total_linea'], 2) . "</td>
                    </tr>";
    }
    
    $html .= "
                </tbody>
            </table>
        </div>
        
        <div class='total'>
            <h3>TOTAL: €" . number_format($albaran['total'], 2) . "</h3>
        </div>
        
        " . ($albaran['observaciones'] ? "
        <div style='margin-top: 30px;'>
            <h4>Observaciones:</h4>
            <p>{$albaran['observaciones']}</p>
        </div>" : "") . "
        
        <div class='footer'>
            <p>Documento generado automáticamente el " . date('d/m/Y H:i') . "</p>
            " . ($empresa['web'] ? "<p>{$empresa['web']}</p>" : "") . "
        </div>
        
        <script>
            // Auto-imprimir si es descarga
            if (window.location.search.includes('download=1')) {
                window.onload = function() {
                    setTimeout(function() { window.print(); }, 500);
                }
            }
        </script>
    </body>
    </html>";
    
    return $html;
}
?>