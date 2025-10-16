<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Administrador') {
    header("Location: ../login.php");
    exit();
}

include('../config/database.php');
$database = new Database();
$db = $database->getConnection();

// Agregar usuario
if ($_POST && isset($_POST['agregar_usuario'])) {
    $nombre_completo = $_POST['nombre_completo'];
    $usuario = $_POST['usuario'];
    $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    
    // Verificar si el usuario ya existe
    $check_query = "SELECT id FROM usuarios WHERE usuario = :usuario";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':usuario', $usuario);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $error = "El nombre de usuario ya existe";
    } else {
        $query = "INSERT INTO usuarios (nombre_completo, usuario, contraseña, rol) 
                  VALUES (:nombre_completo, :usuario, :contraseña, :rol)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre_completo', $nombre_completo);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':contraseña', $contraseña);
        $stmt->bindParam(':rol', $rol);
        
        if ($stmt->execute()) {
            $success = "Usuario agregado exitosamente";
        } else {
            $error = "Error al agregar el usuario";
        }
    }
}

// Cambiar estado de usuario
if (isset($_GET['cambiar_estado'])) {
    $id = $_GET['cambiar_estado'];
    $estado_actual = $_GET['estado'];
    $nuevo_estado = $estado_actual == 'Activo' ? 'Inactivo' : 'Activo';
    
    $query = "UPDATE usuarios SET estado = :estado WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':estado', $nuevo_estado);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $success = "Estado del usuario actualizado";
    } else {
        $error = "Error al actualizar el estado";
    }
}

// Obtener usuarios
$query = "SELECT * FROM usuarios ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Administrador</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <div class="container">
        <?php include('../includes/sidebar_admin.php'); ?>
        
        <div class="main-content">
            <h2>Gestión de Usuarios</h2>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Formulario para agregar usuario -->
            <div class="card">
                <h3>Agregar Nuevo Usuario</h3>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre_completo">Nombre Completo</label>
                            <input type="text" id="nombre_completo" name="nombre_completo" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="usuario">Nombre de Usuario</label>
                            <input type="text" id="usuario" name="usuario" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contraseña">Contraseña</label>
                            <input type="password" id="contraseña" name="contraseña" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="rol">Rol</label>
                            <select id="rol" name="rol" required>
                                <option value="">Seleccionar rol</option>
                                <option value="Cajero">Cajero</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="agregar_usuario" class="btn-primary">Agregar Usuario</button>
                </form>
            </div>
            
            <!-- Lista de usuarios -->
            <div class="card">
                <h3>Lista de Usuarios</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                                <td><?php echo $usuario['rol']; ?></td>
                                <td>
                                    <span class="estado <?php echo strtolower($usuario['estado']); ?>">
                                        <?php echo $usuario['estado']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                                <td>
                                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                    <a href="?cambiar_estado=<?php echo $usuario['id']; ?>&estado=<?php echo $usuario['estado']; ?>" 
                                       class="btn-action <?php echo $usuario['estado'] == 'Activo' ? 'btn-delete' : 'btn-edit'; ?>"
                                       onclick="return confirm('¿Estás seguro de cambiar el estado de este usuario?')">
                                        <?php echo $usuario['estado'] == 'Activo' ? 'Desactivar' : 'Activar'; ?>
                                    </a>
                                    <?php else: ?>
                                        <span class="text-muted">Usuario actual</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>