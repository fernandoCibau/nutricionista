<?php
/**
 * Script para que un superadministrador pueda suplantar la identidad de otro usuario.
 */

session_start();

// 1. Verificar que el usuario actual es un superadministrador.
// Y que no esté ya suplantando a alguien.
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1 || isset($_SESSION['original_admin_id'])) {
    header('Location: ../../index.php?error=no_autorizado');
    exit;
}

// 2. Incluir configuración de la base de datos.
require_once '../../config.php';

// 3. Obtener y validar el ID del usuario a suplantar.
$target_user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$target_user_id) {
    header('Location: index.php?error=id_invalido');
    exit;
}

// 4. No se puede suplantar a uno mismo.
if ($target_user_id === $_SESSION['user_id']) {
    header('Location: index.php?error=auto_suplantacion');
    exit;
}

try {
    // 5. Obtener los datos del usuario a suplantar.
    $stmt = $pdo->prepare("SELECT id, nombre, email, role_id FROM usuarios WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch();

    if (!$target_user) {
        header('Location: index.php?error=usuario_no_encontrado');
        exit;
    }

    // 6. Guardar la sesión del administrador actual para poder volver.
    $_SESSION['original_admin_id'] = $_SESSION['user_id'];
    $_SESSION['original_admin_nombre'] = $_SESSION['user_nombre'];

    // 7. Sobrescribir la sesión actual con los datos del usuario suplantado.
    $_SESSION['user_id'] = $target_user['id'];
    $_SESSION['user_nombre'] = $target_user['nombre'];
    $_SESSION['user_email'] = $target_user['email'];
    $_SESSION['user_rol'] = $target_user['role_id'];

    // 8. Redirigir al panel correspondiente del usuario suplantado.
    switch ($target_user['role_id']) {
        case 2: // Nutricionista
            header('Location: ../nutricionista/index.php');
            break;
        case 3: // Paciente
            header('Location: ../paciente/index.php');
            break;
        default: // Por si acaso, redirigir al login para que resuelva.
            header('Location: ../../index.php');
            break;
    }
    exit;

} catch (PDOException $e) {
    error_log("Error al suplantar usuario: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}
?>