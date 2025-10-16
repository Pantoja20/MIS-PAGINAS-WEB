<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Cajero') {
    header("Location: ../login.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// Procesar venta cuando se confirme
if (isset($_POST['confirmar_venta'])) {
    try {
        $db->beginTransaction();
        
        $metodo_pago = $_POST['metodo_pago'];
        $total = $_POST['total_venta'];
        $productos_venta = json_decode($_POST['productos_venta'], true);
        
        // Insertar venta
        $query_venta = "INSERT INTO ventas (id_usuario, total, metodo_pago) VALUES (:user_id, :total, :metodo_pago)";
        $stmt_venta = $db->prepare($query_venta);
        $stmt_venta->bindParam(':user_id', $_SESSION['user_id']);
        $stmt_venta->bindParam(':total', $total);
        $stmt_venta->bindParam(':metodo_pago', $metodo_pago);
        $stmt_venta->execute();
        
        $venta_id = $db->lastInsertId();
        
        // Insertar detalles de venta y actualizar stock
        foreach ($productos_venta as $producto) {
            $query_detalle = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, subtotal) 
                             VALUES (:venta_id, :producto_id, :cantidad, :subtotal)";
            $stmt_detalle = $db->prepare($query_detalle);
            $stmt_detalle->bindParam(':venta_id', $venta_id);
            $stmt_detalle->bindParam(':producto_id', $producto['id']);
            $stmt_detalle->bindParam(':cantidad', $producto['cantidad']);
            $stmt_detalle->bindParam(':subtotal', $producto['subtotal']);
            $stmt_detalle->execute();
            
            // Actualizar stock
            $query_update_stock = "UPDATE productos SET cantidad = cantidad - :cantidad WHERE id = :producto_id";
            $stmt_update = $db->prepare($query_update_stock);
            $stmt_update->bindParam(':cantidad', $producto['cantidad']);
            $stmt_update->bindParam(':producto_id', $producto['id']);
            $stmt_update->execute();
        }
        
        $db->commit();
        
        // Redirigir a ventas.php con mensaje de éxito
        header("Location: ventas.php?success=Venta procesada exitosamente&venta_id=" . $venta_id);
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al procesar la venta: " . $e->getMessage();
    }
}

// Obtener datos de la venta temporal desde GET
if (isset($_GET['metodo_pago']) && isset($_GET['total']) && isset($_GET['productos'])) {
    $metodo_pago = $_GET['metodo_pago'];
    $total = $_GET['total'];
    $productos_venta = json_decode($_GET['productos'], true);
    
    // Validar que los datos sean correctos
    if (empty($productos_venta)) {
        header("Location: ventas.php?error=Datos de venta inválidos");
        exit();
    }
} else {
    header("Location: ventas.php");
    exit();
}

// Obtener información completa de los productos para mostrar en la boleta
$detalles = [];
foreach ($productos_venta as $producto) {
    $query_producto = "SELECT nombre, precio FROM productos WHERE id = :id";
    $stmt_producto = $db->prepare($query_producto);
    $stmt_producto->bindParam(':id', $producto['id']);
    $stmt_producto->execute();
    $producto_info = $stmt_producto->fetch(PDO::FETCH_ASSOC);
    
    if ($producto_info) {
        $detalles[] = [
            'producto_nombre' => $producto_info['nombre'],
            'precio_unitario' => $producto_info['precio'],
            'cantidad' => $producto['cantidad'],
            'subtotal' => $producto['subtotal']
        ];
    }
}

