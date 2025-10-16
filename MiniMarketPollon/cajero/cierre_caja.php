<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Cajero') {
    header("Location: ../login.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// FECHA ACTUAL DEL SISTEMA - se actualiza automáticamente
$fecha_actual = date('Y-m-d');
$fecha_consulta = isset($_GET['fecha']) ? $_GET['fecha'] : $fecha_actual;
$fecha_formateada = date('d/m/Y', strtotime($fecha_consulta));

// Obtener el año actual para el selector de fecha
$ano_actual = date('Y');
$ano_maximo = $ano_actual + 5; // Permitir hasta 5 años en el futuro

// CONSULTA DIAGNÓSTICO: Ver TODAS las ventas sin filtrar por usuario primero
$query_todas_ventas = "SELECT * FROM ventas ORDER BY fecha DESC LIMIT 10";
$stmt_todas_ventas = $db->prepare($query_todas_ventas);
$stmt_todas_ventas->execute();
$todas_las_ventas = $stmt_todas_ventas->fetchAll(PDO::FETCH_ASSOC);

// CONSULTA: Ventas del usuario para la fecha específica
$query_ventas = "
    SELECT * FROM ventas 
    WHERE DATE(fecha) = :fecha_consulta 
    AND id_usuario = :user_id
    ORDER BY fecha DESC
";

$stmt_ventas = $db->prepare($query_ventas);
$stmt_ventas->bindParam(':fecha_consulta', $fecha_consulta);
$stmt_ventas->bindParam(':user_id', $_SESSION['user_id']);
$stmt_ventas->execute();
$ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

// Si no encuentra ventas, probar con el mes actual
if (count($ventas) === 0) {
    $mes_actual = date('Y-m');
    $query_ventas_mes = "
        SELECT * FROM ventas 
        WHERE id_usuario = :user_id
        AND DATE_FORMAT(fecha, '%Y-%m') = :mes_actual
        ORDER BY fecha DESC
    ";
    
    $stmt_ventas_mes = $db->prepare($query_ventas_mes);
    $stmt_ventas_mes->bindParam(':user_id', $_SESSION['user_id']);
    $stmt_ventas_mes->bindParam(':mes_actual', $mes_actual);
    $stmt_ventas_mes->execute();
    $ventas_mes = $stmt_ventas_mes->fetchAll(PDO::FETCH_ASSOC);
}

// Calcular totales
$total_efectivo = 0;
$total_yape = 0;
$total_plin = 0;
$total_general = 0;
$total_ventas = count($ventas);

foreach ($ventas as $venta) {
    $total_general += $venta['total'];
    switch ($venta['metodo_pago']) {
        case 'Efectivo':
            $total_efectivo += $venta['total'];
            break;
        case 'Yape':
            $total_yape += $venta['total'];
            break;
        case 'Plin':
            $total_plin += $venta['total'];
            break;
    }
}

// Procesar cierre de caja
if ($_POST && isset($_POST['procesar_cierre'])) {
    $efectivo_reportado = $_POST['efectivo_reportado'];
    $observaciones = $_POST['observaciones'];
    
    // Registrar cierre de caja
    $query_cierre = "
        INSERT INTO cierres_caja (id_usuario, fecha, total_ventas, total_efectivo, total_yape, total_plin, efectivo_reportado, observaciones)
        VALUES (:user_id, NOW(), :total_ventas, :total_efectivo, :total_yape, :total_plin, :efectivo_reportado, :observaciones)
    ";
    
    try {
        $stmt_cierre = $db->prepare($query_cierre);
        $stmt_cierre->bindParam(':user_id', $_SESSION['user_id']);
        $stmt_cierre->bindParam(':total_ventas', $total_ventas);
        $stmt_cierre->bindParam(':total_efectivo', $total_efectivo);
        $stmt_cierre->bindParam(':total_yape', $total_yape);
        $stmt_cierre->bindParam(':total_plin', $total_plin);
        $stmt_cierre->bindParam(':efectivo_reportado', $efectivo_reportado);
        $stmt_cierre->bindParam(':observaciones', $observaciones);
        
        if ($stmt_cierre->execute()) {
            $success = "Cierre de caja registrado exitosamente";
            header("Location: cierre_caja.php?success=" . urlencode($success) . "&fecha=" . $fecha_consulta);
            exit();
        } else {
            $error = "Error al registrar el cierre de caja";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Mostrar mensaje de éxito si viene por GET
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja - Cajero</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .debug-info { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .debug-success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .debug-error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .fecha-selector { background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .no-print { }
        
        .resumen-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .resumen-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .resumen-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .resumen-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .metodo-pago {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .metodo-pago.efectivo { background: #e8f5e8; color: #2e7d32; }
        .metodo-pago.yape { background: #e3f2fd; color: #1565c0; }
        .metodo-pago.plin { background: #f3e5f5; color: #7b1fa2; }
        
        .diferencia {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
            font-size: 16px;
        }
        
        .diferencia.exacto { background: #e8f5e8; color: #2e7d32; border: 1px solid #2e7d32; }
        .diferencia.sobrante { background: #fff3e0; color: #ef6c00; border: 1px solid #ef6c00; }
        .diferencia.faltante { background: #ffebee; color: #c62828; border: 1px solid #c62828; }
        
        @media print { 
            .no-print { display: none !important; } 
            .sidebar, .user-actions, .btn-action, .cierre-form-card, .debug-info, .fecha-selector {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <div class="sidebar">
            <h3>Panel del Cajero</h3>
            <ul>
                <li><a href="dashboard.php">Inicio</a></li>
                <li><a href="ventas.php">Registrar Venta</a></li>
                <li><a href="cierre_caja.php" class="active">Cierre de Caja</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h2>Cierre de Caja</h2>
            
            <!-- Selector de fecha ACTUALIZADO -->
            <div class="fecha-selector no-print">
                <h4>📅 Seleccionar Fecha para el Cierre</h4>
                <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
                    <label for="fecha_consulta"><strong>Fecha:</strong></label>
                    <input type="date" id="fecha_consulta" name="fecha" 
                           value="<?php echo $fecha_consulta; ?>" 
                           min="<?php echo date('Y-01-01'); ?>" 
                           max="<?php echo date('Y-12-31', strtotime('+5 years')); ?>">
                    <button type="submit" class="btn-action btn-primary">Consultar</button>
                    <button type="button" onclick="location.href='cierre_caja.php'" class="btn-action btn-secondary">Hoy</button>
                </form>
                <p><small><strong>Fecha actual del sistema:</strong> <?php echo $fecha_actual; ?></small></p>
            </div>
            
            <p class="subtitle">Resumen de ventas del día - <?php echo $fecha_formateada; ?></p>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- INFORMACIÓN DE DIAGNÓSTICO MEJORADA -->
          
            
            <div class="cierre-container">
                <!-- Resumen del día -->
                <div class="card resumen-card">
                    <h3>Resumen del Día - <?php echo $fecha_formateada; ?></h3>
                    <div class="resumen-stats">
                        <div class="resumen-item">
                            <div class="resumen-label">Total Ventas</div>
                            <div class="resumen-value"><?php echo $total_ventas; ?></div>
                        </div>
                        <div class="resumen-item">
                            <div class="resumen-label">Ingreso Total</div>
                            <div class="resumen-value">S/ <?php echo number_format($total_general, 2); ?></div>
                        </div>
                        <div class="resumen-item">
                            <div class="resumen-label">Efectivo</div>
                            <div class="resumen-value">S/ <?php echo number_format($total_efectivo, 2); ?></div>
                        </div>
                        <div class="resumen-item">
                            <div class="resumen-label">Yape</div>
                            <div class="resumen-value">S/ <?php echo number_format($total_yape, 2); ?></div>
                        </div>
                        <div class="resumen-item">
                            <div class="resumen-label">Plin</div>
                            <div class="resumen-value">S/ <?php echo number_format($total_plin, 2); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Formulario de cierre -->
                <div class="card cierre-form-card no-print">
                    <h3>Procesar Cierre de Caja</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="fecha_cierre" value="<?php echo $fecha_consulta; ?>">
                        
                        <div class="form-group">
                            <label for="efectivo_reportado">Efectivo Reportado en Caja</label>
                            <input type="number" id="efectivo_reportado" name="efectivo_reportado" 
                                   step="0.01" min="0" value="<?php echo number_format($total_efectivo, 2, '.', ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" rows="4"></textarea>
                        </div>
                        
                        <div class="diferencia-section">
                            <?php
                            $diferencia_calculada = 0;
                            if (isset($_POST['efectivo_reportado'])) {
                                $diferencia_calculada = $_POST['efectivo_reportado'] - $total_efectivo;
                            }
                            
                            $clase_diferencia = 'exacto';
                            if ($diferencia_calculada > 0) {
                                $clase_diferencia = 'sobrante';
                            } elseif ($diferencia_calculada < 0) {
                                $clase_diferencia = 'faltante';
                            }
                            ?>
                            <div class="diferencia <?php echo $clase_diferencia; ?>">
                                <strong>Diferencia: S/ <?php echo number_format(abs($diferencia_calculada), 2); ?></strong>
                                <span>
                                    <?php 
                                    if ($diferencia_calculada == 0) {
                                        echo "✅ Exacto";
                                    } elseif ($diferencia_calculada > 0) {
                                        echo "📈 Sobrante";
                                    } else {
                                        echo "📉 Faltante";
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <button type="submit" name="procesar_cierre" class="btn-primary large">
                            ✅ Procesar Cierre de Caja
                        </button>
                    </form>
                </div>
                
                <!-- Detalle de ventas -->
                <div class="card ventas-detalle-card">
                    <h3>Detalle de Ventas del Día - <?php echo $fecha_formateada; ?></h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th># Venta</th>
                                    <th>Hora</th>
                                    <th>Método Pago</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($ventas) > 0): ?>
                                    <?php foreach ($ventas as $venta): ?>
                                    <tr>
                                        <td>#<?php echo $venta['id']; ?></td>
                                        <td><?php echo date('H:i', strtotime($venta['fecha'])); ?></td>
                                        <td>
                                            <span class="metodo-pago <?php echo strtolower($venta['metodo_pago']); ?>">
                                                <?php echo $venta['metodo_pago']; ?>
                                            </span>
                                        </td>
                                        <td>S/ <?php echo number_format($venta['total'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="no-data">No hay ventas registradas para esta fecha</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Reporte imprimible -->
                <div class="card reporte-card">
                    <div class="card-header">
                        <h3>Reporte de Cierre - <?php echo $fecha_formateada; ?></h3>
                        <button onclick="window.print()" class="btn-action btn-primary no-print">🖨️ Imprimir Reporte</button>
                    </div>
                    <div class="card-body printable-report">
                        <div class="reporte-header">
                            <h2>Minimarket Pollon</h2>
                            <p>Reporte de Cierre de Caja</p>
                            <p><strong>Cajero:</strong> <?php echo $_SESSION['user_name'] ?? 'Usuario ' . $_SESSION['user_id']; ?></p>
                            <p><strong>Fecha:</strong> <?php echo $fecha_formateada . ' ' . date('H:i'); ?></p>
                        </div>
                        
                        <div class="reporte-resumen">
                            <table class="reporte-table">
                                <tr>
                                    <td>Total Ventas:</td>
                                    <td><?php echo $total_ventas; ?></td>
                                </tr>
                                <tr>
                                    <td>Total Efectivo:</td>
                                    <td>S/ <?php echo number_format($total_efectivo, 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Total Yape:</td>
                                    <td>S/ <?php echo number_format($total_yape, 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Total Plin:</td>
                                    <td>S/ <?php echo number_format($total_plin, 2); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong>Total General:</strong></td>
                                    <td><strong>S/ <?php echo number_format($total_general, 2); ?></strong></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="firmas">
                            <div class="firma-cajero">
                                <p>_________________________</p>
                                <p>Firma del Cajero</p>
                            </div>
                            <div class="firma-admin">
                                <p>_________________________</p>
                                <p>Firma del Administrador</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>