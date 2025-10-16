<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Administrador') {
    header("Location: ../login.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// Actualizar stock
if ($_POST && isset($_POST['actualizar_stock'])) {
    $producto_id = $_POST['producto_id'];
    $nueva_cantidad = $_POST['nueva_cantidad'];
    
    $query = "UPDATE productos SET cantidad = :cantidad WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cantidad', $nueva_cantidad);
    $stmt->bindParam(':id', $producto_id);
    
    if ($stmt->execute()) {
        $success = "Stock actualizado exitosamente";
    } else {
        $error = "Error al actualizar el stock";
    }
}

// Obtener productos con bajo stock
$query_bajo_stock = "SELECT * FROM productos WHERE cantidad < 10 AND estado = 'Activo' ORDER BY cantidad ASC";
$stmt_bajo_stock = $db->prepare($query_bajo_stock);
$stmt_bajo_stock->execute();
$bajo_stock = $stmt_bajo_stock->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los productos
$query_productos = "SELECT * FROM productos WHERE estado = 'Activo' ORDER BY categoria, nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Inventario - Administrador</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <?php include('../includes/sidebar_admin.php'); ?>
        
        <div class="main-content">
            <h2>Control de Inventario</h2>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Alertas de bajo stock -->
            <?php if (count($bajo_stock) > 0): ?>
            <div class="card warning-card">
                <h3>⚠ Productos con Bajo Stock</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock Actual</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bajo_stock as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                <td class="stock-bajo"><?php echo $producto['cantidad']; ?> unidades</td>
                                <td>
                                    <button class="btn-action btn-edit" onclick="abrirModal(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo $producto['cantidad']; ?>)">
                                        Reabastecer
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Inventario completo -->
            <div class="card">
                <h3>Inventario Completo</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                <td>S/ <?php echo number_format($producto['precio'], 2); ?></td>
                                <td>
                                    <span class="<?php echo $producto['cantidad'] < 10 ? 'stock-bajo' : 'stock-normal'; ?>">
                                        <?php echo $producto['cantidad']; ?> unidades
                                    </span>
                                </td>
                                <td>
                                    <?php if ($producto['cantidad'] == 0): ?>
                                        <span class="estado agotado">Agotado</span>
                                    <?php elseif ($producto['cantidad'] < 10): ?>
                                        <span class="estado bajo">Bajo Stock</span>
                                    <?php else: ?>
                                        <span class="estado normal">Disponible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-action btn-edit" onclick="abrirModal(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo $producto['cantidad']; ?>)">
                                        Actualizar Stock
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para actualizar stock -->
    <div id="modalStock" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Actualizar Stock</h3>
            <form method="POST" action="">
                <input type="hidden" id="producto_id" name="producto_id">
                <div class="form-group">
                    <label for="nombre_producto">Producto</label>
                    <input type="text" id="nombre_producto" readonly>
                </div>
                <div class="form-group">
                    <label for="stock_actual">Stock Actual</label>
                    <input type="number" id="stock_actual" readonly>
                </div>
                <div class="form-group">
                    <label for="nueva_cantidad">Nueva Cantidad</label>
                    <input type="number" id="nueva_cantidad" name="nueva_cantidad" min="0" required>
                </div>
                <button type="submit" name="actualizar_stock" class="btn-primary">Actualizar Stock</button>
            </form>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
    
    <script>
        // Funciones para el modal
        const modal = document.getElementById('modalStock');
        const closeBtn = document.querySelector('.close');
        
        function abrirModal(id, nombre, stockActual) {
            document.getElementById('producto_id').value = id;
            document.getElementById('nombre_producto').value = nombre;
            document.getElementById('stock_actual').value = stockActual;
            document.getElementById('nueva_cantidad').value = stockActual;
            modal.style.display = 'block';
        }
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>