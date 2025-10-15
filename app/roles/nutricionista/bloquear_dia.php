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

$fecha = trim($_POST['fecha'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');

if (empty($fecha)) {
    header('Location: index.php?error=fecha_invalida');
    exit;
}

$check = $pdo->query("SHOW TABLES LIKE 'dias_no_laborales'");
if (!$check || $check->rowCount() === 0) {
    header('Location: index.php?error=tabla_dias_no_laborales_no_exist');
    exit;
}

try {
    $ins = $pdo->prepare("INSERT INTO dias_no_laborales (id_nutricionista, fecha, motivo, creado_en) VALUES (?, ?, ?, NOW())");
    $ins->execute([$_SESSION['user_id'], $fecha, $motivo]);
    header('Location: index.php?exito=dia_bloqueado');
    exit;
} catch (PDOException $e) {
    error_log('Error al bloquear día: ' . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
