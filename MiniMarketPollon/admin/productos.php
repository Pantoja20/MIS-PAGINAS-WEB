<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Administrador') {
    header("Location: ../login.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// Agregar producto
if ($_POST && isset($_POST['agregar_producto'])) {
    $nombre = $_POST['nombre'];
    $categoria = $_POST['categoria'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    
    $query = "INSERT INTO productos (nombre, categoria, precio, cantidad) 
              VALUES (:nombre, :categoria, :precio, :cantidad)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':categoria', $categoria);
    $stmt->bindParam(':precio', $precio);
    $stmt->bindParam(':cantidad', $cantidad);
    
    if ($stmt->execute()) {
        $success = "Producto agregado exitosamente";
        header("Location: productos.php?success=" . urlencode($success));
        exit();
    } else {
        $error = "Error al agregar el producto";
    }
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $query = "UPDATE productos SET estado = 'Inactivo' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $success = "Producto eliminado exitosamente";
        header("Location: productos.php?success=" . urlencode($success));
        exit();
    } else {
        $error = "Error al eliminar el producto";
    }
}

// Obtener productos
$query = "SELECT * FROM productos WHERE estado = 'Activo' ORDER BY cantidad ASC, id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CORREGIDO: Contar productos con stock crítico (<= 10)
$query_critico = "SELECT COUNT(*) as total FROM productos WHERE cantidad <= 10 AND estado = 'Activo'";
$stmt_critico = $db->prepare($query_critico);
$stmt_critico->execute();
$stock_critico = $stmt_critico->fetch(PDO::FETCH_ASSOC);

// Mostrar mensaje de éxito si viene por GET
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Administrador</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <?php include('../includes/sidebar_admin.php'); ?>
        
        <div class="main-content">
            <h2>Gestión de Productos</h2>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Estadísticas rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Productos</div>
                    <div class="stat-number"><?php echo count($productos); ?></div>
                    <small>Productos activos</small>
                </div>
                
                <div class="stat-card <?php echo $stock_critico['total'] > 0 ? 'critico' : ''; ?>">
                    <div class="stat-label">Stock Crítico</div>
                    <div class="stat-number"><?php echo $stock_critico['total']; ?></div>
                    <small>10 unidades o menos</small>
                </div>
            </div>
            
            <!-- Formulario para agregar producto -->
            <div class="card">
                <h3>Agregar Nuevo Producto</h3>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre del Producto</label>
                            <input type="text" id="nombre" name="nombre" required 
                                   placeholder="Ej: Arroz Costeño">
                        </div>
                        
                        <div class="form-group">
                            <label for="categoria">Categoría</label>
                            <select id="categoria" name="categoria" required>
                                <option value="">Seleccionar categoría</option>
                                <option value="Abarrotes">Abarrotes</option>
                                <option value="Lácteos">Lácteos</option>
                                <option value="Bebidas">Bebidas</option>
                                <option value="Limpieza">Limpieza</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Congelados">Congelados</option>
                               
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="precio">Precio (S/)</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" 
                                   placeholder="0.00" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cantidad">Cantidad en Stock</label>
                            <input type="number" id="cantidad" name="cantidad" min="0" 
                                   placeholder="0" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="agregar_producto" class="btn-primary">
                        Agregar Producto
                    </button>
                </form>
            </div>
            
            <!-- Lista de productos -->
            <div class="card">
                <div class="card-header">
                    <h3>Lista de Productos</h3>
                    <span class="stat-label">Total: <?php echo count($productos); ?> productos</span>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($productos) > 0): ?>
                                <?php foreach ($productos as $producto): ?>
                                <tr class="<?php echo $producto['cantidad'] <= 10 ? 'fila-critica' : ''; ?>">
                                    <td>#<?php echo $producto['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($producto['nombre']); ?>
                                        <?php if ($producto['cantidad'] <= 10): ?>
                                        <span class="stock-critico-badge">CRÍTICO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                    <td>S/ <?php echo number_format($producto['precio'], 2); ?></td>
                                    <td>
                                        <!-- CORREGIDO: Stock crítico <= 10 -->
                                        <span class="<?php echo $producto['cantidad'] <= 10 ? 'stock-bajo' : 'stock-normal'; ?>">
                                            <?php echo $producto['cantidad']; ?> unidades
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" 
                                               class="btn-action btn-edit">Editar</a>
                                            <a href="?eliminar=<?php echo $producto['id']; ?>" 
                                               class="btn-action btn-delete">Eliminar</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-data">
                                        No hay productos registrados. Agrega el primer producto usando el formulario arriba.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>

    <script>
    // Confirmaciones mejoradas para enlaces de eliminación
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.btn-delete');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const deleteUrl = this.getAttribute('href');
                const productRow = this.closest('tr');
                const productName = productRow.querySelector('td:nth-child(2)').textContent.replace('CRÍTICO', '').trim();
                
                showDeleteConfirmation(deleteUrl, productName);
            });
        });
    });

    function showDeleteConfirmation(deleteUrl, productName) {
        // Crear modal de confirmación usando estilos existentes
        const modalHTML = `
            <div class="modal-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; justify-content:center; align-items:center; z-index:1000;">
                <div class="card" style="max-width:400px; width:90%; margin:20px; text-align:center;">
                    <div style="font-size:48px; margin-bottom:15px;">🗑️</div>
                    <h3 style="color:#e74c3c; margin-bottom:10px;">Confirmar Eliminación</h3>
                    <p style="color:#666; margin-bottom:25px; line-height:1.5;">
                        ¿Estás seguro de eliminar <strong>"${productName}"</strong>?<br>
                        Esta acción no se puede deshacer.
                    </p>
                    <div style="display:flex; gap:10px; justify-content:center;">
                        <button class="btn-cancel" style="background:#95a5a6; color:white; border:none; padding:12px 25px; border-radius:6px; cursor:pointer; font-weight:600;">
                            Cancelar
                        </button>
                        <button class="btn-confirm-delete" style="background:#e74c3c; color:white; border:none; padding:12px 25px; border-radius:6px; cursor:pointer; font-weight:600;">
                            Sí, Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar modal al body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Event listeners para los botones del modal
        const overlay = document.querySelector('.modal-overlay');
        const cancelBtn = overlay.querySelector('.btn-cancel');
        const confirmBtn = overlay.querySelector('.btn-confirm-delete');
        
        cancelBtn.addEventListener('click', () => {
            overlay.remove();
        });
        
        confirmBtn.addEventListener('click', () => {
            // Mostrar notificación usando estilos existentes
            showSuccessNotification(`"${productName}" eliminado correctamente`);
            
            // Cerrar modal
            overlay.remove();
            
            // Redirigir después de un breve delay para que se vea la notificación
            setTimeout(() => {
                window.location.href = deleteUrl;
            }, 1500);
        });
        
        // Cerrar modal al hacer clic fuera
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
            }
        });
        
        // Cerrar modal con tecla Escape
        const closeOnEscape = (e) => {
            if (e.key === 'Escape') {
                overlay.remove();
                document.removeEventListener('keydown', closeOnEscape);
            }
        };
        document.addEventListener('keydown', closeOnEscape);
    }

    
    </script>
</body>
</html>