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
    $st = $pdo->prepare("SELECT p.id FROM pacientes p JOIN nutricionistas n ON n.id=p.id_nutricionista WHERE p.id=? AND n.id_usuario=? LIMIT 1");
    $st->execute([$pacienteId, $_SESSION['user_id']]);
    if (!$st->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado']);
        exit;
    }

    $q = $pdo->prepare("SELECT fecha, peso_kg FROM consultas WHERE id_paciente=? AND peso_kg IS NOT NULL ORDER BY fecha ASC");
    $q->execute([$pacienteId]);
    $rows = $q->fetchAll();

    $labels = [];
    $data = [];
    foreach ($rows as $r) {
        $labels[] = date('d/m', strtotime($r['fecha']));
        $data[] = (float)$r['peso_kg'];
    }

    echo json_encode(['success' => true, 'labels' => $labels, 'data' => $data]);
} catch (Throwable $e) {
    error_log('evolucion_peso: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener evolución']);
}
