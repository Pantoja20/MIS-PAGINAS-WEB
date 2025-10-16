<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Procesar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['metodo_pago']) && isset($data['carrito']) && isset($data['cliente'])) {
        try {
            $pdo->beginTransaction();
            
            // 1. Verificar stock y actualizar
            foreach ($data['carrito'] as $producto) {
                // Verificar stock actual
                $sql = "SELECT stock, nombre FROM productos WHERE id = :id FOR UPDATE";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $producto['id']]);
                $productoDB = $stmt->fetch();
                
                if (!$productoDB) {
                    throw new Exception("Producto no encontrado: ID " . $producto['id']);
                }
                
                if ($productoDB['stock'] < $producto['cantidad']) {
                    throw new Exception("Stock insuficiente para: " . $productoDB['nombre'] . " (Stock: " . $productoDB['stock'] . ", Solicitado: " . $producto['cantidad'] . ")");
                }
                
                // Actualizar stock
                $sql = "UPDATE productos SET stock = stock - :cantidad WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $producto['id'],
                    ':cantidad' => $producto['cantidad']
                ]);
            }
            
            // 2. Registrar venta
            $total = array_reduce($data['carrito'], function($sum, $producto) {
                return $sum + ($producto['precio'] * $producto['cantidad']);
            }, 0);
            
            $sql = "INSERT INTO ventas (cliente_nombre, total, metodo_pago, fecha) VALUES (:cliente_nombre, :total, :metodo_pago, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cliente_nombre' => $data['cliente']['nombre'],
                ':total' => $total,
                ':metodo_pago' => $data['metodo_pago']
            ]);
            
            $venta_id = $pdo->lastInsertId();
            
            // 3. Registrar detalles de venta
            foreach ($data['carrito'] as $producto) {
                $sql = "INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio) VALUES (:venta_id, :producto_id, :cantidad, :precio)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':venta_id' => $venta_id,
                    ':producto_id' => $producto['id'],
                    ':cantidad' => $producto['cantidad'],
                    ':precio' => $producto['precio']
                ]);
            }
            
            $pdo->commit();
            
            // Generar número de boleta
            $numero_boleta = str_pad($venta_id, 8, '0', STR_PAD_LEFT);
            
            echo json_encode([
                'success' => true,
                'numero_boleta' => $numero_boleta,
                'total' => $total,
                'venta_id' => $venta_id
            ]);
            
        } catch(Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos para procesar el pago']);
    }
}
?>