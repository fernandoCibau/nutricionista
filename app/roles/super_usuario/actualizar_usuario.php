<?php
/**
 * Script para actualizar los datos de un usuario.
 * Solo accesible por el superadmin.
 */

session_start();

// 1. Seguridad: Verificar sesión y rol de superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    header('Location: ../../index.php');
    exit;
}

// 2. Verificar que la solicitud sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once '../../config.php';

// 3. Obtener y validar los datos del formulario
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$nombre = trim($_POST['user_name'] ?? '');
$email = trim($_POST['user_email'] ?? '');
$role_id = filter_input(INPUT_POST, 'user_role_id', FILTER_VALIDATE_INT);
$status_id = filter_input(INPUT_POST, 'user_status_id', FILTER_VALIDATE_INT);

if (!$user_id || empty($nombre) || empty($email) || !$role_id) {
    header('Location: index.php?error=campos_vacios_actualizar');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?error=email_invalido_actualizar');
    exit;
}

// Iniciar transacción
$pdo->beginTransaction();

try {
    // 4. Verificar que el nuevo email no pertenezca a otro usuario
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        header('Location: index.php?error=email_existente_actualizar');
        exit;
    }

    // 5. Actualizar la tabla 'usuarios'
    // Construir la consulta de actualización dinámicamente
    $sql_update = "UPDATE usuarios SET nombre = ?, email = ?, role_id = ?";
    $params = [$nombre, $email, $role_id];

    // 6. Si se envió un nuevo estado, añadirlo a la consulta
    if ($status_id) {
        $sql_update .= ", id_estado = ?";
        $params[] = $status_id;
    }

    $sql_update .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt_update_user = $pdo->prepare($sql_update);
    $stmt_update_user->execute($params);

    // 7. Confirmar la transacción
    $pdo->commit();

    header('Location: index.php?exito=usuario_actualizado');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al actualizar usuario: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}
?>