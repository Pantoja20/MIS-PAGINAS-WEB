// Variables globales
let productos = [];
let carrito = [];
let categoriaActual = 'all';
let terminoBusqueda = '';

// Elementos DOM
const productsContainer = document.getElementById('products-container');
const cartIcon = document.getElementById('cart-icon');
const cartModal = document.getElementById('cart-modal');
const closeCart = document.getElementById('close-cart');
const cartItems = document.getElementById('cart-items');
const cartTotal = document.getElementById('cart-total');
const cartCount = document.getElementById('cart-count');
const categories = document.querySelectorAll('.categories li');
const searchInput = document.getElementById('search-input');
const yapeBtn = document.getElementById('yape-btn');
const cashBtn = document.getElementById('cash-btn');
const yapeModal = document.getElementById('yape-modal');
const cashModal = document.getElementById('cash-modal');
const cancelYape = document.getElementById('cancel-yape');
const confirmYape = document.getElementById('confirm-yape');
const printBoleta = document.getElementById('print-boleta');
const continueShopping = document.getElementById('continue-shopping');
const confirmCash = document.getElementById('confirm-cash');
const customerName = document.getElementById('customer-name');
const boletaContent = document.getElementById('boleta-content');

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    cargarProductos();
    cargarCarritoDesdeLocalStorage();
    actualizarCarrito();
    
    // Event Listeners
    cartIcon.addEventListener('click', mostrarCarrito);
    closeCart.addEventListener('click', ocultarCarrito);
    
    categories.forEach(category => {
        category.addEventListener('click', filtrarPorCategoria);
    });
    
    searchInput.addEventListener('input', buscarProductos);
    
    yapeBtn.addEventListener('click', mostrarPagoYape);
    cashBtn.addEventListener('click', mostrarPagoEfectivo);
    
    cancelYape.addEventListener('click', ocultarPagoYape);
    confirmYape.addEventListener('click', procesarPagoYape);
    
    printBoleta.addEventListener('click', imprimirBoleta);
    continueShopping.addEventListener('click', continuarComprando);
    confirmCash.addEventListener('click', confirmarPagoEfectivo);
    
    // Cerrar modales al hacer clic fuera
    window.addEventListener('click', function(event) {
        if (event.target === cartModal) {
            ocultarCarrito();
        }
        if (event.target === yapeModal) {
            ocultarPagoYape();
        }
        if (event.target === cashModal) {
            ocultarPagoEfectivo();
        }
    });
});

// Cargar productos desde la base de datos
async function cargarProductos() {
    try {
        const response = await fetch('backend/productos.php');
        if (!response.ok) {
            throw new Error('Error al cargar productos');
        }
        productos = await response.json();
        mostrarProductos();
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('No se pudieron cargar los productos', 'error');
    }
}

