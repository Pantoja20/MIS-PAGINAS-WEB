<?php
header('Content-Type: application/json');
include_once '../database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

switch($action) {
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $db->beginTransaction();
            
            // Insertar venta
            $query = "INSERT INTO ventas (total, metodo_pago, nombre_cliente) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$data['total'], $data['metodo_pago'], $data['nombre_cliente']]);
            $venta_id = $db->lastInsertId();
            
            // Insertar detalles de venta y actualizar stock
            foreach($data['productos'] as $producto) {
                $query = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$venta_id, $producto['id'], $producto['cantidad'], $producto['precio'], $producto['subtotal']]);
                
                // Actualizar stock
                $query = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$producto['cantidad'], $producto['id']]);
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'venta_id' => $venta_id]);
            
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'receipt':
        $venta_id = $_GET['id'];
        
        // Obtener información de la venta
        $query = "SELECT * FROM ventas WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$venta_id]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener detalles de la venta
        $query = "SELECT dv.*, p.nombre 
                 FROM detalle_venta dv 
                 JOIN productos p ON dv.producto_id = p.id 
                 WHERE dv.venta_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$venta_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'venta' => $venta,
            'detalles' => $detalles,
            'cliente' => $venta['nombre_cliente']
        ]);
        break;
}
?>