<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Cajero') {
    header("Location: ../index.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas del día
$query_ventas_hoy = "SELECT COUNT(*) as total, SUM(total) as ingresos 
                     FROM ventas 
                     WHERE DATE(fecha) = CURDATE() AND id_usuario = :user_id";
$stmt_ventas_hoy = $db->prepare($query_ventas_hoy);
$stmt_ventas_hoy->bindParam(':user_id', $_SESSION['user_id']);
$stmt_ventas_hoy->execute();
$ventas_hoy = $stmt_ventas_hoy->fetch(PDO::FETCH_ASSOC);

// Productos con bajo stock
$query_bajo_stock = "SELECT COUNT(*) as total FROM productos WHERE cantidad < 5 AND estado = 'Activo'";
$stmt_bajo_stock = $db->prepare($query_bajo_stock);
$stmt_bajo_stock->execute();
$bajo_stock = $stmt_bajo_stock->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Cajero</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <div class="sidebar">
            <h3>Panel del Cajero</h3>
            <ul>
                <li><a href="dashboard.php" class="active">Inicio</a></li>
                <li><a href="ventas.php">Registrar Venta</a></li>
                <li><a href="cierre_caja.php">Cierre de Caja</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h2>Inicio del Cajero</h2>
            
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Ventas Hoy</h3>
                    <p class="stat-number"><?php echo $ventas_hoy['total'] ?? 0; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Ingresos Hoy</h3>
                    <p class="stat-number">S/ <?php echo number_format($ventas_hoy['ingresos'] ?? 0, 2); ?></p>
                </div>
                
                <div class="stat-card warning">
                    <h3>Productos Bajo Stock</h3>
                    <p class="stat-number"><?php echo $bajo_stock; ?></p>
                </div>
            </div>
            
            <div class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="action-buttons">
                    <a href="ventas.php" class="btn-action btn-primary large">Nueva Venta</a>
                    <a href="cierre_caja.php" class="btn-action btn-edit large">Cierre de Caja</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>