// Mostrar productos en la página
function mostrarProductos() {
    productsContainer.innerHTML = '';
    
    const productosFiltrados = productos.filter(producto => {
        const coincideCategoria = categoriaActual === 'all' || producto.categoria === categoriaActual;
        const coincideBusqueda = producto.nombre.toLowerCase().includes(terminoBusqueda.toLowerCase());
        return coincideCategoria && coincideBusqueda;
    });
    
    if (productosFiltrados.length === 0) {
        productsContainer.innerHTML = '<p class="empty-cart">No se encontraron productos.</p>';
        return;
    }
    
    productosFiltrados.forEach(producto => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        
        const itemEnCarrito = carrito.find(item => item.id === producto.id);
        const cantidadEnCarrito = itemEnCarrito ? itemEnCarrito.cantidad : 0;
        const stockDisponible = producto.stock - cantidadEnCarrito;
        
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${producto.imagen || 'images/productos/default.jpg'}" alt="${producto.nombre}" onerror="this.src='images/productos/default.jpg'">
            </div>
            <div class="product-info">
                <div class="product-name">${producto.nombre}</div>
                <div class="product-price">S/ ${parseFloat(producto.precio).toFixed(2)}</div>
                <div class="product-stock ${stockDisponible <= 0 ? 'sin-stock' : ''}">
                    ${stockDisponible <= 0 ? 'Agotado' : `Stock: ${stockDisponible}`}
                </div>
                <button class="add-to-cart" data-id="${producto.id}" ${stockDisponible <= 0 ? 'disabled' : ''}>
                    ${stockDisponible <= 0 ? 'Sin Stock' : 'Agregar al Carrito'}
                </button>
            </div>
        `;
        
        productsContainer.appendChild(productCard);
    });
    
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', agregarAlCarrito);
    });
}

// Filtrar productos por categoría
function filtrarPorCategoria(event) {
    categories.forEach(category => category.classList.remove('active'));
    event.target.classList.add('active');
    categoriaActual = event.target.getAttribute('data-category');
    mostrarProductos();
}

// Buscar productos
function buscarProductos(event) {
    terminoBusqueda = event.target.value;
    mostrarProductos();
}

// Funciones del carrito
function agregarAlCarrito(event) {
    const productId = parseInt(event.target.getAttribute('data-id'));
    const producto = productos.find(p => p.id === productId);
    
    if (!producto) return;
    
    const itemEnCarrito = carrito.find(item => item.id === productId);
    const cantidadEnCarrito = itemEnCarrito ? itemEnCarrito.cantidad : 0;
    
    if (cantidadEnCarrito >= producto.stock) {
        mostrarNotificacion('No hay suficiente stock disponible', 'warning');
        return;
    }
    
    if (itemEnCarrito) {
        itemEnCarrito.cantidad++;
    } else {
        carrito.push({
            id: producto.id,
            nombre: producto.nombre,
            precio: parseFloat(producto.precio),
            imagen: producto.imagen,
            cantidad: 1
        });
    }
    
    guardarCarritoEnLocalStorage();
    actualizarCarrito();
    mostrarProductos();
    mostrarNotificacion('Producto agregado al carrito', 'success');
}

function actualizarCarrito() {
    const totalItems = carrito.reduce((total, item) => total + item.cantidad, 0);
    cartCount.textContent = totalItems;
    
    cartItems.innerHTML = '';
    
    if (carrito.length === 0) {
        cartItems.innerHTML = '<p class="empty-cart">Tu carrito está vacío</p>';
        cartTotal.textContent = '0.00';
        return;
    }
    
    let total = 0;
    
    carrito.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div class="item-info">
                <div class="item-image">
                    <img src="${item.imagen || 'images/productos/default.jpg'}" alt="${item.nombre}" onerror="this.src='images/productos/default.jpg'">
                </div>
                <div class="item-details">
                    <h4>${item.nombre}</h4>
                    <p>S/ ${item.precio.toFixed(2)}</p>
                </div>
            </div>
            <div class="item-quantity">
                <button class="quantity-btn decrease" data-id="${item.id}">-</button>
                <input type="number" class="quantity-input" value="${item.cantidad}" min="1" data-id="${item.id}">
                <button class="quantity-btn increase" data-id="${item.id}">+</button>
                <button class="remove-item" data-id="${item.id}">🗑️</button>
            </div>
            <div class="item-subtotal">
                S/ ${subtotal.toFixed(2)}
            </div>
        `;
        
        cartItems.appendChild(cartItem);
    });
    
    cartTotal.textContent = total.toFixed(2);
    
    document.querySelectorAll('.decrease').forEach(button => {
        button.addEventListener('click', disminuirCantidad);
    });
    
    document.querySelectorAll('.increase').forEach(button => {
        button.addEventListener('click', aumentarCantidad);
    });
    
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', cambiarCantidad);
    });
    
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', eliminarDelCarrito);
    });
}

