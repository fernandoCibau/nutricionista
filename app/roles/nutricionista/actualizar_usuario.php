<?php
// 1. Iniciar la sesión y verificar el rol de nutricionista
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
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
// Forzar servidor-side que el rol sea paciente
$role_id = filter_var($_POST['user_role_id'] ?? '', FILTER_VALIDATE_INT);

// Verificar que role_id corresponde a 'paciente'
$stmtRole = $pdo->prepare("SELECT nombre FROM roles WHERE id = ? LIMIT 1");
$stmtRole->execute([$role_id]);
$roleRow = $stmtRole->fetch();
if (!$roleRow || strtolower($roleRow['nombre']) !== 'paciente') {
    header('Location: index.php?error=rol_invalido_actualizar');
    exit;
}

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
    // 5. Verificar si el usuario a actualizar existe y pertenece a este nutricionista
    $stmtUser = $pdo->prepare("SELECT u.id, u.role_id, r.nombre as role_name, u.assigned_nutricionista_id FROM usuarios u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
    $stmtUser->execute([$user_id]);
    $target = $stmtUser->fetch();
    if (!$target) {
        header('Location: index.php?error=usuario_no_encontrado');
        exit;
    }

    if (strtolower($target['role_name']) !== 'paciente') {
        header('Location: index.php?error=no_autorizado');
        exit;
    }

    // Verificar pertenencia mediante assigned_nutricionista_id o turnos
    if (isset($target['assigned_nutricionista_id']) && $target['assigned_nutricionista_id'] !== null) {
        if ($target['assigned_nutricionista_id'] != $_SESSION['user_id']) {
            header('Location: index.php?error=no_autorizado');
            exit;
        }
    } else {
        $stmtTurn = $pdo->prepare("SELECT 1 FROM turnos WHERE id_nutricionista = ? AND id_paciente = ? LIMIT 1");
        $stmtTurn->execute([$_SESSION['user_id'], $user_id]);
        if (!$stmtTurn->fetch()) {
            header('Location: index.php?error=no_autorizado');
            exit;
        }
    }

    // 6. Verificar si el nuevo email ya está en uso por OTRO usuario
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        // El email ya está registrado por otro usuario
        header('Location: index.php?error=email_existente_actualizar');
        exit;
    }

    // 7. Actualizar los datos del usuario en la base de datos (no permitimos cambiar role fuera de paciente)
    $updateStmt = $pdo->prepare(
        "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?"
    );
    $updateStmt->execute([$nombre, $email, $user_id]);

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