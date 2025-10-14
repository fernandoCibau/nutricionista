<?php
// 1. Iniciar la sesión y verificar el rol de superadmin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    header('Location: ../../index.php');
    exit;
}

// 2. Verificar que la solicitud sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 3. Incluir dependencias
require_once __DIR__ . '/../../config.php';

// 4. Obtener y validar los datos del formulario
$user_id = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
$nombre = trim($_POST['user_name'] ?? '');
$email = trim($_POST['user_email'] ?? '');
$role_id = filter_var($_POST['user_role_id'] ?? '', FILTER_VALIDATE_INT);

// Validar que los datos no estén vacíos
if ($user_id === false || empty($nombre) || empty($email) || $role_id === false) {
    header('Location: index.php?error=campos_vacios_actualizar');
    exit;
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?error=email_invalido_actualizar');
    exit;
}

try {
    // 5. Verificar si el nuevo email ya está en uso por OTRO usuario
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        // El email ya está registrado por otro usuario
        header('Location: index.php?error=email_existente_actualizar');
        exit;
    }

    // 6. Actualizar los datos del usuario en la base de datos
    $updateStmt = $pdo->prepare(
        "UPDATE usuarios SET nombre = ?, email = ?, role_id = ? WHERE id = ?"
    );
    $updateStmt->execute([$nombre, $email, $role_id, $user_id]);

    // 7. Redirigir con mensaje de éxito
    header('Location: index.php?exito=usuario_actualizado');
    exit;

} catch (PDOException $e) {
    // En caso de un error de base de datos, registrar y redirigir
    error_log("Error al actualizar usuario: " . $e->getMessage());
    header('Location: index.php?error=db_error_actualizar');
    exit;
}
?>