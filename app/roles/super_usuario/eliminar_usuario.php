<?php
// 1. Iniciar la sesión y verificar el rol de superadmin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    header('Location: ../../index.php');
    exit;
}

// 2. Verificar que la solicitud sea por método POST para mayor seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 3. Incluir dependencias
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/logica_eliminacion.php'; // Incluimos la nueva lógica de eliminación

// 4. Obtener y validar el ID del usuario a eliminar
$user_id = filter_var($_POST['delete_user_id'] ?? '', FILTER_VALIDATE_INT);
$admin_password = $_POST['admin_password'] ?? '';

if ($user_id === false || empty($admin_password)) {
    header('Location: index.php?error=id_invalido');
    exit;
}

// 5. Medida de seguridad: un superadmin no puede eliminarse a sí mismo
if ($user_id === $_SESSION['user_id']) {
    header('Location: index.php?error=auto_eliminacion');
    exit;
}

try {
    // 6. Verificar la contraseña del superadministrador que realiza la acción
    $admin_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin_hash = $stmt->fetchColumn();

    if (!$admin_hash || !password_verify($admin_password, $admin_hash)) {
        // La contraseña es incorrecta
        header('Location: index.php?error=password_incorrecta');
        exit;
    }

    // 7. Si la contraseña es correcta, proceder con la eliminación en cascada.
    // Iniciamos una transacción para asegurar que todas las eliminaciones se completen o ninguna.
    $pdo->beginTransaction();

    // Obtenemos el rol del usuario que se va a eliminar
    $rol_usuario_a_eliminar = obtenerRolUsuario($user_id, $pdo);

    if (!$rol_usuario_a_eliminar) {
        $pdo->rollBack();
        header('Location: index.php?error=usuario_no_encontrado');
        exit;
    }

    // 8. Ejecutar la función de eliminación correspondiente según el rol
    switch ($rol_usuario_a_eliminar) {
        case 'nutricionista':
            $pacientes_eliminados = eliminarNutricionistaCompleto($user_id, $pdo);
            if ($pacientes_eliminados > 0) {
                $_SESSION['flash_message'] = "Además, se eliminaron $pacientes_eliminados pacientes asociados.";
            }
            break;
        
        case 'paciente':
            eliminarPacienteCompleto($user_id, $pdo);
            break;

        default:
            // Para otros roles (como otro superadmin), solo eliminamos el usuario
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$user_id]);
            break;
    }

    // 9. Si todo fue bien, confirmamos la transacción
    $pdo->commit();

    header('Location: index.php?exito=usuario_eliminado');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al eliminar usuario: " . $e->getMessage());
    header('Location: index.php?error=db_error_eliminar');
    exit;
}
?>