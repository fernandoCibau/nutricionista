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

// 3. Obtener y preparar datos del formulario
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$nombre = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
$email = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
$role_id = filter_input(INPUT_POST, 'user_role_id', FILTER_VALIDATE_INT);
$status_id = filter_input(INPUT_POST, 'user_status_id', FILTER_VALIDATE_INT);
$paciente_estado = isset($_POST['paciente_estado']) ? trim($_POST['paciente_estado']) : '';

if (!$user_id) {
    header('Location: index.php?error=campos_vacios_actualizar');
    exit;
}

// Iniciar transacción
$pdo->beginTransaction();

try {
    // 4. Leer valores actuales del usuario para permitir cambios parciales
    $stmt_curr = $pdo->prepare("SELECT nombre, email, role_id FROM usuarios WHERE id = ? LIMIT 1");
    $stmt_curr->execute([$user_id]);
    $actual = $stmt_curr->fetch(PDO::FETCH_ASSOC);
    if (!$actual) {
        $pdo->rollBack();
        header('Location: index.php?error=db_error');
        exit;
    }

    // Usar valores actuales si vienen vacíos
    $nuevo_nombre = ($nombre !== '') ? $nombre : $actual['nombre'];
    $nuevo_email_orig = ($email !== '') ? $email : $actual['email'];
    $nuevo_role_id = ($role_id) ? $role_id : (int)$actual['role_id'];

    // Validar email solo si es distinto y es válido; si no es válido, conservar email actual
    $nuevo_email = $nuevo_email_orig;
    $email_cambiado = ($nuevo_email_orig !== $actual['email']);
    if ($email_cambiado && !filter_var($nuevo_email_orig, FILTER_VALIDATE_EMAIL)) {
        $nuevo_email = $actual['email'];
        $email_cambiado = false; // como no aceptamos el nuevo por inválido, no disparar check de unicidad
    }

    // 4.1. Verificar que el nuevo email no pertenezca a otro usuario (solo si cambió)
    if ($email_cambiado) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$nuevo_email, $user_id]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            header('Location: index.php?error=email_existente_actualizar');
            exit;
        }
    }

    // 5. Actualizar la tabla 'usuarios'
    // Construir la consulta de actualización dinámicamente
    $sql_update = "UPDATE usuarios SET nombre = ?, email = ?, role_id = ?";
    $params = [$nuevo_nombre, $nuevo_email, $nuevo_role_id];

    // 6. Si se envió un nuevo estado, añadirlo a la consulta
    if ($status_id) {
        $sql_update .= ", id_estado = ?";
        $params[] = $status_id;
    }

    $sql_update .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt_update_user = $pdo->prepare($sql_update);
    $stmt_update_user->execute($params);

    // 6.1. Actualizar estado clínico del paciente si corresponde
    if ($paciente_estado !== '' && in_array($paciente_estado, ['activo','alta'], true)) {
        $stmt_up_p = $pdo->prepare("UPDATE pacientes SET estado = ? WHERE id_usuario = ? LIMIT 1");
        $stmt_up_p->execute([$paciente_estado, $user_id]);
    }

    // 6.1. Actualizar estado clínico del paciente si corresponde
    if ($paciente_estado !== '' && in_array($paciente_estado, ['activo','alta'], true)) {
        $stmt_up_p = $pdo->prepare("UPDATE pacientes SET estado = ? WHERE id_usuario = ? LIMIT 1");
        $stmt_up_p->execute([$paciente_estado, $user_id]);
    }

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
