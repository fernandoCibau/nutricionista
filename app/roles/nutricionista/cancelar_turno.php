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

$fecha_hora = $_POST['fecha_hora'] ?? '';
$id_paciente = filter_var($_POST['id_paciente'] ?? '', FILTER_VALIDATE_INT);

if (empty($fecha_hora) || $id_paciente === false) {
    header('Location: index.php?error=turno_invalido');
    exit;
}

try {
    // Verificar que el turno con esa fecha pertenezca al paciente y nutricionista y esté programado
    $stmt = $pdo->prepare("SELECT id_nutricionista, id_paciente, fecha_hora, estado FROM turnos WHERE id_nutricionista = ? AND id_paciente = ? AND fecha_hora = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $id_paciente, $fecha_hora]);
    $found = $stmt->fetch();
    if (!$found || $found['estado'] !== 'programado') {
        header('Location: index.php?error=turno_no_encontrado');
        exit;
    }

    $upd = $pdo->prepare("UPDATE turnos SET estado = 'cancelado' WHERE id_nutricionista = ? AND id_paciente = ? AND fecha_hora = ?");
    $upd->execute([$_SESSION['user_id'], $id_paciente, $fecha_hora]);
    header('Location: index.php?exito=turno_cancelado');
    exit;
} catch (PDOException $e) {
    error_log("Error al cancelar turno: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