function aumentarCantidad(event) {
    const productId = parseInt(event.target.getAttribute('data-id'));
    const item = carrito.find(item => item.id === productId);
    const producto = productos.find(p => p.id === productId);
    
    if (item && producto) {
        if (item.cantidad >= producto.stock) {
            mostrarNotificacion('No hay suficiente stock disponible', 'warning');
            return;
        }
        
        item.cantidad++;
        guardarCarritoEnLocalStorage();
        actualizarCarrito();
        mostrarProductos();
    }
}

function disminuirCantidad(event) {
    const productId = parseInt(event.target.getAttribute('data-id'));
    const item = carrito.find(item => item.id === productId);
    
    if (item) {
        if (item.cantidad > 1) {
            item.cantidad--;
        } else {
            carrito = carrito.filter(item => item.id !== productId);
        }
        
        guardarCarritoEnLocalStorage();
        actualizarCarrito();
        mostrarProductos();
    }
}

function cambiarCantidad(event) {
    const productId = parseInt(event.target.getAttribute('data-id'));
    const nuevaCantidad = parseInt(event.target.value);
    const item = carrito.find(item => item.id === productId);
    const producto = productos.find(p => p.id === productId);
    
    if (item && producto && nuevaCantidad > 0) {
        if (nuevaCantidad > producto.stock) {
            mostrarNotificacion('No hay suficiente stock disponible', 'warning');
            event.target.value = item.cantidad;
            return;
        }
        
        item.cantidad = nuevaCantidad;
        guardarCarritoEnLocalStorage();
        actualizarCarrito();
        mostrarProductos();
    } else {
        event.target.value = item.cantidad;
    }
}

function eliminarDelCarrito(event) {
    const productId = parseInt(event.target.getAttribute('data-id'));
    const producto = carrito.find(item => item.id === productId);
    carrito = carrito.filter(item => item.id !== productId);
    guardarCarritoEnLocalStorage();
    actualizarCarrito();
    mostrarProductos();
    mostrarNotificacion(`${producto.nombre} eliminado del carrito`, 'info');
}

function mostrarCarrito() {
    if (carrito.length === 0) {
        mostrarNotificacion('Tu carrito está vacío', 'info');
        return;
    }
    cartModal.style.display = 'flex';
}

function ocultarCarrito() {
    cartModal.style.display = 'none';
}

// Funciones de pago
function mostrarPagoYape() {
    if (carrito.length === 0) {
        mostrarNotificacion('Tu carrito está vacío', 'warning');
        return;
    }
    
    ocultarCarrito();
    yapeModal.style.display = 'flex';
}

function ocultarPagoYape() {
    yapeModal.style.display = 'none';
}

async function procesarPagoYape() {
    try {
        const response = await fetch('backend/pago.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                metodo_pago: 'yape',
                carrito: carrito,
                cliente: { nombre: 'Cliente Yape' }
            })
        });

        const resultado = await response.json();
        
        if (resultado.success) {
            mostrarCompraExitosa(resultado.numero_boleta, resultado.total, 'Yape');
            await actualizarStockFrontend();
            vaciarCarrito();
            ocultarPagoYape();
        } else {
            mostrarNotificacion('Error en el pago: ' + resultado.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al procesar el pago', 'error');
    }
}

function mostrarPagoEfectivo() {
    if (carrito.length === 0) {
        mostrarNotificacion('Tu carrito está vacío', 'warning');
        return;
    }
    
    ocultarCarrito();
    generarBoleta();
    cashModal.style.display = 'flex';
}

function ocultarPagoEfectivo() {
    cashModal.style.display = 'none';
}

