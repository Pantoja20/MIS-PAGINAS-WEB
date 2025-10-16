<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Administrador') {
    header("Location: ../login.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

$producto = null;
$error = '';
$success = '';

// Obtener datos del producto
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM productos WHERE id = :id AND estado = 'Activo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Producto no encontrado";
    }
}

// Procesar actualización
if ($_POST && isset($_POST['actualizar_producto'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $categoria = $_POST['categoria'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    
    $query = "UPDATE productos SET nombre = :nombre, categoria = :categoria, 
              precio = :precio, cantidad = :cantidad WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':categoria', $categoria);
    $stmt->bindParam(':precio', $precio);
    $stmt->bindParam(':cantidad', $cantidad);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $success = "✅ Producto actualizado exitosamente";
        // Actualizar datos del producto
        $producto['nombre'] = $nombre;
        $producto['categoria'] = $categoria;
        $producto['precio'] = $precio;
        $producto['cantidad'] = $cantidad;
    } else {
        $error = "❌ Error al actualizar el producto";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Administrador</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <?php include('../includes/sidebar_admin.php'); ?>
        
        <div class="main-content">
            <div class="page-header">
                <h2>Editar Producto</h2>
                <a href="productos.php" class="btn-back">← Volver a Productos</a>
            </div>
            
            <!-- Notificaciones Modernas -->
            <?php if (!empty($error)): ?>
                <div class="notification error">
                    <div class="notification-icon">❌</div>
                    <div class="notification-content">
                        <div class="notification-title">Error</div>
                        <div class="notification-message"><?php echo $error; ?></div>
                    </div>
                    <button class="notification-close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="notification success">
                    <div class="notification-icon">✅</div>
                    <div class="notification-content">
                        <div class="notification-title">Éxito</div>
                        <div class="notification-message"><?php echo $success; ?></div>
                    </div>
                    <button class="notification-close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>
            
            <?php if ($producto): ?>
            <div class="card">
                <form method="POST" action="" class="edit-form">
                    <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre">Nombre del Producto</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                   required class="modern-input">
                        </div>
                        
                        <div class="form-group">
                            <label for="categoria">Categoría</label>
                            <select id="categoria" name="categoria" required class="modern-select">
                                <option value="">Seleccionar categoría</option>
                                <option value="Abarrotes" <?php echo $producto['categoria'] == 'Abarrotes' ? 'selected' : ''; ?>>Abarrotes</option>
                                <option value="Lácteos" <?php echo $producto['categoria'] == 'Lácteos' ? 'selected' : ''; ?>>Lácteos</option>
                                <option value="Bebidas" <?php echo $producto['categoria'] == 'Bebidas' ? 'selected' : ''; ?>>Bebidas</option>
                                <option value="Limpieza" <?php echo $producto['categoria'] == 'Limpieza' ? 'selected' : ''; ?>>Limpieza</option>
                                <option value="Snacks" <?php echo $producto['categoria'] == 'Snacks' ? 'selected' : ''; ?>>Snacks</option>
                                <option value="Congelados" <?php echo $producto['categoria'] == 'Congelados' ? 'selected' : ''; ?>>Congelados</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="precio">Precio (S/)</label>
                            <input type="number" id="precio" name="precio" 
                                   value="<?php echo $producto['precio']; ?>" 
                                   step="0.01" min="0" required class="modern-input">
                        </div>
                        
                        <div class="form-group">
                            <label for="cantidad">Cantidad en Stock</label>
                            <input type="number" id="cantidad" name="cantidad" 
                                   value="<?php echo $producto['cantidad']; ?>" 
                                   min="0" required class="modern-input">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="actualizar_producto" class="btn-primary large">
                            💾 Guardar Cambios
                        </button>
                        <a href="productos.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <div class="notification warning">
                    <div class="notification-icon">⚠️</div>
                    <div class="notification-content">
                        <div class="notification-title">Producto no disponible</div>
                        <div class="notification-message">El producto que intentas editar no existe o ha sido eliminado.</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>