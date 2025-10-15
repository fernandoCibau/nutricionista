<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$id_paciente = filter_var($_POST['id_paciente'] ?? '', FILTER_VALIDATE_INT);
$titulo = trim($_POST['titulo'] ?? '');
$contenido = trim($_POST['contenido'] ?? '');

if ($id_paciente === false || empty($titulo) || empty($contenido)) {
    header('Location: index.php?error=campos_vacios');
    exit;
}

$check = $pdo->query("SHOW TABLES LIKE 'dietas'");
if (!$check || $check->rowCount() === 0) {
    header('Location: index.php?error=tabla_dietas_no_exist');
    exit;
}

// Verificar pertenencia del paciente
$stmtUser = $pdo->prepare("SELECT u.id, u.assigned_nutricionista_id, r.nombre as role_name FROM usuarios u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
$stmtUser->execute([$id_paciente]);
$userRow = $stmtUser->fetch();
if (!$userRow || strtolower($userRow['role_name']) !== 'paciente') {
    header('Location: index.php?error=paciente_no_encontrado');
    exit;
}

$colCheck = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'assigned_nutricionista_id'");
if ($colCheck && $colCheck->rowCount() > 0) {
    if ($userRow['assigned_nutricionista_id'] == null || $userRow['assigned_nutricionista_id'] != $_SESSION['user_id']) {
        header('Location: index.php?error=no_autorizado');
        exit;
    }
} else {
    $stmtTurn = $pdo->prepare("SELECT 1 FROM turnos WHERE id_nutricionista = ? AND id_paciente = ? LIMIT 1");
    $stmtTurn->execute([$_SESSION['user_id'], $id_paciente]);
    if (!$stmtTurn->fetch()) {
        header('Location: index.php?error=no_autorizado');
        exit;
    }
}

try {
    $ins = $pdo->prepare("INSERT INTO dietas (id_paciente, id_nutricionista, titulo, contenido, creado_en) VALUES (?, ?, ?, ?, NOW())");
    $ins->execute([$id_paciente, $_SESSION['user_id'], $titulo, $contenido]);
    header('Location: index.php?exito=dieta_agregada');
    exit;
} catch (PDOException $e) {
    error_log('Error al agregar dieta: ' . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
