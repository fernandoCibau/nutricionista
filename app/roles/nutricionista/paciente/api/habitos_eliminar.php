<?php
session_start();
require_once '../../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$pacienteId = (int)($_POST['paciente_id'] ?? 0);
$habitoId = (int)($_POST['habito_id'] ?? 0);

if ($pacienteId <= 0 || $habitoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    // Verificar que el hábito pertenece al paciente y al nutricionista
    $st = $pdo->prepare("SELECT h.id FROM habitos h JOIN pacientes p ON h.id_paciente = p.id WHERE h.id = ? AND p.id = ? AND p.id_nutricionista = (SELECT id FROM nutricionistas WHERE id_usuario = ?) LIMIT 1");
    $st->execute([$habitoId, $pacienteId, $_SESSION['user_id']]);
    if (!$st->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Hábito no encontrado o no autorizado']);
        exit;
    }

    // Eliminar registros de completados primero (por la restricción de clave foránea)
    $pdo->prepare("DELETE FROM habit_completados WHERE id_habito = ?")->execute([$habitoId]);

    // Eliminar el hábito
    $pdo->prepare("DELETE FROM habitos WHERE id = ?")->execute([$habitoId]);

    echo json_encode(['success' => true, 'message' => 'Hábito eliminado']);
} catch (Throwable $e) {
    error_log('habitos_eliminar: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar hábito']);
}
