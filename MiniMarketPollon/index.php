<?php
session_start();
include('config/database.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $contraseña = $_POST['contraseña'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM usuarios WHERE usuario = :usuario AND estado = 'Activo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($contraseña, $user['contraseña'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre_completo'];
            $_SESSION['user_role'] = $user['rol'];
            
            if ($user['rol'] == 'Administrador') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: cajero/dashboard.php");
            }
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Minimarket Pollon</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <h1>Minimarket Pollon</h1>
                <p>Sistema de Gestión</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>
                
                <div class="form-group">
                    <label for="contraseña">Contraseña</label>
                    <input type="password" id="contraseña" name="contraseña" required>
                </div>
                
                <button type="submit" class="btn-primary">Iniciar Sesión</button>
            </form>
            
            <div class="register-link">
                <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>