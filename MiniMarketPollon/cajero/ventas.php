<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Cajero') {
    header("Location: ../login.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// Obtener productos disponibles
$query_productos = "SELECT * FROM productos WHERE estado = 'Activo' AND cantidad > 0 ORDER BY nombre";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Procesar datos de venta temporal (NO guardar en BD todavía)
if ($_POST && isset($_POST['procesar_venta'])) {
    try {
        $metodo_pago = $_POST['metodo_pago'];
        $total = $_POST['total_venta'];
        $productos_venta = json_decode($_POST['productos_venta'], true);
        
        // Validar que haya productos en el carrito
        if (empty($productos_venta)) {
            throw new Exception("No hay productos en el carrito");
        }
        
        // Validar stock disponible
        foreach ($productos_venta as $producto) {
            $query_stock = "SELECT cantidad, nombre FROM productos WHERE id = :id";
            $stmt_stock = $db->prepare($query_stock);
            $stmt_stock->bindParam(':id', $producto['id']);
            $stmt_stock->execute();
            $producto_info = $stmt_stock->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto_info || $producto_info['cantidad'] < $producto['cantidad']) {
                throw new Exception("Stock insuficiente para: " . $producto_info['nombre']);
            }
        }
        
        // Redirigir a boleta.php con los datos de la venta temporal
        header("Location: boleta.php?metodo_pago=" . urlencode($metodo_pago) . 
               "&total=" . urlencode($total) . 
               "&productos=" . urlencode($_POST['productos_venta']));
        exit();
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

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
    <title>Registrar Venta - Cajero</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <div class="sidebar">
            <h3>Panel del Cajero</h3>
            <ul>
                <li><a href="dashboard.php">Inicio</a></li>
                <li><a href="ventas.php" class="active">Registrar Venta</a></li>
                <li><a href="cierre_caja.php">Cierre de Caja</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h2>Registrar Venta</h2>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="venta-container">
                <!-- Lista de productos -->
                <div class="card productos-card">
                    <h3>Productos Disponibles</h3>
                    <div class="search-box">
                        <input type="text" id="searchProduct" placeholder="Buscar producto...">
                    </div>
                    <div class="productos-grid" id="productosGrid">
                        <?php foreach ($productos as $producto): ?>
                        <div class="producto-card" data-id="<?php echo $producto['id']; ?>" 
                             data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                             data-precio="<?php echo $producto['precio']; ?>"
                             data-stock="<?php echo $producto['cantidad']; ?>">
                            <h4><?php echo htmlspecialchars($producto['nombre']); ?></h4>
                            <p class="categoria"><?php echo htmlspecialchars($producto['categoria']); ?></p>
                            <p class="precio">S/ <?php echo number_format($producto['precio'], 2); ?></p>
                            <p class="stock">Stock: <?php echo $producto['cantidad']; ?></p>
                            <button class="btn-action btn-primary agregar-producto">Agregar</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Carrito de compras -->
                <div class="card carrito-card">
                    <h3>Carrito de Venta</h3>
                    <div class="carrito-items" id="carritoItems">
                        <p class="empty-cart">No hay productos en el carrito</p>
                    </div>
                    
                    <div class="carrito-total">
                        <h4>Total: S/ <span id="totalVenta">0.00</span></h4>
                    </div>
                    
                    <form method="POST" action="" id="ventaForm">
                        <input type="hidden" name="productos_venta" id="productosVenta">
                        <input type="hidden" name="total_venta" id="totalVentaInput">
                        
                        <div class="form-group">
                            <label for="metodo_pago">Método de Pago</label>
                            <select id="metodo_pago" name="metodo_pago" required>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Yape">Yape</option>
                                <option value="Plin">Plin</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="procesar_venta" class="btn-primary large" id="procesarVenta" disabled>
                            Ver Boleta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
    
    <script>
        let carrito = [];
        
        // Agregar producto al carrito
        document.querySelectorAll('.agregar-producto').forEach(button => {
            button.addEventListener('click', function() {
                const productoCard = this.closest('.producto-card');
                const id = productoCard.dataset.id;
                const nombre = productoCard.dataset.nombre;
                const precio = parseFloat(productoCard.dataset.precio);
                const stock = parseInt(productoCard.dataset.stock);
                
                // Verificar si el producto ya está en el carrito
                const existingItem = carrito.find(item => item.id == id);
                
                if (existingItem) {
                    if (existingItem.cantidad < stock) {
                        existingItem.cantidad++;
                        existingItem.subtotal = existingItem.cantidad * precio;
                    } else {
                        alert('No hay suficiente stock disponible');
                        return;
                    }
                } else {
                    carrito.push({
                        id: id,
                        nombre: nombre,
                        precio: precio,
                        cantidad: 1,
                        subtotal: precio
                    });
                }
                
                actualizarCarrito();
            });
        });
        
        // Actualizar carrito
        function actualizarCarrito() {
            const carritoItems = document.getElementById('carritoItems');
            const totalVenta = document.getElementById('totalVenta');
            const totalVentaInput = document.getElementById('totalVentaInput');
            const productosVenta = document.getElementById('productosVenta');
            const procesarVenta = document.getElementById('procesarVenta');
            
            if (carrito.length === 0) {
                carritoItems.innerHTML = '<p class="empty-cart">No hay productos en el carrito</p>';
                totalVenta.textContent = '0.00';
                procesarVenta.disabled = true;
                procesarVenta.textContent = 'Ver Boleta';
                return;
            }
            
            let html = '';
            let total = 0;
            
            carrito.forEach((item, index) => {
                total += item.subtotal;
                html += `
                    <div class="carrito-item">
                        <div class="item-info">
                            <h5>${item.nombre}</h5>
                            <p>S/ ${item.precio.toFixed(2)} x ${item.cantidad}</p>
                        </div>
                        <div class="item-actions">
                            <span>S/ ${item.subtotal.toFixed(2)}</span>
                            <button type="button" class="btn-action btn-delete small" onclick="eliminarDelCarrito(${index})">×</button>
                        </div>
                    </div>
                `;
            });
            
            carritoItems.innerHTML = html;
            totalVenta.textContent = total.toFixed(2);
            totalVentaInput.value = total;
            productosVenta.value = JSON.stringify(carrito);
            procesarVenta.disabled = false;
            procesarVenta.textContent = 'Ver Boleta';
        }
        
        // Eliminar producto del carrito
        function eliminarDelCarrito(index) {
            carrito.splice(index, 1);
            actualizarCarrito();
        }
        
        // Buscar productos
        document.getElementById('searchProduct').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const productos = document.querySelectorAll('.producto-card');
            
            productos.forEach(producto => {
                const nombre = producto.dataset.nombre.toLowerCase();
                if (nombre.includes(searchTerm)) {
                    producto.style.display = 'block';
                } else {
                    producto.style.display = 'none';
                }
            });
        });

        // Limpiar carrito después de una venta exitosa
        <?php if (isset($_GET['success'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            carrito = [];
            actualizarCarrito();
        });
        <?php endif; ?>
    </script>
</body>
</html>