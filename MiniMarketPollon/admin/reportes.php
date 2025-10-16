<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Administrador') {
    header("Location: ../login.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// Obtener parámetros de fecha
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Estadísticas generales
$query_ventas = "SELECT COUNT(*) as total_ventas, SUM(total) as total_ingresos 
                 FROM ventas 
                 WHERE DATE(fecha) BETWEEN :fecha_inicio AND :fecha_fin";
$stmt_ventas = $db->prepare($query_ventas);
$stmt_ventas->bindParam(':fecha_inicio', $fecha_inicio);
$stmt_ventas->bindParam(':fecha_fin', $fecha_fin);
$stmt_ventas->execute();
$estadisticas = $stmt_ventas->fetch(PDO::FETCH_ASSOC);

// Productos más vendidos
$query_mas_vendidos = "
    SELECT p.nombre, p.categoria, SUM(dv.cantidad) as total_vendido, SUM(dv.subtotal) as total_ingresos
    FROM detalle_ventas dv
    JOIN productos p ON dv.id_producto = p.id
    JOIN ventas v ON dv.id_venta = v.id
    WHERE DATE(v.fecha) BETWEEN :fecha_inicio AND :fecha_fin
    GROUP BY p.id
    ORDER BY total_vendido DESC
    LIMIT 10
";
$stmt_mas_vendidos = $db->prepare($query_mas_vendidos);
$stmt_mas_vendidos->bindParam(':fecha_inicio', $fecha_inicio);
$stmt_mas_vendidos->bindParam(':fecha_fin', $fecha_fin);
$stmt_mas_vendidos->execute();
$mas_vendidos = $stmt_mas_vendidos->fetchAll(PDO::FETCH_ASSOC);

// Ventas por día
$query_ventas_dia = "
    SELECT DATE(fecha) as fecha, COUNT(*) as cantidad_ventas, SUM(total) as total_dia
    FROM ventas
    WHERE DATE(fecha) BETWEEN :fecha_inicio AND :fecha_fin
    GROUP BY DATE(fecha)
    ORDER BY fecha
";
$stmt_ventas_dia = $db->prepare($query_ventas_dia);
$stmt_ventas_dia->bindParam(':fecha_inicio', $fecha_inicio);
$stmt_ventas_dia->bindParam(':fecha_fin', $fecha_fin);
$stmt_ventas_dia->execute();
$ventas_por_dia = $stmt_ventas_dia->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Análisis - Administrador</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <?php include('../includes/sidebar_admin.php'); ?>
        
        <div class="main-content">
            <h2>Reportes y Análisis</h2>
            
            <!-- Filtros -->
            <div class="card">
                <h3>Filtrar por Fecha</h3>
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Generar Reporte</button>
                </form>
            </div>
            
            <!-- Estadísticas generales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Ventas</h3>
                    <p class="stat-number"><?php echo $estadisticas['total_ventas'] ?? 0; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Ingresos Totales</h3>
                    <p class="stat-number">S/ <?php echo number_format($estadisticas['total_ingresos'] ?? 0, 2); ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Promedio por Venta</h3>
                    <p class="stat-number">
                        S/ <?php echo $estadisticas['total_ventas'] > 0 ? number_format($estadisticas['total_ingresos'] / $estadisticas['total_ventas'], 2) : '0.00'; ?>
                    </p>
                </div>
            </div>
            
            <!-- Gráfico de ventas -->
            <div class="card">
                <h3>Ventas por Día</h3>
                <canvas id="ventasChart" width="400" height="200"></canvas>
            </div>
            
            <!-- Productos más vendidos -->
            <div class="card">
                <h3>Productos Más Vendidos</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Cantidad Vendida</th>
                                <th>Total Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mas_vendidos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                <td><?php echo $producto['total_vendido']; ?> unidades</td>
                                <td>S/ <?php echo number_format($producto['total_ingresos'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
    
    <script>
        // Gráfico de ventas
        const ctx = document.getElementById('ventasChart').getContext('2d');
        const ventasChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($v) { return "'" . date('d/m', strtotime($v['fecha'])) . "'"; }, $ventas_por_dia)); ?>],
                datasets: [{
                    label: 'Ventas por Día (S/)',
                    data: [<?php echo implode(',', array_map(function($v) { return $v['total_dia']; }, $ventas_por_dia)); ?>],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>