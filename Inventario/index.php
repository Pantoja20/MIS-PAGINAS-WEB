<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Mini Market</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .scanner-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        #video {
            width: 100%;
            border: 2px solid #007bff;
            border-radius: 8px;
        }
        .scan-region {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #28a745;
            border-radius: 8px;
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-store"></i> Mini Market</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" onclick="showSection('ventas')">
                            <i class="fas fa-cash-register"></i> Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showSection('inventario')">
                            <i class="fas fa-boxes"></i> Inventario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showSection('reportes')">
                            <i class="fas fa-chart-bar"></i> Reportes
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Sección de Ventas -->
        <div id="ventas-section" class="section">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4><i class="fas fa-barcode"></i> Escanear Productos</h4>
                        </div>
                        <div class="card-body">
                            <div class="scanner-container">
                                <video id="video" playsinline></video>
                                <div class="scan-region"></div>
                            </div>
                            <div class="mt-3 text-center">
                                <button class="btn btn-primary" onclick="startScanner()">
                                    <i class="fas fa-play"></i> Iniciar Escáner
                                </button>
                                <button class="btn btn-secondary" onclick="stopScanner()">
                                    <i class="fas fa-stop"></i> Detener Escáner
                                </button>
                            </div>
                            <div class="mt-3">
                                <label for="manual-barcode" class="form-label">O ingresar código manualmente:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="manual-barcode" placeholder="Código de barras">
                                    <button class="btn btn-outline-primary" onclick="searchProductManual()">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h4><i class="fas fa-shopping-cart"></i> Carrito de Compras</h4>
                        </div>
                        <div class="card-body">
                            <div id="cart-items">
                                <p class="text-muted">No hay productos en el carrito</p>
                            </div>
                            <div class="mt-3">
                                <strong>Total: S/ <span id="total-amount">0.00</span></strong>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-warning btn-sm" onclick="clearCart()">
                                    <i class="fas fa-trash"></i> Limpiar Carrito
                                </button>
                                <button class="btn btn-success btn-sm" onclick="showPaymentModal()">
                                    <i class="fas fa-credit-card"></i> Proceder al Pago
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Inventario -->
        <div id="inventario-section" class="section" style="display: none;">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-boxes"></i> Gestión de Productos</h4>
                </div>
                <div class="card-body">
                    <button class="btn btn-success mb-3" onclick="showProductModal()">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                    <div id="productos-table">
                        <!-- Tabla de productos se cargará aquí -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Reportes -->
        <div id="reportes-section" class="section" style="display: none;">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4><i class="fas fa-chart-bar"></i> Reportes de Ventas</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="fecha-inicio" class="form-label">Fecha Inicio:</label>
                            <input type="date" class="form-control" id="fecha-inicio">
                        </div>
                        <div class="col-md-4">
                            <label for="fecha-fin" class="form-label">Fecha Fin:</label>
                            <input type="date" class="form-control" id="fecha-fin">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary form-control" onclick="loadReports()">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                    <div id="reportes-content">
                        <!-- Contenido de reportes se cargará aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Pago -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Procesar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre-cliente" class="form-label">Nombre del Cliente (opcional):</label>
                        <input type="text" class="form-control" id="nombre-cliente" placeholder="Ingrese nombre del cliente">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Pago:</label>
                        <div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="metodo-pago" id="efectivo" value="efectivo" checked>
                                <label class="form-check-label" for="efectivo">
                                    <i class="fas fa-money-bill-wave"></i> Efectivo
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="metodo-pago" id="yape" value="yape">
                                <label class="form-check-label" for="yape">
                                    <i class="fas fa-mobile-alt"></i> Yape
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="yape-qr" style="display: none; text-align: center;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=YAPE_PAYMENT_12345" 
                             alt="QR Yape" class="img-fluid">
                        <p class="mt-2">Escanea el código QR para pagar con Yape</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="processPayment()">
                        <i class="fas fa-check"></i> Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Producto -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Agregar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="product-id">
                        <div class="mb-3">
                            <label for="codigo-barras" class="form-label">Código de Barras</label>
                            <input type="text" class="form-control" id="codigo-barras" required>
                        </div>
                        <div class="mb-3">
                            <label for="nombre-producto" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre-producto" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="precio" class="form-label">Precio (S/)</label>
                            <input type="number" step="0.01" class="form-control" id="precio" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" required>
                        </div>
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <input type="text" class="form-control" id="categoria">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">Guardar Producto</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>