function generarBoleta() {
    const nombreCliente = customerName.value || 'Cliente';
    let total = 0;
    const fecha = new Date();
    const numeroBoletaTemporal = 'B' + Date.now().toString().slice(-8);
    
    let boletaHTML = `
        <div class="boleta-container">
            <div class="boleta-header">
                <div class="logo-boleta">
                    <h2>BeaMarket</h2>
                    <p>Tu tienda de confianza</p>
                </div>
                <div class="boleta-info">
                    <div class="boleta-title">BOLETA DE VENTA</div>
                    <div class="boleta-number">N°: ${numeroBoletaTemporal}</div>
                </div>
            </div>
            
            <div class="boleta-divider"></div>
            
            <div class="boleta-details">
                <div class="detail-row">
                    <span class="detail-label">Cliente:</span>
                    <span class="detail-value">${nombreCliente}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fecha:</span>
                    <span class="detail-value">${fecha.toLocaleDateString()}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Hora:</span>
                    <span class="detail-value">${fecha.toLocaleTimeString()}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Método:</span>
                    <span class="detail-value">EFECTIVO</span>
                </div>
            </div>
            
            <div class="boleta-divider"></div>
            
            <div class="products-table">
                <div class="table-header">
                    <div class="col-producto">Producto</div>
                    <div class="col-cantidad">Cant.</div>
                    <div class="col-precio">P.Unit</div>
                    <div class="col-subtotal">Subtotal</div>
                </div>
                
                <div class="table-body">
    `;
    
    carrito.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        
        boletaHTML += `
                    <div class="table-row">
                        <div class="col-producto">${item.nombre}</div>
                        <div class="col-cantidad">${item.cantidad}</div>
                        <div class="col-precio">S/ ${item.precio.toFixed(2)}</div>
                        <div class="col-subtotal">S/ ${subtotal.toFixed(2)}</div>
                    </div>
        `;
    });
    
    const igv = total * 0.18;
    const subtotalSinIgv = total - igv;
    
    boletaHTML += `
                </div>
            </div>
            
            <div class="boleta-divider"></div>
            
            <div class="boleta-totals">
                <div class="total-row">
                    <span class="total-label">Subtotal:</span>
                    <span class="total-value">S/ ${subtotalSinIgv.toFixed(2)}</span>
                </div>
                <div class="total-row">
                    <span class="total-label">IGV (18%):</span>
                    <span class="total-value">S/ ${igv.toFixed(2)}</span>
                </div>
                <div class="total-row grand-total">
                    <span class="total-label">TOTAL:</span>
                    <span class="total-value">S/ ${total.toFixed(2)}</span>
                </div>
            </div>
            
            <div class="boleta-divider"></div>
            
            <div class="boleta-footer">
                <div class="thank-you">
                    <p>¡Gracias por su compra!</p>
                    <p class="slogan">Vuelva pronto</p>
                </div>
                <div class="boleta-qr">
                    <div class="qr-placeholder">
                        <div class="qr-text">CÓDIGO QR</div>
                        <div class="qr-small">${numeroBoletaTemporal}</div>
                    </div>
                </div>
            </div>
            
            <div class="boleta-legal">
                <p>Esta boleta es un comprobante de pago electrónico</p>
                <p>Conserve este documento para cualquier reclamo</p>
            </div>
        </div>
    `;
    
    boletaContent.innerHTML = boletaHTML;
}

async function confirmarPagoEfectivo() {
    const nombreCliente = customerName.value.trim();
    
    if (!nombreCliente) {
        mostrarNotificacion('Por favor ingrese su nombre para generar la boleta', 'warning');
        customerName.focus();
        return;
    }

    try {
        const response = await fetch('backend/pago.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                metodo_pago: 'efectivo',
                carrito: carrito,
                cliente: { nombre: nombreCliente }
            })
        });

        const resultado = await response.json();
        
        if (resultado.success) {
            mostrarCompraExitosa(resultado.numero_boleta, resultado.total, 'Efectivo');
            await actualizarStockFrontend();
            vaciarCarrito();
            ocultarPagoEfectivo();
            customerName.value = '';
        } else {
            mostrarNotificacion('Error en la venta: ' + resultado.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al procesar la venta', 'error');
    }
}

