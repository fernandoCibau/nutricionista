<?php
// cambiar_estado_paciente.php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$idPaciente  = (int)($_POST['paciente_id'] ?? 0);
$nuevoEstado = $_POST['estado'] ?? '';

if ($idPaciente <= 0 || !in_array($nuevoEstado, ['activo','alta'], true)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

try {
    $st = $pdo->prepare("
        UPDATE pacientes p
        JOIN nutricionistas n ON n.id = p.id_nutricionista
        SET p.estado = ?
        WHERE p.id = ? AND n.id_usuario = ?
        LIMIT 1
    ");
    $st->execute([$nuevoEstado, $idPaciente, $_SESSION['user_id']]);

    if ($st->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado.']);
        exit;
    }

    $msg = $nuevoEstado === 'alta' ? 'Paciente dado de alta médica.' : 'Paciente reingresado (activo).';
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Throwable $e) {
    error_log('cambiar_estado_paciente: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cambiar estado.']);
}
