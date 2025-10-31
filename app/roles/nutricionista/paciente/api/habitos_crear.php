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
$nombre = trim($_POST['nombre'] ?? '');
$color = trim($_POST['color'] ?? '');
$visibilidad = 'publico';

if ($pacienteId <= 0 || $nombre === '') {
    echo json_encode(['success' => false, 'message' => 'Nombre requerido']);
    exit;
}

try {
    $st = $pdo->prepare("SELECT p.id FROM pacientes p JOIN nutricionistas n ON n.id=p.id_nutricionista WHERE p.id=? AND n.id_usuario=? LIMIT 1");
    $st->execute([$pacienteId, $_SESSION['user_id']]);
    if (!$st->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado']);
        exit;
    }

    $ins = $pdo->prepare("INSERT INTO habitos (id_paciente, nombre, color, visibilidad, creado_por, racha_dias) VALUES (?,?,?,?,?,0)");
    $ins->execute([$pacienteId, $nombre, ($color !== '' ? $color : null), $visibilidad, 'nutricionista']);

    echo json_encode(['success' => true, 'message' => 'Hábito agregado']);
} catch (Throwable $e) {
    error_log('habitos_crear: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al agregar hábito']);
}

