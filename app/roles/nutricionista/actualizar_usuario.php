<?php
// 1) Iniciar sesión y verificar rol NUTRICIONISTA
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

// 2) Debe ser POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 3) Conexión
require_once __DIR__ . '/../../config.php';

// 4) Inputs
$user_id  = filter_var($_POST['usuario_id'] ?? $_POST['user_id'] ?? '', FILTER_VALIDATE_INT); // compat
$nombre   = trim($_POST['user_name'] ?? '');
$email    = trim($_POST['user_email'] ?? '');

// Estos vienen del modal Editar Paciente
$dni              = trim($_POST['dni'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;   // YYYY-MM-DD o ''
$telefono         = trim($_POST['telefono'] ?? '');
$objetivo         = trim($_POST['objetivo_principal'] ?? '');
$estado           = trim($_POST['estado'] ?? 'activo');   // activo | alta

// (Opcional/legacy) role_id si tu form lo sigue enviando:
$role_id = isset($_POST['user_role_id']) ? filter_var($_POST['user_role_id'], FILTER_VALIDATE_INT) : null;

// Validaciones básicas
if ($user_id === false || $user_id <= 0 || $nombre === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?error=campos_vacios_actualizar');
    exit;
}

// Si te asegurás por servidor que SIEMPRE es paciente:
if ($role_id !== null) {
    $stmtRole = $pdo->prepare("SELECT nombre FROM roles WHERE id = ? LIMIT 1");
    $stmtRole->execute([$role_id]);
    $roleRow = $stmtRole->fetch(PDO::FETCH_ASSOC);
    if (!$roleRow || mb_strtolower($roleRow['nombre']) !== 'paciente') {
        header('Location: index.php?error=rol_invalido_actualizar');
        exit;
    }
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- TRANSACTION ---
    $pdo->beginTransaction();

    // 5) Verificar que el usuario existe, es PACIENTE y pertenece a este nutricionista
    //    Usamos la tabla pacientes + nutricionistas para validar pertenencia.
    $q = $pdo->prepare("
        SELECT 
            u.id AS usuario_id,
            r.nombre AS role_name,
            p.id AS paciente_id,
            n.id AS nutri_id
        FROM usuarios u
        JOIN roles r        ON r.id = u.role_id
        JOIN pacientes p    ON p.id_usuario = u.id
        JOIN nutricionistas n ON n.id = p.id_nutricionista
        WHERE u.id = ? AND n.id_usuario = ?
        LIMIT 1
    ");
    $q->execute([$user_id, $_SESSION['user_id']]);
    $row = $q->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // O no existe, o no es tu paciente
        $pdo->rollBack();
        header('Location: index.php?error=sin_permiso_paciente');
        exit;
    }
    if (mb_strtolower($row['role_name']) !== 'paciente') {
        $pdo->rollBack();
        header('Location: index.php?error=no_autorizado');
        exit;
    }

    $paciente_id = (int)$row['paciente_id'];

    // 6) Evitar colisión de email en otro usuario
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        header('Location: index.php?error=email_existente_actualizar');
        exit;
    }

    // 7) Actualizar datos básicos de usuario
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
    $stmt->execute([$nombre, $email, $user_id]);

    // 8) Normalizar valores para pacientes ('' -> NULL). Validar estado permitido.
    $dni_n              = ($dni !== '') ? $dni : null;
    $fecha_nacimiento_n = ($fecha_nacimiento !== '') ? $fecha_nacimiento : null;
    $telefono_n         = ($telefono !== '') ? $telefono : null;
    $objetivo_n         = ($objetivo !== '') ? $objetivo : null;
    $estado_n           = in_array($estado, ['activo', 'alta'], true) ? $estado : 'activo';

    // 9) Actualizar ficha del paciente
    $stmt = $pdo->prepare("
        UPDATE pacientes
        SET dni = ?, fecha_nacimiento = ?, telefono = ?, objetivo_principal = ?, estado = ?
        WHERE id = ?
    ");
    $stmt->execute([$dni_n, $fecha_nacimiento_n, $telefono_n, $objetivo_n, $estado_n, $paciente_id]);

    // 10) Listo
    $pdo->commit();
    header('Location: index.php?exito=paciente_actualizado');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al actualizar paciente/usuario: " . $e->getMessage());
    header('Location: index.php?error=db_error_actualizar');
    exit;
}
