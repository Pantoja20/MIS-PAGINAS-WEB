<?php
header('Content-Type: application/json');
include_once '../database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

switch($action) {
    case 'sales':
        $where = "WHERE estado = 'completada'";
        $params = [];
        
        if($fecha_inicio) {
            $where .= " AND DATE(fecha_venta) >= ?";
            $params[] = $fecha_inicio;
        }
        
        if($fecha_fin) {
            $where .= " AND DATE(fecha_venta) <= ?";
            $params[] = $fecha_fin;
        }
        
        // Total de ventas
        $query = "SELECT SUM(total) as total_ventas, COUNT(*) as numero_ventas FROM ventas $where";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $totales = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total productos vendidos
        $query = "SELECT SUM(dv.cantidad) as total_productos 
                 FROM detalle_venta dv 
                 JOIN ventas v ON dv.venta_id = v.id 
                 $where";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $productos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Detalle de ventas
        $query = "SELECT * FROM ventas $where ORDER BY fecha_venta DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(array_merge($totales, $productos, ['ventas' => $ventas]));
        break;
}
?>