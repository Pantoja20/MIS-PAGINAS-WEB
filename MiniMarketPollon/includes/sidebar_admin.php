<div class="sidebar-admin">
    <h3>Panel de Administración</h3>
    <ul>
        <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Inicio</a></li>
        <li><a href="productos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : ''; ?>">Gestión de Productos</a></li>
        <li><a href="inventario.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventario.php' ? 'active' : ''; ?>">Control de Inventario</a></li>
        <li><a href="usuarios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">Gestión de Usuarios</a></li>
        <li><a href="reportes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>">Reportes y Análisis</a></li>
    </ul>
</div>