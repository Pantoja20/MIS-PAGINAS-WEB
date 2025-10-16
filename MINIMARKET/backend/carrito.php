<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Guardar carrito en la base de datos (para persistencia entre sesiones)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['session_id']) && isset($data['carrito'])) {
        try {
            $sql = "REPLACE INTO carritos (session_id, datos_carrito) VALUES (:session_id, :datos_carrito)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':session_id' => $data['session_id'],
                ':datos_carrito' => json_encode($data['carrito'])
            ]);
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

// Recuperar carrito de la base de datos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $session_id = $_GET['session_id'] ?? '';
    
    if ($session_id) {
        try {
            $sql = "SELECT datos_carrito FROM carritos WHERE session_id = :session_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':session_id' => $session_id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo $result['datos_carrito'];
            } else {
                echo json_encode([]);
            }
        } catch(PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
?>