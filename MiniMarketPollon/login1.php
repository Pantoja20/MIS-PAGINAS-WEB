<?php
session_start();
// Si el usuario ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'Administrador') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: cajero/dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minimarket Pollon - Sistema de Gestión</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="home-page">
    <!-- Header -->
    <header class="home-header">
        <div class="container">
            <div class="logo">
                <h1>Minimarket Pollon</h1>
            </div>
            <nav class="main-nav">
                <a href="#features">Características</a>
                <a href="#about">Acerca de</a>
                <a href="login.php" class="btn-login">Iniciar Sesión</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Sistema de Gestión Minimarket Pollon</h1>
                <p class="hero-subtitle">Controla tu inventario, gestiona ventas y optimiza tu negocio con nuestra plataforma integral</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn-primary large">Iniciar Sesión</a>
                    <a href="register.php" class="btn-secondary large">Registrarse</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="dashboard-preview">
                    <div class="preview-header">
                        <div class="preview-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    <div class="preview-content">
                        <div class="preview-stats">
                            <div class="preview-stat">
                                <div class="stat-value">152</div>
                                <div class="stat-label">Ventas Hoy</div>
                            </div>
                            <div class="preview-stat">
                                <div class="stat-value">S/ 2,845</div>
                                <div class="stat-label">Ingresos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Características Principales</h2>
                <p>Todo lo que necesitas para gestionar tu minimarket eficientemente</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h3>Gestión de Inventario</h3>
                    <p>Controla tu stock en tiempo real, recibe alertas de bajo inventario y gestiona categorías de productos.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Registro de Ventas</h3>
                    <p>Sistema de punto de venta intuitivo con soporte para múltiples métodos de pago.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Reportes y Análisis</h3>
                    <p>Genera reportes detallados de ventas, inventario y tendencias de tu negocio.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Múltiples Roles</h3>
                    <p>Sistema de permisos con roles de Administrador y Cajero para un control seguro.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3>Actualización en Tiempo Real</h3>
                    <p>El inventario se actualiza automáticamente con cada venta registrada.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Diseño Responsive</h3>
                    <p>Accede al sistema desde cualquier dispositivo: computadora, tablet o móvil.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Roles Section -->
    <section class="roles">
        <div class="container">
            <div class="section-header">
                <h2>Roles del Sistema</h2>
                <p>Diferentes niveles de acceso según las responsabilidades</p>
            </div>
            <div class="roles-grid">
                <div class="role-card admin-role">
                    <div class="role-header">
                        <div class="role-icon">👑</div>
                        <h3>Administrador</h3>
                    </div>
                    <ul class="role-features">
                        <li>✅ Gestión completa de productos</li>
                        <li>✅ Control total de inventario</li>
                        <li>✅ Administración de usuarios</li>
                        <li>✅ Reportes y análisis avanzados</li>
                        <li>✅ Supervisión de ventas</li>
                    </ul>
                </div>
                <div class="role-card cajero-role">
                    <div class="role-header">
                        <div class="role-icon">💼</div>
                        <h3>Cajero</h3>
                    </div>
                    <ul class="role-features">
                        <li>✅ Registro de ventas</li>
                        <li>✅ Procesamiento de pagos</li>
                        <li>✅ Atención al cliente</li>
                        <li>✅ Cierre de caja</li>
                        <li>✅ Reportes de turno</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>¿Listo para optimizar tu minimarket?</h2>
                <p>Comienza a usar nuestro sistema hoy mismo y lleva tu negocio al siguiente nivel</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn-primary large">Crear Cuenta Gratis</a>
                    <a href="login.php" class="btn-secondary large">Acceder al Sistema</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="home-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Minimarket Pollon</h3>
                    <p>Sistema de gestión integral para minimarkets. Controla tu inventario, ventas y reportes en un solo lugar.</p>
                </div>
                <div class="footer-section">
                    <h4>Enlaces Rápidos</h4>
                    <ul>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                        <li><a href="register.php">Registrarse</a></li>
                        <li><a href="#features">Características</a></li>
                    </ul>
                </div>
                
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Minimarket Pollon. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>