// NUEVA FUNCIÓN: Mostrar notificaciones bonitas
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    
    // Iconos para cada tipo
    const iconos = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    notificacion.innerHTML = `
        <div class="notificacion-contenido">
            <div class="notificacion-icono">${iconos[tipo]}</div>
            <div class="notificacion-mensaje">${mensaje}</div>
            <button class="notificacion-cerrar" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Agregar al contenedor de notificaciones
    const contenedor = document.getElementById('notificaciones-container') || crearContenedorNotificaciones();
    contenedor.appendChild(notificacion);
    
    // Animación de entrada
    setTimeout(() => {
        notificacion.classList.add('notificacion-show');
    }, 10);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.classList.remove('notificacion-show');
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.remove();
                }
            }, 300);
        }
    }, 5000);
}

function crearContenedorNotificaciones() {
    const contenedor = document.createElement('div');
    contenedor.id = 'notificaciones-container';
    contenedor.className = 'notificaciones-container';
    document.body.appendChild(contenedor);
    return contenedor;
}

function mostrarCompraExitosa(numeroBoleta, total, metodoPago) {
    const successModal = document.createElement('div');
    successModal.className = 'success-modal';
    successModal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2000;
        animation: fadeIn 0.3s ease;
    `;
    
    successModal.innerHTML = `
        <div class="success-content" style="
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease;
        ">
            <div class="success-icon" style="
                font-size: 4rem;
                margin-bottom: 20px;
                animation: bounce 1s ease;
            ">
                ✅
            </div>
            <h2 style="margin-bottom: 15px; font-size: 1.8rem;">¡Compra Exitosa!</h2>
            <div class="success-details" style="
                background: rgba(255, 255, 255, 0.2);
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
            ">
                <p style="margin: 10px 0; font-size: 1.1rem;">
                    <strong>N° Boleta:</strong> ${numeroBoleta}
                </p>
                <p style="margin: 10px 0; font-size: 1.1rem;">
                    <strong>Total:</strong> S/ ${parseFloat(total).toFixed(2)}
                </p>
                <p style="margin: 10px 0; font-size: 1.1rem;">
                    <strong>Método:</strong> ${metodoPago}
                </p>
            </div>
            <p style="margin: 20px 0; font-size: 1rem; opacity: 0.9;">
                Gracias por tu compra. ¡Vuelve pronto!
            </p>
            <button class="success-btn" onclick="this.closest('.success-modal').remove()" style="
                background: white;
                color: #27ae60;
                border: none;
                padding: 12px 30px;
                border-radius: 25px;
                font-size: 1rem;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 10px;
            ">
                Aceptar
            </button>
        </div>
    `;
    
    document.body.appendChild(successModal);
    
    setTimeout(() => {
        if (successModal.parentNode) {
            successModal.remove();
        }
    }, 5000);
}

