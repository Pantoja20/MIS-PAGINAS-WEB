<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Obtener todos los productos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "SELECT id, nombre, precio, categoria, stock, imagen FROM productos WHERE stock > 0 ORDER BY categoria, nombre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $productos = $stmt->fetchAll();
        
        echo json_encode($productos);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar productos: ' . $e->getMessage()]);
    }
}
?>