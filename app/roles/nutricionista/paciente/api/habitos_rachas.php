<?php
session_start();
require_once '../../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$pacienteId = (int)($_GET['paciente_id'] ?? 0);
if ($pacienteId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de paciente inválido']);
    exit;
}

try {
    // Validar que el paciente pertenece al nutricionista logueado
    $st = $pdo->prepare("SELECT p.id FROM pacientes p JOIN nutricionistas n ON n.id=p.id_nutricionista WHERE p.id=? AND n.id_usuario=? LIMIT 1");
    $st->execute([$pacienteId, $_SESSION['user_id']]);
    if (!$st->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado']);
        exit;
    }

    $q = $pdo->prepare("SELECT id, nombre, color, racha_dias FROM habitos WHERE id_paciente=? ORDER BY racha_dias DESC, nombre ASC");
    $q->execute([$pacienteId]);
    $rows = $q->fetchAll();
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    error_log('habitos_rachas: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener hábitos']);
}

