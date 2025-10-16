<?php
session_start();
include('config/database.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_completo = trim($_POST['nombre_completo']);
    $usuario = trim($_POST['usuario']);
    $contraseña = $_POST['contraseña'];
    $rol = $_POST['rol'];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si el usuario ya existe
        $check_sql = "SELECT id FROM usuarios WHERE usuario = ?";
        $check_stmt = $db->prepare($check_sql);
        $check_stmt->execute([$usuario]);
        
        if ($check_stmt->rowCount() > 0) {
            $error = "El nombre de usuario ya existe";
        } else {
            // Hashear la contraseña
            $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
            
            // Insertar usando parámetros posicionales
            $sql = "INSERT INTO usuarios (nombre_completo, usuario, contraseña, rol) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            
            if ($stmt->execute([$nombre_completo, $usuario, $contraseña_hash, $rol])) {
                $success = "Usuario registrado exitosamente. Puedes iniciar sesión.";
                $_POST = array(); // Limpiar formulario
            } else {
                $error = "Error al registrar el usuario";
            }
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Minimarket Pollon</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <h1>Minimarket Pollon</h1>
                <p>Crear Nueva Cuenta</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" 
                           value="<?php echo isset($_POST['nombre_completo']) ? htmlspecialchars($_POST['nombre_completo']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" 
                           value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="contraseña">Contraseña</label>
                    <input type="password" id="contraseña" name="contraseña" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" required>
                        <option value="">Seleccionar rol</option>
                        <option value="Cajero" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'Cajero') ? 'selected' : ''; ?>>Cajero</option>
                        <option value="Administrador" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">Registrarse</button>
            </form>
            
            <div class="register-link">
                <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>