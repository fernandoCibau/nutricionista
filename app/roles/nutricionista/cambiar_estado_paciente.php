<?php
// cambiar_estado_paciente.php (Nutricionista)
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$idPaciente  = (int)($_POST['paciente_id'] ?? 0); // pacientes.id
$nuevoEstado = strtolower($_POST['estado'] ?? ''); // 'activo' | 'inactivo' | 'pendiente'

if ($idPaciente <= 0 || !in_array($nuevoEstado, ['activo','inactivo','pendiente'], true)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

try {
    // Mapear estado por nombre a su ID
    $stmtEstado = $pdo->prepare("SELECT id FROM estados WHERE nombre = ? LIMIT 1");
    $stmtEstado->execute([$nuevoEstado]);
    $estadoId = $stmtEstado->fetchColumn();
    if (!$estadoId) {
        echo json_encode(['success' => false, 'message' => 'Estado no válido.']);
        exit;
    }

    // Actualizar usuarios.id_estado del paciente, validando pertenencia al nutricionista actual
    $st = $pdo->prepare("
        UPDATE usuarios u
        JOIN pacientes p ON p.id_usuario = u.id
        JOIN nutricionistas n ON n.id = p.id_nutricionista
        SET u.id_estado = ?
        WHERE p.id = ? AND n.id_usuario = ?
        LIMIT 1
    ");
    $st->execute([$estadoId, $idPaciente, $_SESSION['user_id']]);

    if ($st->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado.']);
        exit;
    }

    $msg = $nuevoEstado === 'activo' ? 'Paciente activado.' : ($nuevoEstado === 'inactivo' ? 'Paciente desactivado.' : 'Estado actualizado.');
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Throwable $e) {
    error_log('cambiar_estado_paciente: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cambiar estado.']);
}
