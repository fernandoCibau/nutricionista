<?php
/**
 * Script para restaurar la sesión del superadministrador después de suplantar a un usuario.
 */

session_start();

// 1. Verificar que hay una sesión de administrador original guardada.
if (!isset($_SESSION['original_admin_id'])) {
    // Si no hay, es un acceso no válido. Redirigir al login.
    header('Location: ../../index.php');
    exit;
}

// 2. Incluir configuración de la base de datos.
require_once '../../config.php';

try {
    // 3. Obtener los datos originales del administrador.
    $stmt = $pdo->prepare("SELECT id, nombre, email, role_id FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['original_admin_id']]);
    $admin_user = $stmt->fetch();

    if ($admin_user) {
        // 4. Restaurar la sesión del administrador.
        $_SESSION['user_id'] = $admin_user['id'];
        $_SESSION['user_nombre'] = $admin_user['nombre'];
        $_SESSION['user_email'] = $admin_user['email'];
        $_SESSION['user_rol'] = $admin_user['role_id'];
    }

    // 5. Limpiar las variables de sesión de suplantación.
    unset($_SESSION['original_admin_id']);
    unset($_SESSION['original_admin_nombre']);

    // 6. Redirigir al panel de superadministrador con un mensaje de éxito.
    header('Location: index.php?exito=admin_restaurado');
    exit;

} catch (PDOException $e) {
    error_log("Error al restaurar sesión de admin: " . $e->getMessage());
    header('Location: ../../index.php?error=db_error');
    exit;
}
?>