<?php
/**
 * Script para que un superadministrador que está suplantando a otro usuario
 * pueda volver a su sesión original.
 */

session_start();

// 1. Verificar que el usuario actual esté suplantando a alguien.
// Es decir, que exista una sesión de administrador original guardada.
if (!isset($_SESSION['original_admin_id'])) {
    // Si no hay sesión original, no hay nada que hacer. Redirigir al inicio.
    header('Location: ../../index.php?error=no_suplantacion');
    exit;
}

// 2. Incluir configuración de la base de datos para obtener datos del admin.
require_once '../../config.php';

try {
    // 3. Restaurar la sesión del administrador desde los datos guardados.
    $_SESSION['user_id'] = $_SESSION['original_admin_id'];
    $_SESSION['user_nombre'] = $_SESSION['original_admin_nombre'];
    $_SESSION['user_rol'] = 1; // El rol de superadmin es siempre 1.

    // 4. Limpiar las variables de sesión de la suplantación.
    unset($_SESSION['original_admin_id']);
    unset($_SESSION['original_admin_nombre']);
<<<<<<< HEAD

=======
    
>>>>>>> origin/master
    // Limpiar estado del formulario de creación de usuario para evitar persistencia
    unset($_SESSION['add_user_step']);
    unset($_SESSION['add_user_role_id']);
    unset($_SESSION['add_user_nutri_id']);

    // 5. Redirigir de vuelta al panel de superadministrador con un mensaje de éxito.
    header('Location: index.php?exito=admin_restaurado');
    exit;
} catch (Exception $e) {
    error_log("Error al volver a la sesión de admin: " . $e->getMessage());

    
    header('Location: index.php?error=db_error');
    exit;
}
?>