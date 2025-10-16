<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Administrador') {
    header("Location: ../index.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// CORREGIR: Configurar zona horaria correcta
date_default_timezone_set('America/Lima');

// Obtener estadísticas generales
$productos_query = "SELECT COUNT(*) as total FROM productos WHERE estado = 'Activo'";
$productos_stmt = $db->prepare($productos_query);
$productos_stmt->execute();
$total_productos = $productos_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$usuarios_query = "SELECT COUNT(*) as total FROM usuarios WHERE estado = 'Activo'";
$usuarios_stmt = $db->prepare($usuarios_query);
$usuarios_stmt->execute();
$total_usuarios = $usuarios_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// CORREGIR: Usar fecha actual correcta
$fecha_hoy = date('Y-m-d');
$ventas_hoy_query = "SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as ingresos 
                     FROM ventas WHERE DATE(fecha) = :fecha_hoy";
$ventas_hoy_stmt = $db->prepare($ventas_hoy_query);
$ventas_hoy_stmt->bindParam(':fecha_hoy', $fecha_hoy);
$ventas_hoy_stmt->execute();
$ventas_hoy = $ventas_hoy_stmt->fetch(PDO::FETCH_ASSOC);

// CORREGIDO: Stock crítico ahora es <= 10 (10 unidades o menos)
$bajo_stock_query = "SELECT COUNT(*) as total FROM productos WHERE cantidad <= 10 AND estado = 'Activo'";
$bajo_stock_stmt = $db->prepare($bajo_stock_query);
$bajo_stock_stmt->execute();
$bajo_stock = $bajo_stock_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// CORREGIR: Ventas de los últimos 7 días con fecha actual
$ventas_semana_query = "
    SELECT DATE(fecha) as fecha, COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total
    FROM ventas 
    WHERE fecha >= DATE_SUB(:fecha_actual, INTERVAL 7 DAY)
    GROUP BY DATE(fecha)
    ORDER BY fecha
";
$ventas_semana_stmt = $db->prepare($ventas_semana_query);
$ventas_semana_stmt->bindParam(':fecha_actual', $fecha_hoy);
$ventas_semana_stmt->execute();
$ventas_semana = $ventas_semana_stmt->fetchAll(PDO::FETCH_ASSOC);

// CORREGIR: Productos más vendidos del mes con fecha actual
$mes_actual = date('m');
$ano_actual = date('Y');
$mas_vendidos_query = "
    SELECT p.nombre, SUM(dv.cantidad) as total_vendido
    FROM detalle_ventas dv
    JOIN productos p ON dv.id_producto = p.id
    JOIN ventas v ON dv.id_venta = v.id
    WHERE MONTH(v.fecha) = :mes_actual AND YEAR(v.fecha) = :ano_actual
    GROUP BY p.id
    ORDER BY total_vendido DESC
    LIMIT 5
";
$mas_vendidos_stmt = $db->prepare($mas_vendidos_query);
$mas_vendidos_stmt->bindParam(':mes_actual', $mes_actual);
$mas_vendidos_stmt->bindParam(':ano_actual', $ano_actual);
$mas_vendidos_stmt->execute();
$mas_vendidos = $mas_vendidos_stmt->fetchAll(PDO::FETCH_ASSOC);

// CORREGIDO: Productos con stock crítico (10 unidades o menos)
$stock_critico_query = "
    SELECT nombre, cantidad 
    FROM productos 
    WHERE cantidad <= 10 AND estado = 'Activo'
    ORDER BY cantidad ASC
    LIMIT 5
";
$stock_critico_stmt = $db->prepare($stock_critico_query);
$stock_critico_stmt->execute();
$stock_critico = $stock_critico_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio- Administrador</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <?php include('../includes/sidebar_admin.php'); ?>
        
        <div class="main-content">
            <div class="welcome-section">
                <h2>Inicio del Administrador</h2>
              
            </div>
            
            <!-- Estadísticas principales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3>Total Productos</h3>
                        <p class="stat-number"><?php echo $total_productos; ?></p>
                        <span class="stat-label">Activos en inventario</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Usuarios Activos</h3>
                        <p class="stat-number"><?php echo $total_usuarios; ?></p>
                        <span class="stat-label">En el sistema</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>Ventas Hoy</h3>
                        <p class="stat-number"><?php echo $ventas_hoy['total']; ?></p>
                        <span class="stat-label">S/ <?php echo number_format($ventas_hoy['ingresos'], 2); ?></span>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <h3>Bajo Stock</h3>
                        <p class="stat-number"><?php echo $bajo_stock; ?></p>
                        <span class="stat-label">Productos por reabastecer</span>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <!-- Gráfico de ventas -->
                <div class="dashboard-card large">
                    <div class="card-header">
                        <h3>Ventas de los Últimos 7 Días</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="ventasChart" height="250"></canvas>
                    </div>
                </div>
                
                <!-- Productos más vendidos -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Productos Más Vendidos</h3>
                        <span class="card-subtitle">Este mes</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($mas_vendidos) > 0): ?>
                            <div class="ranking-list">
                                <?php foreach ($mas_vendidos as $index => $producto): ?>
                                <div class="ranking-item">
                                    <div class="rank">#<?php echo $index + 1; ?></div>
                                    <div class="product-info">
                                        <div class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                        <div class="product-sales"><?php echo $producto['total_vendido']; ?> unidades</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No hay datos de ventas este mes</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Stock crítico -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Stock Crítico</h3>
                        <span class="card-subtitle">Necesitan atención</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($stock_critico) > 0): ?>
                            <div class="alert-list">
                                <?php foreach ($stock_critico as $producto): ?>
                                <div class="alert-item warning">
                                    <div class="alert-icon">⚠️</div>
                                    <div class="alert-content">
                                        <div class="alert-title"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                        <div class="alert-desc">Solo <?php echo $producto['cantidad']; ?> unidades</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">Todo el stock está en niveles normales</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Acciones rápidas -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Acciones Rápidas</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions-grid">
                            <a href="productos.php" class="quick-action">
                                <div class="action-icon">➕</div>
                                <span>Agregar Producto</span>
                            </a>
                            <a href="inventario.php" class="quick-action">
                                <div class="action-icon">📊</div>
                                <span>Ver Inventario</span>
                            </a>
                            <a href="usuarios.php" class="quick-action">
                                <div class="action-icon">👤</div>
                                <span>Gestionar Usuarios</span>
                            </a>
                            <a href="reportes.php" class="quick-action">
                                <div class="action-icon">📈</div>
                                <span>Ver Reportes</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actividad reciente -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Actividad Reciente</h3>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <?php
                        // Obtener actividad reciente (ventas recientes)
                        $actividad_query = "
                            SELECT v.id, v.fecha, v.total, u.nombre_completo as cajero
                            FROM ventas v
                            JOIN usuarios u ON v.id_usuario = u.id
                            ORDER BY v.fecha DESC
                            LIMIT 8
                        ";
                        $actividad_stmt = $db->prepare($actividad_query);
                        $actividad_stmt->execute();
                        $actividades = $actividad_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($actividades) > 0):
                            foreach ($actividades as $actividad):
                        ?>
                        <div class="activity-item">
                            <div class="activity-icon">💰</div>
                            <div class="activity-content">
                                <div class="activity-title">Venta #<?php echo $actividad['id']; ?></div>
                                <div class="activity-desc">
                                    Realizada por <?php echo $actividad['cajero']; ?> - S/ <?php echo number_format($actividad['total'], 2); ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('H:i', strtotime($actividad['fecha'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                        <p class="no-data">No hay actividad reciente</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
    
    <script>
        // Gráfico de ventas
        const ventasCtx = document.getElementById('ventasChart').getContext('2d');
        const ventasChart = new Chart(ventasCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $labels = [];
                    foreach ($ventas_semana as $venta) {
                        $labels[] = "'" . date('d/m', strtotime($venta['fecha'])) . "'";
                    }
                    echo implode(',', $labels);
                ?>],
                datasets: [{
                    label: 'Ventas (S/)',
                    data: [<?php 
                        $data = [];
                        foreach ($ventas_semana as $venta) {
                            $data[] = $venta['total'];
                        }
                        echo implode(',', $data);
                    ?>],
                    backgroundColor: '#4361ee',
                    borderColor: '#3f37c9',
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'S/ ' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
        
        // Actualizar la página cada 5 minutos para datos en tiempo real
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutos
    </script>
</body>
</html>