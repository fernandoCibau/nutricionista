<?php
// cambiar_estado_paciente.php (Super Usuario)
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// Solo super admin (rol id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
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
    $st = $pdo->prepare("UPDATE pacientes SET estado = ? WHERE id = ? LIMIT 1");
    $st->execute([$nuevoEstado, $idPaciente]);

    if ($st->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado.']);
        exit;
    }

    $msg = $nuevoEstado === 'alta' ? 'Paciente dado de alta médica.' : 'Paciente reingresado (activo).';
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Throwable $e) {
    error_log('SU cambiar_estado_paciente: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cambiar estado.']);
}