// Obtener información del cajero
$query_cajero = "SELECT nombre_completo FROM usuarios WHERE id = :user_id";
$stmt_cajero = $db->prepare($query_cajero);
$stmt_cajero->bindParam(':user_id', $_SESSION['user_id']);
$stmt_cajero->execute();
$cajero = $stmt_cajero->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher de Venta - Minimarket Pollon</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Variables de diseño moderno */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #10b981;
            --success-dark: #059669;
            --light-bg: #f8f9fa;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            --radius: 16px;
        }

        /* Notificación moderna */
        .notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease-out;
        }

        .notification-card {
            width: 90%;
            max-width: 450px;
            max-height: 85vh;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Encabezado de notificación - VERSIÓN PANTALLA */
        .notification-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4cc9f0, #7209b7, #4361ee);
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            backdrop-filter: blur(10px);
        }

        .notification-title {
            flex: 1;
        }

        .notification-title h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .notification-subtitle {
            margin: 0.3rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .close-notification {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .close-notification:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        /* Contenido de la notificación */
        .notification-content {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        /* Información rápida - VERSIÓN PANTALLA */
        .quick-info {
            padding: 1.5rem;
            background: var(--light-bg);
            border-bottom: 1px solid var(--border);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.8rem;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Items compactos - VERSIÓN PANTALLA */
        .items-container {
            padding: 1.2rem 1.5rem;
            max-height: 220px;
            overflow-y: auto;
        }

        .items-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            padding: 0.6rem 0;
            border-bottom: 2px solid var(--border);
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
        }

        .item-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .item-price, .item-quantity, .item-subtotal {
            text-align: right;
            font-weight: 600;
        }

        /* Totales destacados - VERSIÓN PANTALLA */
        .totals-section {
            padding: 1.5rem;
            background: linear-gradient(to right, #f8f9fa, #ffffff);
            border-top: 2px dashed var(--border);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.95rem;
        }

        .total-final {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary);
            border-top: 2px solid var(--border);
            padding-top: 1rem;
            margin-top: 0.5rem;
        }

        /* Footer minimalista - VERSIÓN PANTALLA */
        .notification-footer {
            padding: 1.2rem 1.5rem;
            text-align: center;
            background: var(--light-bg);
            border-top: 1px solid var(--border);
        }

        .thank-you {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.6rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .footer-note {
            font-size: 0.75rem;
            color: var(--text-light);
            line-height: 1.4;
        }

        /* Botones de acción modernos */
        .action-buttons {
            padding: 1.5rem;
            display: flex;
            gap: 12px;
            background: white;
            border-top: 1px solid var(--border);
        }

        .action-btn {
            flex: 1;
            border: none;
            padding: 14px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-print {
            background: var(--primary);
            color: white;
        }

        .btn-print:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-confirm {
            background: var(--success);
            color: white;
        }

        .btn-confirm:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* Badge de método de pago - VERSIÓN PANTALLA */
        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .payment-badge.efectivo {
            background: #d4edda;
            color: #155724;
        }

        .payment-badge.yape {
            background: #e6f7ff;
            color: var(--primary);
        }

        .payment-badge.plin {
            background: #fff0e6;
            color: #ff8c00;
        }

        /* Scrollbar personalizado */
        .items-container::-webkit-scrollbar {
            width: 5px;
        }

        .items-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .items-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .items-container::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* LÍNEA DE CORTE - Solo para pantalla */
        .voucher-cut {
            border-top: 1px dashed #ccc;
            margin: 15px 0;
            text-align: center;
            position: relative;
        }

        .voucher-cut::after {
            content: "✂️";
            font-size: 8pt;
            background: white;
            padding: 0 10px;
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
        }

        /* ESTILOS DE IMPRESIÓN - VOUCHER MODERNO */
        @media print {
            /* Reset completo para impresión */
            * {
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            body {
                background: white !important;
                color: black !important;
                font-family: 'Courier New', monospace !important;
                font-size: 9pt !important;
                line-height: 1 !important;
                width: 80mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
            }
            
            /* Ocultar todo excepto el voucher */
            body * {
                visibility: hidden;
            }
            
            .notification-overlay,
            .notification-card,
            .notification-card * {
                visibility: visible !important;
            }
            
            .notification-overlay {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: auto !important;
                background: white !important;
                display: block !important;
                z-index: 9999 !important;
                overflow: visible !important;
                animation: none !important;
            }
            
            .notification-card {
                width: 80mm !important;
                max-width: 80mm !important;
                max-height: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-inside: avoid;
                break-inside: avoid;
                animation: none !important;
            }
            
            /* Ocultar elementos no necesarios para impresión */
            .action-buttons,
            .close-notification,
            .notification-icon {
                display: none !important;
            }
            
            /* VOUCHER HEADER - Estilo moderno para impresión */
            .notification-header {
                background: linear-gradient(135deg, #000, #333) !important;
                color: white !important;
                padding: 8px 10px !important;
                border-bottom: 2px dashed #fff !important;
                text-align: center !important;
                display: block !important;
                border-radius: 0 !important;
            }
            
            .notification-title h2 {
                font-size: 12pt !important;
                margin: 0 !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
                letter-spacing: 1px !important;
            }
            
            .notification-subtitle {
                font-size: 8pt !important;
                opacity: 0.9 !important;
                margin: 2px 0 0 0 !important;
                font-weight: normal !important;
            }
            
            /* INFORMACIÓN COMPACTA para impresión */
            .quick-info {
                background: #f8f8f8 !important;
                border: none !important;
                border-bottom: 1px dashed #ccc !important;
                padding: 6px 8px !important;
                margin: 0 !important;
            }
            
            .info-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 4px !important;
            }
            
            .info-item {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            
            .info-label {
                font-size: 6pt !important;
                color: #666 !important;
                margin: 0 !important;
                font-weight: bold !important;
            }
            
            .info-value {
                font-size: 7pt !important;
                color: #000 !important;
                font-weight: bold !important;
                text-align: right !important;
            }
            
            .payment-badge {
                background: #000 !important;
                color: white !important;
                border: none !important;
                padding: 1px 4px !important;
                font-size: 6pt !important;
                border-radius: 3px !important;
            }
            
            /* TABLA DE PRODUCTOS ULTRA COMPACTA para impresión */
            .items-container {
                padding: 4px 6px !important;
                max-height: none !important;
                overflow: visible !important;
                margin: 0 !important;
                border-bottom: 1px dashed #ccc !important;
            }
            
            .items-header {
                display: grid !important;
                grid-template-columns: 3fr 1fr 1fr 1fr !important;
                padding: 3px 0 !important;
                border-bottom: 1px solid #000 !important;
                font-weight: bold !important;
                font-size: 6pt !important;
                color: #000 !important;
                text-transform: uppercase !important;
            }
            
            .item-row {
                display: grid !important;
                grid-template-columns: 3fr 1fr 1fr 1fr !important;
                padding: 2px 0 !important;
                border-bottom: 1px dotted #eee !important;
                font-size: 7pt !important;
            }
            
            .item-name {
                font-weight: normal !important;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .item-price, .item-quantity, .item-subtotal {
                text-align: right !important;
                font-weight: bold !important;
            }
            
            /* TOTALES DESTACADOS para impresión */
            .totals-section {
                background: white !important;
                border: none !important;
                border-bottom: 1px dashed #ccc !important;
                padding: 6px 8px !important;
                margin: 0 !important;
            }
            
            .total-row {
                display: flex !important;
                justify-content: space-between !important;
                padding: 1px 0 !important;
                font-size: 7pt !important;
            }
            
            .total-final {
                font-size: 9pt !important;
                font-weight: bold !important;
                border-top: 2px solid #000 !important;
                padding-top: 3px !important;
                margin-top: 2px !important;
                color: #000 !important;
            }
            
            /* LÍNEA DE CORTE para impresión */
            .voucher-cut {
                border-top: 1px dashed #000 !important;
                margin: 5px 0 !important;
                text-align: center !important;
            }
            
            .voucher-cut::after {
                content: "✂️" !important;
                font-size: 6pt !important;
                display: block !important;
                margin-top: -8px !important;
                background: transparent !important;
                padding: 0 !important;
                position: static !important;
                transform: none !important;
            }
            
            /* PIE DE VOUCHER para impresión */
            .notification-footer {
                background: #f8f8f8 !important;
                border: none !important;
                padding: 6px 8px !important;
                margin: 0 !important;
                text-align: center !important;
            }
            
            .thank-you {
                color: #000 !important;
                font-size: 8pt !important;
                margin-bottom: 3px !important;
                font-weight: bold !important;
            }
            
            .thank-you i {
                display: none !important;
            }
            
            .footer-note {
                font-size: 5pt !important;
                color: #666 !important;
                line-height: 1.1 !important;
            }
            
            /* AJUSTES DE MÁRGENES PARA VOUCHER */
            @page {
                margin: 2mm;
                size: auto;
            }
            
            /* EVITAR SALTO DE PÁGINA */
            .notification-card {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .notification-card {
                width: 95%;
                max-width: 95%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .items-header,
            .item-row {
                grid-template-columns: 2fr 1fr 1fr 1fr;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Notificación moderna -->
    <div class="notification-overlay">
        <div class="notification-card">
            <!-- Encabezado - Versión pantalla -->
            <div class="notification-header">
                <div class="notification-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="notification-title">
                    <h2>Voucher de Venta</h2>
                    <p class="notification-subtitle">MINIMARKET POLLON</p>
                </div>
                <button class="close-notification" onclick="cerrarNotificacion()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Contenido -->
            <div class="notification-content">
                <!-- Información rápida - Versión pantalla -->
                <div class="quick-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Fecha</span>
                            <span class="info-value"><?php echo date('d/m/Y'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Hora</span>
                            <?php
                            date_default_timezone_set('America/Lima');
                            ?>
                            <span class="info-value"><?php echo date('H:i:s'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Cajero</span>
                            <span class="info-value"><?php echo htmlspecialchars($cajero['nombre_completo']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Método de Pago</span>
                            <span class="payment-badge <?php echo strtolower($metodo_pago); ?>">
                                <i class="fas fa-<?php 
                                    echo $metodo_pago == 'Efectivo' ? 'money-bill-wave' : 
                                         ($metodo_pago == 'Yape' ? 'mobile-alt' : 
                                         ($metodo_pago == 'Plin' ? 'mobile-alt' : 'credit-card')); 
                                ?>"></i>
                                <?php echo $metodo_pago; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Items de la venta - Versión pantalla -->
                <div class="items-container">
                    <div class="items-header">
                        <span>Producto</span>
                        <span class="item-price">P.Unit</span>
                        <span class="item-quantity">Cant</span>
                        <span class="item-subtotal">Total</span>
                    </div>
                    
                    <?php foreach ($detalles as $item): ?>
                    <div class="item-row">
                        <span class="item-name"><?php echo htmlspecialchars($item['producto_nombre']); ?></span>
                        <span class="item-price">S/ <?php echo number_format($item['precio_unitario'], 2); ?></span>
                        <span class="item-quantity"><?php echo $item['cantidad']; ?></span>
                        <span class="item-subtotal">S/ <?php echo number_format($item['subtotal'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Totales - Versión pantalla -->
                <div class="totals-section">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>S/ <?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>IGV (18%):</span>
                        <span>S/ <?php echo number_format($total * 0.18, 2); ?></span>
                    </div>
                    <div class="total-row total-final">
                        <span>TOTAL:</span>
                        <span>S/ <?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <!-- Línea de corte -->
                <div class="voucher-cut"></div>

                <!-- Footer - Versión pantalla -->
                <div class="notification-footer">
                    <div class="thank-you">
                        <i class="fas fa-check-circle"></i>
                        ¡Gracias por su compra!
                    </div>
                    <div class="footer-note">
                        Conserve este voucher para cualquier reclamo<br>
                        RUC: 20123456789 • Tel: (01) 123-4567<br>
                        Av. Principal 123, Lima - Perú
                    </div>
                </div>
            </div>

            <!-- Botones de acción (solo visibles en pantalla) -->
            <div class="action-buttons">
                <button class="action-btn btn-print" onclick="imprimirVoucher()">
                    <i class="fas fa-print"></i>
                    Imprimir Voucher
                </button>
                
                <!-- Form para confirmar la venta -->
                <form method="POST" action="" id="confirmarForm" style="display: contents;">
                    <input type="hidden" name="metodo_pago" value="<?php echo htmlspecialchars($metodo_pago); ?>">
                    <input type="hidden" name="total_venta" value="<?php echo htmlspecialchars($total); ?>">
                    <input type="hidden" name="productos_venta" value='<?php echo htmlspecialchars(json_encode($productos_venta)); ?>'>
                    <button type="submit" name="confirmar_venta" class="action-btn btn-confirm">
                        <i class="fas fa-check"></i>
                        Confirmar Venta
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function cerrarNotificacion() {
            const card = document.querySelector('.notification-card');
            card.style.animation = 'slideUp 0.3s reverse forwards';
            setTimeout(() => {
                window.location.href = 'ventas.php';
            }, 250);
        }

        function imprimirVoucher() {
            if (event && event.target) {
                event.target.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    event.target.style.transform = '';
                }, 150);
            }
            
            window.print();
        }

        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarNotificacion();
            }
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                imprimirVoucher();
            }
        });

        // Cerrar haciendo click fuera
        document.querySelector('.notification-overlay').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarNotificacion();
            }
        });

        // Auto-imprimir si se solicita
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoPrint') === '1') {
            setTimeout(imprimirVoucher, 1000);
        }
    </script>
</body>
</html>