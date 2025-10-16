// Variables globales
let cart = [];
let scannerActive = false;

// Mostrar/ocultar secciones
function showSection(section) {
    document.querySelectorAll('.section').forEach(sec => {
        sec.style.display = 'none';
    });
    document.getElementById(section + '-section').style.display = 'block';
}

// Carrito de compras
function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        existingItem.cantidad += 1;
        existingItem.subtotal = existingItem.cantidad * existingItem.precio;
    } else {
        cart.push({
            ...product,
            cantidad: 1,
            subtotal: product.precio
        });
    }
    
    updateCartDisplay();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
}

function updateCartDisplay() {
    const cartItems = document.getElementById('cart-items');
    const totalAmount = document.getElementById('total-amount');
    
    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="text-muted">No hay productos en el carrito</p>';
        totalAmount.textContent = '0.00';
        return;
    }
    
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        total += item.subtotal;
        html += `
            <div class="cart-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>${item.nombre}</strong><br>
                        <small>S/ ${item.precio} x ${item.cantidad}</small>
                    </div>
                    <div>
                        <strong>S/ ${item.subtotal.toFixed(2)}</strong>
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    cartItems.innerHTML = html;
    totalAmount.textContent = total.toFixed(2);
}

function clearCart() {
    cart = [];
    updateCartDisplay();
}

// Escáner de código de barras
function startScanner() {
    if (scannerActive) return;
    
    scannerActive = true;
    
    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: document.querySelector('#video'),
            constraints: {
                width: 640,
                height: 480,
                facingMode: "environment"
            }
        },
        decoder: {
            readers: ["ean_reader", "ean_8_reader", "code_128_reader"]
        }
    }, function(err) {
        if (err) {
            console.log(err);
            alert('Error al iniciar el escáner: ' + err);
            return;
        }
        Quagga.start();
    });

    Quagga.onDetected(function(result) {
        const code = result.codeResult.code;
        searchProduct(code);
    });
}

function stopScanner() {
    if (scannerActive) {
        Quagga.stop();
        scannerActive = false;
    }
}

// Búsqueda de productos
function searchProduct(barcode) {
    fetch('api/productos.php?action=search&barcode=' + barcode)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addToCart(data.product);
                showNotification('Producto agregado: ' + data.product.nombre, 'success');
            } else {
                showNotification('Producto no encontrado', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al buscar producto', 'error');
        });
}

function searchProductManual() {
    const barcode = document.getElementById('manual-barcode').value;
    if (barcode) {
        searchProduct(barcode);
        document.getElementById('manual-barcode').value = '';
    }
}

// Proceso de pago
function showPaymentModal() {
    if (cart.length === 0) {
        showNotification('El carrito está vacío', 'warning');
        return;
    }
    
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
    
    // Mostrar/ocultar QR de Yape según método de pago
    document.querySelectorAll('input[name="metodo-pago"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('yape-qr').style.display = 
                this.value === 'yape' ? 'block' : 'none';
        });
    });
}

function processPayment() {
    const nombreCliente = document.getElementById('nombre-cliente').value;
    const metodoPago = document.querySelector('input[name="metodo-pago"]:checked').value;
    
    const ventaData = {
        productos: cart,
        total: cart.reduce((sum, item) => sum + item.subtotal, 0),
        metodo_pago: metodoPago,
        nombre_cliente: nombreCliente || null
    };
    
    fetch('api/ventas.php?action=create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(ventaData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Venta registrada exitosamente', 'success');
            generateReceipt(data.venta_id);
            clearCart();
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
        } else {
            showNotification('Error al procesar la venta: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al procesar la venta', 'error');
    });
}

// Generar boleta
function generateReceipt(ventaId) {
    fetch('api/ventas.php?action=receipt&id=' + ventaId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const receiptWindow = window.open('', '_blank');
                receiptWindow.document.write(`
                    <html>
                    <head>
                        <title>Boleta de Venta</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                            .item { display: flex; justify-content: space-between; margin-bottom: 5px; }
                            .total { border-top: 1px solid #000; padding-top: 10px; margin-top: 10px; font-weight: bold; }
                            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>MINI MARKET</h2>
                            <p>Boleta de Venta #${data.venta.id}</p>
                            <p>Fecha: ${new Date(data.venta.fecha_venta).toLocaleString()}</p>
                        </div>
                        ${data.cliente ? `<p><strong>Cliente:</strong> ${data.cliente}</p>` : ''}
                        <div class="items">
                            ${data.detalles.map(item => `
                                <div class="item">
                                    <span>${item.nombre} x${item.cantidad}</span>
                                    <span>S/ ${item.subtotal}</span>
                                </div>
                            `).join('')}
                        </div>
                        <div class="total">
                            <div class="item">
                                <span>TOTAL:</span>
                                <span>S/ ${data.venta.total}</span>
                            </div>
                        </div>
                        <div class="footer">
                            <p>¡Gracias por su compra!</p>
                        </div>
                    </body>
                    </html>
                `);
                receiptWindow.document.close();
                receiptWindow.print();
            }
        });
}

// Gestión de productos
function showProductModal(product = null) {
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    const title = document.getElementById('productModalTitle');
    const form = document.getElementById('productForm');
    
    form.reset();
    
    if (product) {
        title.textContent = 'Editar Producto';
        document.getElementById('product-id').value = product.id;
        document.getElementById('codigo-barras').value = product.codigo_barras;
        document.getElementById('nombre-producto').value = product.nombre;
        document.getElementById('descripcion').value = product.descripcion || '';
        document.getElementById('precio').value = product.precio;
        document.getElementById('stock').value = product.stock;
        document.getElementById('categoria').value = product.categoria || '';
    } else {
        title.textContent = 'Agregar Producto';
    }
    
    modal.show();
}

function saveProduct() {
    const formData = new FormData();
    formData.append('id', document.getElementById('product-id').value);
    formData.append('codigo_barras', document.getElementById('codigo-barras').value);
    formData.append('nombre', document.getElementById('nombre-producto').value);
    formData.append('descripcion', document.getElementById('descripcion').value);
    formData.append('precio', document.getElementById('precio').value);
    formData.append('stock', document.getElementById('stock').value);
    formData.append('categoria', document.getElementById('categoria').value);
    
    fetch('api/productos.php?action=save', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Producto guardado exitosamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
            loadProducts();
        } else {
            showNotification('Error al guardar producto: ' + data.message, 'error');
        }
    });
}

function loadProducts() {
    fetch('api/productos.php?action=list')
        .then(response => response.json())
        .then(data => {
            const table = document.getElementById('productos-table');
            let html = `
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach(product => {
                html += `
                    <tr>
                        <td>${product.codigo_barras}</td>
                        <td>${product.nombre}</td>
                        <td>S/ ${product.precio}</td>
                        <td>${product.stock}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="showProductModal(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            table.innerHTML = html;
        });
}

function deleteProduct(id) {
    if (confirm('¿Está seguro de eliminar este producto?')) {
        fetch('api/productos.php?action=delete&id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Producto eliminado', 'success');
                    loadProducts();
                } else {
                    showNotification('Error al eliminar producto', 'error');
                }
            });
    }
}

// Reportes
function loadReports() {
    const fechaInicio = document.getElementById('fecha-inicio').value;
    const fechaFin = document.getElementById('fecha-fin').value;
    
    let url = 'api/reportes.php?action=sales';
    if (fechaInicio) url += '&fecha_inicio=' + fechaInicio;
    if (fechaFin) url += '&fecha_fin=' + fechaFin;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('reportes-content');
            let html = `
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5>Total Ventas</h5>
                                <h3>S/ ${data.total_ventas || 0}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5>N° de Ventas</h5>
                                <h3>${data.numero_ventas || 0}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5>Productos Vendidos</h5>
                                <h3>${data.total_productos || 0}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (data.ventas && data.ventas.length > 0) {
                html += `
                    <div class="mt-4">
                        <h5>Detalle de Ventas</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Método Pago</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.ventas.forEach(venta => {
                    html += `
                        <tr>
                            <td>${venta.id}</td>
                            <td>${new Date(venta.fecha_venta).toLocaleDateString()}</td>
                            <td>${venta.nombre_cliente || 'N/A'}</td>
                            <td>${venta.metodo_pago}</td>
                            <td>S/ ${venta.total}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            }
            
            content.innerHTML = html;
        });
}

// Utilidades
function showNotification(message, type) {
    // Crear notificación simple
    alert(message);
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
});