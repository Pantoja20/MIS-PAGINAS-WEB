<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <div class="logo">
        <h1>Minimarket Pollon</h1>
    </div>
    <div class="user-info">
        <span>Bienvenido, <?php echo $_SESSION['user_name']; ?> </span>
        <div class="user-actions">
            <a href="../logout.php">Cerrar Sesión</a>
        </div>
    </div>
    <script src="../js/script.js"></script>
</header>