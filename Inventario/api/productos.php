<?php
header('Content-Type: application/json');
include_once '../database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

switch($action) {
    case 'search':
        $barcode = $_GET['barcode'] ?? '';
        $query = "SELECT * FROM productos WHERE codigo_barras = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$barcode]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($product) {
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        }
        break;
        
    case 'list':
        $query = "SELECT * FROM productos ORDER BY nombre";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
        break;
        
    case 'save':
        $id = $_POST['id'] ?? '';
        $codigo_barras = $_POST['codigo_barras'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $categoria = $_POST['categoria'];
        
        if(empty($id)) {
            // Insertar nuevo producto
            $query = "INSERT INTO productos (codigo_barras, nombre, descripcion, precio, stock, categoria) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $success = $stmt->execute([$codigo_barras, $nombre, $descripcion, $precio, $stock, $categoria]);
        } else {
            // Actualizar producto existente
            $query = "UPDATE productos SET codigo_barras=?, nombre=?, descripcion=?, precio=?, stock=?, categoria=?
                     WHERE id=?";
            $stmt = $db->prepare($query);
            $success = $stmt->execute([$codigo_barras, $nombre, $descripcion, $precio, $stock, $categoria, $id]);
        }
        
        if($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar producto']);
        }
        break;
        
    case 'delete':
        $id = $_GET['id'];
        $query = "DELETE FROM productos WHERE id = ?";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$id]);
        echo json_encode(['success' => $success]);
        break;
}
?>