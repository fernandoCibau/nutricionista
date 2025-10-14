<?php
// 1. Iniciar la sesión y verificar el rol de paciente
session_start();
// Los pacientes no pueden eliminar otros usuarios. Revocar acceso.
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3) {
    header('Location: ../../index.php');
    exit;
}

header('Location: index.php?error=permiso_denegado');
exit;

// 2. Verificar que la solicitud sea por método POST para mayor seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 3. Incluir dependencias
require_once __DIR__ . '/../../config.php';

// 4. Obtener y validar el ID del usuario a eliminar
$user_id = filter_var($_POST['delete_user_id'] ?? '', FILTER_VALIDATE_INT);
$admin_password = $_POST['admin_password'] ?? '';

if ($user_id === false || empty($admin_password)) {
    header('Location: index.php?error=id_invalido');
    exit;
}

// 5. Medida de seguridad: un paciente no puede eliminarse a sí mismo
if ($user_id === $_SESSION['user_id']) {
    header('Location: index.php?error=auto_eliminacion');
    exit;
}

try {
    // 6. Verificar la contraseña del paciente
    $admin_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin_user = $stmt->fetch();

    if (!$admin_user || !password_verify($admin_password, $admin_user['password'])) {
        // La contraseña es incorrecta
        header('Location: index.php?error=password_incorrecta');
        exit;
    }

    // Si la contraseña es correcta, proceder con la eliminación

    // 7. Ejecutar la consulta para eliminar al usuario
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);

    // 7. Verificar si se eliminó alguna fila
    if ($stmt->rowCount() > 0) {
        header('Location: index.php?exito=usuario_eliminado');
    } else {
        header('Location: index.php?error=usuario_no_encontrado'); // El usuario ya no existía
    }
    exit;

} catch (PDOException $e) {
    error_log("Error al eliminar usuario: " . $e->getMessage());
    header('Location: index.php?error=db_error_eliminar');
    exit;
}
?>