function imprimirBoleta() {
    const ventanaImpresion = window.open('', '_blank');
    ventanaImpresion.document.write(`
        <html>
            <head>
                <title>Boleta de Venta - MiniMarket</title>
                <style>
                    body { 
                        font-family: 'Arial', sans-serif; 
                        margin: 0;
                        padding: 20px;
                        background: white;
                        color: #333;
                    }
                    .boleta-container {
                        max-width: 400px;
                        margin: 0 auto;
                        border: 2px solid #2c3e50;
                        border-radius: 10px;
                        padding: 20px;
                        background: white;
                    }
                    .boleta-header {
                        text-align: center;
                        margin-bottom: 15px;
                    }
                    .logo-boleta h2 {
                        color: #2c3e50;
                        margin: 0;
                        font-size: 24px;
                    }
                    .logo-boleta p {
                        color: #7f8c8d;
                        margin: 5px 0 0 0;
                        font-size: 12px;
                    }
                    .boleta-info {
                        margin-top: 10px;
                    }
                    .boleta-title {
                        font-weight: bold;
                        font-size: 16px;
                        color: #2c3e50;
                    }
                    .boleta-number {
                        font-size: 14px;
                        color: #e74c3c;
                        font-weight: bold;
                    }
                    .boleta-divider {
                        height: 1px;
                        background: linear-gradient(to right, transparent, #bdc3c7, transparent);
                        margin: 10px 0;
                    }
                    .boleta-details {
                        margin-bottom: 15px;
                    }
                    .detail-row {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 5px;
                        font-size: 12px;
                    }
                    .detail-label {
                        font-weight: bold;
                        color: #2c3e50;
                    }
                    .detail-value {
                        color: #34495e;
                    }
                    .products-table {
                        margin: 15px 0;
                    }
                    .table-header {
                        display: flex;
                        background: #34495e;
                        color: white;
                        padding: 8px 5px;
                        border-radius: 5px;
                        font-weight: bold;
                        font-size: 11px;
                    }
                    .table-body {
                        max-height: 200px;
                        overflow-y: auto;
                    }
                    .table-row {
                        display: flex;
                        padding: 6px 5px;
                        border-bottom: 1px solid #ecf0f1;
                        font-size: 11px;
                    }
                    .table-row:nth-child(even) {
                        background: #f8f9fa;
                    }
                    .col-producto { flex: 3; }
                    .col-cantidad { flex: 1; text-align: center; }
                    .col-precio { flex: 1.5; text-align: right; }
                    .col-subtotal { flex: 1.5; text-align: right; font-weight: bold; }
                    .boleta-totals {
                        margin: 15px 0;
                    }
                    .total-row {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 8px;
                        font-size: 13px;
                    }
                    .grand-total {
                        font-weight: bold;
                        font-size: 16px;
                        color: #2c3e50;
                        border-top: 2px double #bdc3c7;
                        padding-top: 15px;
                        margin-top: 15px;
                    }
                    .boleta-footer {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin: 15px 0;
                    }
                    .thank-you p {
                        margin: 0;
                        font-size: 12px;
                        text-align: center;
                    }
                    .slogan {
                        font-style: italic;
                        color: #7f8c8d;
                    }
                    .qr-placeholder {
                        border: 2px dashed #bdc3c7;
                        padding: 10px;
                        text-align: center;
                        border-radius: 5px;
                    }
                    .qr-text {
                        font-size: 10px;
                        font-weight: bold;
                        color: #7f8c8d;
                    }
                    .qr-small {
                        font-size: 8px;
                        color: #95a5a6;
                        margin-top: 5px;
                    }
                    .boleta-legal {
                        text-align: center;
                        font-size: 9px;
                        color: #95a5a6;
                        margin-top: 15px;
                    }
                    .boleta-legal p {
                        margin: 3px 0;
                    }
                    @media print {
                        body { margin: 0; padding: 10px; }
                        .boleta-container { border: none; box-shadow: none; }
                    }
                </style>
            </head>
            <body>
                ${boletaContent.innerHTML}
            </body>
        </html>
    `);
    ventanaImpresion.document.close();
    ventanaImpresion.print();
}

function continuarComprando() {
    ocultarPagoEfectivo();
}

async function actualizarStockFrontend() {
    try {
        await cargarProductos();
        console.log('Stock actualizado correctamente');
    } catch (error) {
        console.error('Error al actualizar stock:', error);
    }
}

function vaciarCarrito() {
    carrito = [];
    guardarCarritoEnLocalStorage();
    actualizarCarrito();
    mostrarProductos();
}

// Local Storage
function guardarCarritoEnLocalStorage() {
    localStorage.setItem('carrito', JSON.stringify(carrito));
}

function cargarCarritoDesdeLocalStorage() {
    const carritoGuardado = localStorage.getItem('carrito');
    if (carritoGuardado) {
        carrito = JSON.parse(carritoGuardado);
    }
}

function mostrarError(mensaje) {
    productsContainer.innerHTML = `
        <div class="error-message">
            <p>${mensaje}</p>
            <button onclick="cargarProductos()">Reintentar</button>
        </div>
    `;
}